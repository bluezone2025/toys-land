<h2 style="margin:0 0 6px; font-size:20px;">🔔 طلب جديد #{{ $order->number }} بقيمة {{ number_format((float) $order->total, 3) }} د.ك</h2>
<p style="margin:0 0 12px; color:#475569;">وصل طلب جديد للمتجر ويحتاج تجهيز وتوصيل للعميل. {{ $order->user_id ? 'العميل مسجل بحساب.' : 'العميل زائر (بدون حساب).' }}</p>
@include('mail.partials.invoice', ['order' => $order])
@include('mail.partials.button', ['url' => $adminUrl, 'text' => 'فتح الطلب في لوحة التحكم', 'color' => '#0f172a'])
<p style="text-align:center; margin:-8px 0 0;">
    <a href="{{ $invoiceUrl }}" style="color:#0ea5e9; font-weight:bold;">🧾 طباعة الفاتورة</a>
    &nbsp;·&nbsp;
    <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->phone) }}" style="color:#059669; font-weight:bold;">💬 واتساب العميل</a>
</p>
