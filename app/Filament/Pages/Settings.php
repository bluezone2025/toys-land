<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'إعدادات المتجر';

    protected static ?string $title = 'إعدادات المتجر';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    /** Toggles are stored as '1' / '0' strings. */
    protected const TOGGLES = ['whatsapp_api_enabled', 'cart_reminder_enabled', 'welcome_enabled', 'pay_knet_enabled', 'pay_card_enabled', 'pay_applepay_enabled', 'pay_cod_enabled'];

    public function mount(): void
    {
        $values = Setting::allCached();
        foreach (self::TOGGLES as $key) {
            $values[$key] = ($values[$key] ?? '1') === '1';
        }
        $this->form->fill($values);
    }

    protected static function pair(string $key, string $label, bool $textarea = false): array
    {
        $make = fn ($suffix, $lang) => ($textarea ? Textarea::make("{$key}_{$suffix}")->rows(2) : TextInput::make("{$key}_{$suffix}"))->label("{$label} ({$lang})");

        return [$make('ar', 'عربي'), $make('en', 'English')->extraInputAttributes(['dir' => 'ltr'])];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('عام')->icon(Heroicon::OutlinedBuildingStorefront)->columns(2)->schema([
                    ...self::pair('store_name', 'اسم المتجر'),
                    ...self::pair('store_tagline', 'الشعار النصي'),
                    ...self::pair('announcement', 'الشريط العلوي (الإعلان)'),
                    ...self::pair('footer_about', 'نبذة الفوتر', true),
                    ...self::pair('rights', 'حقوق النشر'),
                ]),
                Tab::make('التواصل')->icon(Heroicon::OutlinedPhone)->columns(2)->schema([
                    TextInput::make('phone')->label('الخط الساخن'),
                    TextInput::make('whatsapp')->label('رقم الواتساب (مع رمز الدولة بدون +)')->placeholder('96599999999'),
                    TextInput::make('admin_email')->label('بريد استقبال إشعارات الطلبات')->email(),
                    TextInput::make('map_query')->label('موقع الخريطة (اسم المكان أو الإحداثيات)'),
                    ...self::pair('address', 'العنوان'),
                    ...self::pair('hours', 'ساعات العمل'),
                    ...self::pair('map_note', 'ملاحظة الخريطة'),
                    Section::make('روابط التواصل الاجتماعي')->columns(3)->columnSpanFull()->schema([
                        TextInput::make('social_instagram')->label('Instagram')->url(),
                        TextInput::make('social_tiktok')->label('TikTok')->url(),
                        TextInput::make('social_snapchat')->label('Snapchat')->url(),
                        TextInput::make('social_x')->label('X / Twitter')->url(),
                        TextInput::make('social_facebook')->label('Facebook')->url(),
                    ]),
                ]),
                Tab::make('الدفع والشحن')->icon(Heroicon::OutlinedCreditCard)->columns(2)->schema([
                    TextInput::make('free_shipping_threshold')->label('الشحن مجاني للطلبات فوق (د.ك)')->numeric()->minValue(0)->helperText('ضع 0 لإلغاء الشحن المجاني'),
                    TextInput::make('promo_hint_code')->label('كود خصم يظهر كاقتراح في صفحة الدفع'),
                    Grid::make(4)->columnSpanFull()->schema([
                        Toggle::make('pay_knet_enabled')->label('كي نت'),
                        Toggle::make('pay_card_enabled')->label('فيزا / ماستركارد'),
                        Toggle::make('pay_applepay_enabled')->label('Apple Pay'),
                        Toggle::make('pay_cod_enabled')->label('الدفع عند الاستلام'),
                    ]),
                    ...self::pair('invoice_policy', 'سياسة الضمان في الفاتورة', true),
                    Section::make('تذكير السلة المتروكة')->columns(2)->columnSpanFull()->schema([
                        Toggle::make('cart_reminder_enabled')->label('إرسال إيميل تذكير للعملاء المسجلين اللي تركوا منتجات في السلة'),
                        TextInput::make('cart_reminder_hours')->label('يرسل التذكير بعد (ساعة)')->numeric()->minValue(1)->maxValue(72)->default(3),
                        \Filament\Forms\Components\Select::make('promo_coupon_id')->label('كود الخصم في التذكيرات وإشعار الزوار')
                            ->options(fn () => \App\Models\Coupon::where('is_active', true)->pluck('code', 'id'))->placeholder('بدون كود')
                            ->helperText('يظهر في إيميل تذكير السلة وفي إشعار الموقع للزوار مع عدد مرات الاستخدام المتبقية'),
                        Toggle::make('whatsapp_api_enabled')->label('إرسال تذكير السلة على واتساب (WhatsApp Business API)')
                            ->helperText('يحتاج WHATSAPP_TOKEN و WHATSAPP_PHONE_ID في ملف .env وقالب رسالة معتمد من Meta'),
                    ]),
                ]),
                Tab::make('الصفحة الرئيسية')->icon(Heroicon::OutlinedHome)->columns(2)->schema([
                    ...self::pair('home_age_title', 'عنوان قسم الأعمار'),
                    ...self::pair('home_age_sub', 'وصف قسم الأعمار'),
                    ...self::pair('home_cats_title', 'عنوان قسم الأقسام'),
                    ...self::pair('home_cats_sub', 'وصف قسم الأقسام'),
                    ...self::pair('home_best_title', 'عنوان الأكثر طلباً'),
                    ...self::pair('home_best_sub', 'وصف الأكثر طلباً'),
                    ...self::pair('home_edu_title', 'عنوان سلايدر الذكاء والعلوم'),
                    ...self::pair('home_edu_sub', 'وصف سلايدر الذكاء والعلوم'),
                    ...self::pair('home_fun_title', 'عنوان سلايدر الدمى والمغامرات'),
                    ...self::pair('home_fun_sub', 'وصف سلايدر الدمى والمغامرات'),
                    ...self::pair('home_catalog_title', 'عنوان الكتالوج'),
                    ...self::pair('home_catalog_sub', 'وصف الكتالوج'),
                ]),
                Tab::make('نافذة الترحيب')->icon(Heroicon::OutlinedSparkles)->columns(2)->schema([
                    Toggle::make('welcome_enabled')->label('إظهار نافذة الترحيب للزوار')->columnSpanFull(),
                    FileUpload::make('welcome_video_upload')->label('رفع فيديو (MP4) — اختياري')->disk('public')->directory('welcome')
                        ->acceptedFileTypes(['video/mp4'])->maxSize(20480)->dehydrated(false),
                    TextInput::make('welcome_video')->label('أو رابط الفيديو المباشر (MP4)')->extraInputAttributes(['dir' => 'ltr']),
                    ...self::pair('welcome_badge', 'الشارة'),
                    ...self::pair('welcome_title', 'العنوان'),
                    ...self::pair('welcome_desc', 'الوصف', true),
                    ...self::pair('welcome_btn', 'نص الزر'),
                ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('حفظ الإعدادات')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $upload = $this->data['welcome_video_upload'] ?? null;

        foreach (self::TOGGLES as $key) {
            $state[$key] = ! empty($state[$key]) ? '1' : '0';
        }
        if ($upload) {
            $path = is_array($upload) ? reset($upload) : $upload;
            if (is_string($path)) {
                $state['welcome_video'] = $path;
            }
        }

        $old = Setting::allCached();
        // Show the welcome popup again to everyone when its content changes.
        $welcomeChanged = collect($state)->filter(fn ($v, $k) => str_starts_with($k, 'welcome_') && ($old[$k] ?? null) != $v)->isNotEmpty();
        if ($welcomeChanged) {
            $state['welcome_version'] = (string) ((int) ($old['welcome_version'] ?? 1) + 1);
        }

        Setting::put(collect($state)->except('welcome_video_upload')->map(fn ($v) => is_array($v) ? json_encode($v) : $v)->all());

        Notification::make()->success()->title('تم حفظ الإعدادات')->send();
    }
}
