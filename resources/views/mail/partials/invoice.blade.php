@php
    $ar = app()->getLocale() !== 'en';
    $align = $ar ? 'right' : 'left';
    $end = $ar ? 'left' : 'right';
    $kd = fn ($v) => number_format((float) $v, 3).($ar ? ' د.ك' : ' KD');
    $methods = ['knet' => 'KNET', 'card' => 'Visa / MasterCard', 'applepay' => 'Apple Pay', 'cod' => $ar ? 'الدفع عند الاستلام' : 'Cash on Delivery'];
@endphp
<table role="presentation" width="100%" style="background:#f8fafc; border-radius:14px; margin:16px 0; font-size:13px;">
    <tr><td style="padding:14px 16px;">
        <b>{{ $ar ? 'رقم الطلب' : 'Order No.' }}:</b> #{{ $order->number }}<br>
        <b>{{ $ar ? 'التاريخ' : 'Date' }}:</b> {{ $order->created_at->format('Y/m/d h:i A') }}<br>
        <b>{{ $ar ? 'الاسم' : 'Name' }}:</b> {{ $order->name }} — <span dir="ltr">{{ $order->phone }}</span><br>
        <b>{{ $ar ? 'العنوان' : 'Address' }}:</b> {{ $order->country?->tr('name') }} — {{ $order->addressLine() }}<br>
        <b>{{ $ar ? 'طريقة الدفع' : 'Payment' }}:</b> {{ $methods[$order->payment_method] ?? $order->payment_method }}
        @if ($order->payment_status === 'paid') <span style="color:#059669;">({{ $ar ? 'مدفوع' : 'Paid' }} ✓)</span>@endif
        @if ($order->notes)<br><b>{{ $ar ? 'ملاحظات' : 'Notes' }}:</b> {{ $order->notes }}@endif
    </td></tr>
</table>
<table role="presentation" width="100%" cellpadding="8" style="border-collapse:collapse; font-size:13px;">
    <tr style="background:#0f172a; color:#ffffff;">
        <th align="{{ $align }}" style="border-radius:{{ $ar ? '0 10px 0 0' : '10px 0 0 0' }};">{{ $ar ? 'المنتج' : 'Item' }}</th>
        <th align="center">{{ $ar ? 'الكمية' : 'Qty' }}</th>
        <th align="{{ $end }}" style="border-radius:{{ $ar ? '10px 0 0 0' : '0 10px 0 0' }};">{{ $ar ? 'الإجمالي' : 'Total' }}</th>
    </tr>
    @foreach ($order->items as $item)
        <tr style="border-bottom:1px solid #e2e8f0;">
            <td align="{{ $align }}"><b>{{ $item->tr('name') }}</b>@if ($item->tr('options'))<br><span style="color:#64748b; font-size:11px;">{{ $item->tr('options') }}</span>@endif</td>
            <td align="center">{{ $item->qty }}</td>
            <td align="{{ $end }}" style="white-space:nowrap;">{{ $kd($item->total) }}</td>
        </tr>
    @endforeach
    <tr><td colspan="2" align="{{ $align }}" style="color:#64748b;">{{ $ar ? 'المجموع الفرعي' : 'Subtotal' }}</td><td align="{{ $end }}">{{ $kd($order->subtotal) }}</td></tr>
    @if ($order->discount > 0)
        <tr><td colspan="2" align="{{ $align }}" style="color:#059669;">{{ $ar ? 'الخصم' : 'Discount' }} ({{ $order->coupon_code }})</td><td align="{{ $end }}" style="color:#059669;">- {{ $kd($order->discount) }}</td></tr>
    @endif
    <tr><td colspan="2" align="{{ $align }}" style="color:#64748b;">{{ $ar ? 'التوصيل' : 'Delivery' }}</td><td align="{{ $end }}">{{ $order->shipping > 0 ? $kd($order->shipping) : ($ar ? 'مجاناً' : 'FREE') }}</td></tr>
    <tr style="background:#f0f9ff;"><td colspan="2" align="{{ $align }}" style="font-size:15px;"><b>{{ $ar ? 'الإجمالي النهائي' : 'Grand Total' }}</b></td><td align="{{ $end }}" style="font-size:16px; color:#db2777; white-space:nowrap;"><b>{{ $kd($order->total) }}</b></td></tr>
</table>
