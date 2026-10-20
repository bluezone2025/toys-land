@php
    $ar = app()->getLocale() !== 'en';
    $brand = setting_tr('store_name', __('store.brandName'));
@endphp
<!DOCTYPE html>
<html lang="{{ $ar ? 'ar' : 'en' }}" dir="{{ $ar ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? $brand }}</title>
</head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family:Tahoma, 'Segoe UI', Arial, sans-serif; color:#1e293b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:20px; overflow:hidden; border:1px solid #e2e8f0;">
            <tr>
                <td style="background:#0f172a; padding:24px 28px; border-bottom:4px solid #0ea5e9;">
                    <table role="presentation" width="100%"><tr>
                        <td style="color:#ffffff; font-size:22px; font-weight:bold;">{{ $brand }} 🧸</td>
                        @isset($badge)
                            <td align="{{ $ar ? 'left' : 'right' }}"><span style="background:{{ $badgeColor ?? '#34d399' }}; color:#0f172a; font-size:12px; font-weight:bold; padding:5px 12px; border-radius:20px;">{{ $badge }}</span></td>
                        @endisset
                    </tr></table>
                    <div style="color:#7dd3fc; font-size:13px; margin-top:4px;">{{ setting_tr('store_tagline', __('store.brandSub')) }}</div>
                </td>
            </tr>
            <tr><td style="padding:28px; font-size:14px; line-height:1.8; text-align:{{ $ar ? 'right' : 'left' }};">
                {!! $content !!}
            </td></tr>
            <tr>
                <td style="background:#f8fafc; padding:18px 28px; font-size:12px; color:#64748b; text-align:center; border-top:1px solid #e2e8f0;">
                    {{ setting('phone') }} @if (setting('whatsapp')) · WhatsApp: +{{ preg_replace('/\D/', '', setting('whatsapp')) }} @endif<br>
                    <a href="{{ url('/') }}" style="color:#0ea5e9; text-decoration:none;">{{ preg_replace('#^https?://#', '', config('app.url')) }}</a>
                    @isset($unsubscribeUrl)
                        <br><a href="{{ $unsubscribeUrl }}" style="color:#94a3b8;">{{ $ar ? 'إلغاء الاشتراك في الرسائل الترويجية' : 'Unsubscribe from promotional emails' }}</a>
                    @endisset
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html>
