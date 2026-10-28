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
    protected const TOGGLES = ['whatsapp_api_enabled', 'cart_reminder_enabled', 'welcome_enabled', 'pay_knet_enabled', 'pay_card_enabled', 'pay_applepay_enabled', 'pay_cod_enabled',
        'server_tracking_enabled', 'consent_banner', 'checkout_recovery_enabled'];

    /** Toggles that start switched off when never saved. */
    protected const OFF_BY_DEFAULT = ['checkout_recovery_enabled'];

    public function mount(): void
    {
        $values = Setting::allCached();
        foreach (self::TOGGLES as $key) {
            $values[$key] = ($values[$key] ?? (in_array($key, self::OFF_BY_DEFAULT, true) ? '0' : '1')) === '1';
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
                        TextInput::make('social_youtube')->label('YouTube')->url(),
                        TextInput::make('social_linkedin')->label('LinkedIn')->url(),
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
                        Toggle::make('whatsapp_api_enabled')->label('إرسال تذكير السلة على واتساب')
                            ->helperText('تتطلب تفعيل خدمة واتساب للأعمال على حساب المتجر. تواصل مع الدعم الفني لتفعيلها.'),
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
                Tab::make('SEO والتتبع')->icon(Heroicon::OutlinedMagnifyingGlass)->columns(2)->schema([
                    ...self::pair('seo_home_title', 'عنوان الصفحة الرئيسية في Google'),
                    ...self::pair('seo_home_description', 'وصف الموقع في Google', true),
                    ...self::pair('home_intro_title', 'عنوان الفقرة التعريفية أسفل الصفحة الرئيسية'),
                    ...self::pair('home_intro', 'نص الفقرة التعريفية (يفيد في ظهور الموقع في Google)', true),
                    FileUpload::make('og_image')->label('صورة المشاركة الافتراضية (واتساب / فيسبوك)')->image()->disk('public')->directory('seo')
                        ->helperText(\App\Filament\Support\Fields::sizeHint('1200 × 630 بكسل', 'JPG أو PNG — تظهر عند مشاركة رابط الموقع')),
                    FileUpload::make('favicon')->label('أيقونة الموقع (Favicon)')->image()->disk('public')->directory('seo')
                        ->helperText(\App\Filament\Support\Fields::sizeHint('512 × 512 بكسل (مربعة)', 'PNG بخلفية شفافة')),
                    TextInput::make('google_site_verification')->label('كود التحقق من Google Search Console')->placeholder('الكود فقط بدون <meta>'),
                    TextInput::make('bing_site_verification')->label('كود التحقق من Bing Webmaster'),
                ]),
                Tab::make('التتبع والبيكسلات')->icon(Heroicon::OutlinedSignal)->columns(2)->schema(self::trackingFields()),
                Tab::make('نافذة الترحيب')->icon(Heroicon::OutlinedSparkles)->columns(2)->schema([
                    Toggle::make('welcome_enabled')->label('إظهار نافذة الترحيب للزوار')->columnSpanFull(),
                    FileUpload::make('welcome_video_upload')->label('رفع فيديو (MP4) — اختياري')->disk('public')->directory('welcome')
                        ->acceptedFileTypes(['video/mp4'])->maxSize(20480)->dehydrated(false)
                        ->helperText('🎬 المقترح: فيديو MP4 أفقي 1280 × 720، مدته من 10 إلى 20 ثانية، وحجمه أقل من 8MB حتى يعمل بسرعة على الجوال. (استرشادي فقط، الحد الأقصى 20MB)'),
                    TextInput::make('welcome_video')->label('أو رابط الفيديو المباشر (MP4)')->extraInputAttributes(['dir' => 'ltr']),
                    ...self::pair('welcome_badge', 'الشارة'),
                    ...self::pair('welcome_title', 'العنوان'),
                    ...self::pair('welcome_desc', 'الوصف', true),
                    ...self::pair('welcome_btn', 'نص الزر'),
                ]),
            ]),
        ]);
    }

    protected static function trackingFields(): array
    {
        $secret = fn (string $key, string $label) => TextInput::make($key)->label($label)->password()->revealable()->extraInputAttributes(['dir' => 'ltr', 'autocomplete' => 'off']);
        $id = fn (string $key, string $label, ?string $placeholder = null) => TextInput::make($key)->label($label)->placeholder($placeholder)->extraInputAttributes(['dir' => 'ltr']);

        return [
            Section::make('طريقة التحميل')->columns(2)->columnSpanFull()
                ->description('كل حدث (مشاهدة، سلة، دفع، شراء) ينرسل من المتصفح ومن السيرفر بنفس رقم الحدث event_id، فالمنصات تحسبه مرة وحدة وما يضيع حتى لو العميل سكّر المتصفح قبل صفحة الشكر.')
                ->schema([
                    $id('gtm_id', 'Google Tag Manager Container ID', 'GTM-XXXXXXX'),
                    \Filament\Forms\Components\Select::make('pixels_via')->label('مين يحمّل البيكسلات في المتصفح؟')->default('direct')->selectablePlaceholder(false)
                        ->options(['direct' => 'المتجر مباشرة (أسهل — بدون إعداد)', 'gtm' => 'Google Tag Manager فقط (للمتقدمين)'])
                        ->helperText('في وضع GTM يتعبّى dataLayer بأحداث GA4 القياسية وأنت تضبط الوسوم داخل GTM. لا تفعّل نفس البيكسل في المكانين.'),
                    Toggle::make('server_tracking_enabled')->label('تفعيل الإرسال من السيرفر (Conversions API)')->columnSpanFull()
                        ->helperText('ينرسل كل دقيقة عبر مهمة الجدولة (Cron) بدون ما يبطّئ الموقع.'),
                ]),
            Section::make('Meta (فيسبوك وإنستغرام)')->columns(3)->columnSpanFull()->schema([
                $id('facebook_pixel_id', 'Pixel ID'),
                $secret('meta_capi_token', 'Conversions API Access Token'),
                $id('meta_test_event_code', 'Test Event Code (للاختبار فقط)', 'TEST12345'),
            ]),
            Section::make('TikTok')->columns(3)->columnSpanFull()->schema([
                $id('tiktok_pixel_id', 'Pixel ID'),
                $secret('tiktok_access_token', 'Events API Access Token'),
                $id('tiktok_test_event_code', 'Test Event Code (للاختبار فقط)'),
            ]),
            Section::make('Snapchat')->columns(2)->columnSpanFull()->schema([
                $id('snap_pixel_id', 'Pixel ID'),
                $secret('snap_capi_token', 'Conversions API Token'),
            ]),
            Section::make('Google Analytics 4')->columns(2)->columnSpanFull()->schema([
                $id('ga4_id', 'Measurement ID', 'G-XXXXXXXXXX'),
                $secret('ga4_api_secret', 'Measurement Protocol API Secret')->helperText('من GA4: الإدارة ← مصادر البيانات ← Measurement Protocol API secrets. يستخدم لإرسال المشتريات من السيرفر.'),
            ]),
            Section::make('الموافقة على الكوكيز (Google Consent Mode v2)')->columns(2)->columnSpanFull()->schema([
                Toggle::make('consent_banner')->label('إظهار شريط الموافقة على الكوكيز'),
                \Filament\Forms\Components\Select::make('consent_default')->label('قبل ما يختار الزائر')->default('granted')->selectablePlaceholder(false)
                    ->options(['granted' => 'مسموح (يقدر يرفض من الشريط) — الأنسب للخليج', 'denied' => 'ممنوع حتى يوافق (مثل أوروبا)']),
            ]),
            Section::make('MyFatoorah Webhook')->columnSpanFull()->schema([
                \Filament\Forms\Components\Placeholder::make('webhook_url')->label('ضع هذا الرابط في MyFatoorah ← Integration Settings ← Webhook (حدث Transaction Status Changed)')
                    ->content(fn () => route('payment.webhook')),
            ]),
            Section::make('استرجاع الدفعات المتروكة تلقائياً على واتساب')->columns(2)->columnSpanFull()->schema([
                Toggle::make('checkout_recovery_enabled')->label('إرسال رسالة واتساب تلقائية لمن ترك صفحة الدفع')
                    ->helperText('يتطلب WhatsApp Business API وقالب رسالة معتمد (WHATSAPP_CHECKOUT_TEMPLATE) فيه: {{1}} الاسم، {{2}} رابط إكمال الطلب، {{3}} كود الخصم.'),
                TextInput::make('checkout_recovery_minutes')->label('ترسل بعد (دقيقة)')->numeric()->minValue(10)->maxValue(1440)->default(30),
            ]),
        ];
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

        // File uploads come back as [uuid => path]; store just the path.
        Setting::put(collect($state)->except('welcome_video_upload')->map(fn ($v) => is_array($v) ? (reset($v) ?: null) : $v)->all());
        \Illuminate\Support\Facades\Cache::forget('sitemap.xml');

        Notification::make()->success()->title('تم حفظ الإعدادات')->send();
    }
}
