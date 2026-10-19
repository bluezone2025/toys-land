<!DOCTYPE html>
<html lang="ar" dir="rtl">
<body style="font-family: Tahoma, Arial, sans-serif; background:#f8fafc; padding:24px; color:#1e293b;">
<div style="max-width:600px; margin:auto; background:#fff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0;">
    <div style="background:#0f172a; color:#fff; padding:20px; border-bottom:4px solid #0ea5e9;">
        <h2 style="margin:0;">{{ setting('store_name_ar', 'تويز لاند') }} 🧸</h2>
        <p style="margin:6px 0 0; color:#7dd3fc;">{{ $forAdmin ? 'طلب جديد وصل للمتجر' : 'شكراً لطلبك! تم استلام طلبك بنجاح' }}</p>
    </div>
    <div style="padding:20px; font-size:14px;">
        <p><b>رقم الطلب:</b> #{{ $order->number }}<br>
           <b>الاسم:</b> {{ $order->name }}<br>
           <b>النقال:</b> {{ $order->phone }}<br>
           <b>العنوان:</b> {{ $order->city }} - قطعة {{ $order->block }} - {{ $order->apartment }}<br>
           <b>طريقة الدفع:</b> {{ $order->payment_method }} ({{ $order->payment_status }})</p>
        <table width="100%" cellpadding="8" style="border-collapse:collapse; font-size:13px;">
            <tr style="background:#f1f5f9;"><th align="right">المنتج</th><th>الكمية</th><th align="left">الإجمالي</th></tr>
            @foreach ($order->items as $item)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td>{{ $item->name_ar }}<br><small style="color:#64748b;">{{ $item->options_ar }}</small></td>
                    <td align="center">{{ $item->qty }}</td>
                    <td align="left">{{ number_format((float) $item->total, 3) }} د.ك</td>
                </tr>
            @endforeach
        </table>
        <p style="text-align:left; font-size:16px;"><b>الإجمالي: {{ number_format((float) $order->total, 3) }} د.ك</b></p>
        @if ($forAdmin)
            <p><a href="{{ url('/admin/orders/'.$order->id) }}" style="background:#0ea5e9; color:#fff; padding:10px 18px; border-radius:10px; text-decoration:none;">عرض الطلب في لوحة التحكم</a></p>
        @else
            <p><a href="{{ route('tracking', ['order' => $order->number, 'phone' => $order->phone]) }}" style="background:#0ea5e9; color:#fff; padding:10px 18px; border-radius:10px; text-decoration:none;">تتبع طلبك</a></p>
        @endif
    </div>
</div>
</body>
</html>
