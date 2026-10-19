<!DOCTYPE html>
<html lang="ar" dir="rtl">
<body style="font-family: Tahoma, Arial, sans-serif; background:#f8fafc; padding:24px; color:#1e293b;">
<div style="max-width:600px; margin:auto; background:#fff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0;">
    <div style="background:linear-gradient(90deg,#0ea5e9,#ec4899,#fbbf24); color:#fff; padding:22px;">
        <h2 style="margin:0;">{{ setting('store_name_ar', 'تويز لاند') }} 🧸</h2>
        <p style="margin:6px 0 0;">هلا {{ $cart->user->name }}! ألعابك لسه تنتظرك في السلة 🛒</p>
    </div>
    <div style="padding:20px; font-size:14px;">
        <p>لاحظنا إنك ضفت ألعاب حلوة لسلتك وما كملت الطلب. حجزناها لك، بس الكميات محدودة 😉</p>
        <table width="100%" cellpadding="8" style="border-collapse:collapse;">
            @foreach ($lines as $line)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td width="64"><img src="{{ $line['product']->image_url }}" width="56" height="56" style="border-radius:10px; object-fit:cover;" alt=""></td>
                    <td><b>{{ $line['product']->name_ar }}</b><br><small style="color:#64748b;">الكمية: {{ $line['qty'] }}</small></td>
                </tr>
            @endforeach
        </table>
        @if (setting('promo_hint_code'))
            <p style="background:#f0f9ff; border:1px dashed #0ea5e9; border-radius:10px; padding:10px; text-align:center;">استخدم كود الخصم <b>{{ setting('promo_hint_code') }}</b> عند الدفع 🎁</p>
        @endif
        <p style="text-align:center; margin-top:20px;">
            <a href="{{ route('cart') }}" style="background:#0ea5e9; color:#fff; padding:12px 26px; border-radius:12px; text-decoration:none; font-weight:bold;">كمّل طلبك الحين</a>
        </p>
    </div>
</div>
</body>
</html>
