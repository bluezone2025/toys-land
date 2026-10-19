<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\ManageCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'كود خصم';

    protected static ?string $pluralModelLabel = 'أكواد الخصم';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('code')->label('الكود')->required()->unique(ignoreRecord: true)->maxLength(50)
                ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state)))->extraInputAttributes(['dir' => 'ltr', 'style' => 'text-transform:uppercase']),
            Select::make('type')->label('نوع الخصم')->options(['percent' => 'نسبة مئوية %', 'fixed' => 'مبلغ ثابت (د.ك)'])->default('percent')->required()->live(),
            TextInput::make('value')->label('قيمة الخصم')->numeric()->minValue(0)->required()
                ->suffix(fn ($get) => $get('type') === 'fixed' ? 'د.ك' : '%'),
            TextInput::make('min_subtotal')->label('أقل مبلغ للطلب (د.ك)')->numeric()->minValue(0)->default(0),
            TextInput::make('max_uses')->label('عدد مرات الاستخدام المسموحة')->integer()->minValue(1)->placeholder('غير محدود'),
            TextInput::make('used_count')->label('استخدم')->disabled()->dehydrated(false),
            DateTimePicker::make('starts_at')->label('يبدأ من'),
            DateTimePicker::make('expires_at')->label('ينتهي في'),
            Toggle::make('is_active')->label('مفعل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('code')->label('الكود')->weight('bold')->copyable(),
            TextColumn::make('value')->label('الخصم')->formatStateUsing(fn ($state, Coupon $r) => $r->type === 'fixed' ? (float) $state.' د.ك' : (float) $state.'%'),
            TextColumn::make('used_count')->label('الاستخدام')->formatStateUsing(fn ($state, Coupon $r) => $state.($r->max_uses ? ' / '.$r->max_uses : '')),
            TextColumn::make('expires_at')->label('ينتهي')->date('Y/m/d')->placeholder('بدون انتهاء'),
            ToggleColumn::make('is_active')->label('مفعل'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCoupons::route('/')];
    }
}
