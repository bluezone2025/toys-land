<?php

namespace App\Filament\Resources\EmailCampaigns;

use App\Console\Commands\RunEmailCampaigns;
use App\Filament\Resources\EmailCampaigns\Pages\CreateEmailCampaign;
use App\Filament\Resources\EmailCampaigns\Pages\EditEmailCampaign;
use App\Filament\Resources\EmailCampaigns\Pages\ListEmailCampaigns;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\GuestCustomer;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'التسويق';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'حملة بريدية';

    protected static ?string $pluralModelLabel = 'الرسائل البريدية الدورية';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الإعدادات')->columns(3)->columnSpanFull()->schema([
                TextInput::make('name')->label('اسم الحملة (داخلي)')->required()->placeholder('مثال: عروض نهاية الأسبوع'),
                Select::make('repeat_hours')->label('التكرار')->options([24 => 'كل يوم', 48 => 'كل 48 ساعة', 72 => 'كل 3 أيام', 168 => 'كل أسبوع', 336 => 'كل أسبوعين'])
                    ->placeholder('يدوي فقط (بزر الإرسال)')->default(48),
                Select::make('coupon_id')->label('كود خصم يرفق مع الرسالة')->relationship('coupon', 'code')->placeholder('بدون كود'),
                Toggle::make('is_active')->label('مفعلة')->default(true),
                Toggle::make('include_guests')->label('ترسل كمان للزوار اللي اشتروا بدون حساب'),
            ]),
            Tabs::make()->columnSpanFull()->tabs([
                Tab::make('عربي')->schema([
                    TextInput::make('subject_ar')->label('عنوان الإيميل')->required(),
                    RichEditor::make('body_ar')->label('نص الرسالة')->required()
                        ->helperText('اكتب {الاسم} في أي مكان وهيتحط مكانه اسم العميل'),
                    TextInput::make('button_text_ar')->label('نص الزر')->placeholder('تسوق الحين'),
                ]),
                Tab::make('English')->schema([
                    TextInput::make('subject_en')->label('Subject')->helperText('اختياري: لو فاضي يتبعت العربي للكل'),
                    RichEditor::make('body_en')->label('Body')->helperText('Use {name} for the customer name'),
                    TextInput::make('button_text_en')->label('Button text')->placeholder('Shop now'),
                ]),
            ]),
            TextInput::make('button_url')->label('رابط الزر')->url()->placeholder(url('/products'))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('name')->label('الحملة')->description(fn (EmailCampaign $r) => $r->subject_ar)->searchable(),
            TextColumn::make('repeat_hours')->label('التكرار')->formatStateUsing(fn ($state) => $state ? 'كل '.$state.' ساعة' : 'يدوي')->placeholder('يدوي'),
            TextColumn::make('coupon.code')->label('الكود')->badge()->placeholder('—'),
            TextColumn::make('last_run_at')->label('آخر إرسال')->since()->description(fn (EmailCampaign $r) => $r->last_run_at ? $r->last_run_recipients.' مستلم' : null)->placeholder('لم ترسل بعد'),
            TextColumn::make('next')->label('الإرسال القادم')->getStateUsing(fn (EmailCampaign $r) => $r->nextRunAt()?->diffForHumans())->placeholder('—'),
            TextColumn::make('sent_total')->label('إجمالي المرسل')->badge()->color('success'),
            ToggleColumn::make('is_active')->label('مفعلة'),
        ])->recordActions([
            static::testAction(),
            static::sendNowAction(),
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function sendNowAction(): Action
    {
        return Action::make('send_now')->label('إرسال للكل الآن')->icon(Heroicon::OutlinedPaperAirplane)->color('success')
            ->requiresConfirmation()
            ->modalDescription(fn (EmailCampaign $r) => 'هتتبعت الرسالة لـ '.static::recipientsCount($r).' مستلم على دفعات صغيرة (حوالي 40 إيميل في الدقيقة) علشان السيرفر ما يتضغطش.')
            ->action(function (EmailCampaign $record) {
                $count = RunEmailCampaigns::dispatch($record);
                Notification::make()->success()->title("تمت جدولة {$count} رسالة للإرسال")->send();
            });
    }

    public static function testAction(): Action
    {
        return Action::make('test')->label('تجربة على إيميلي')->icon(Heroicon::OutlinedBeaker)->color('gray')
            ->action(function (EmailCampaign $record) {
                $admin = auth()->user();
                Mail::to($admin->email)->send(new CampaignMail($record, $admin->name, 'user', $admin->id, 'ar'));
                Notification::make()->success()->title('تم إرسال نسخة تجربة إلى '.$admin->email)->send();
            });
    }

    public static function recipientsCount(EmailCampaign $campaign): int
    {
        $users = User::where('is_admin', false)->whereNull('unsubscribed_at')->count();

        return $users + ($campaign->include_guests ? GuestCustomer::whereNull('unsubscribed_at')->count() : 0);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailCampaigns::route('/'),
            'create' => CreateEmailCampaign::route('/create'),
            'edit' => EditEmailCampaign::route('/{record}/edit'),
        ];
    }
}
