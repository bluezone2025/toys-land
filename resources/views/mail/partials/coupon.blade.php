@if ($coupon)
    @php $ar = app()->getLocale() !== 'en'; @endphp
    <table role="presentation" width="100%" style="margin:20px 0; background:#fff7ed; border:2px dashed #f59e0b; border-radius:16px;">
        <tr><td align="center" style="padding:18px;">
            <div style="font-size:13px; color:#92400e; font-weight:bold;">🎁 {{ $ar ? 'هدية خاصة لك' : 'A special gift for you' }} — {{ $coupon->discountText() }}</div>
            <div style="font-size:26px; font-weight:bold; letter-spacing:3px; color:#0f172a; margin:8px 0; font-family:monospace;">{{ $coupon->code }}</div>
            @if ($coupon->urgencyText())
                <div style="font-size:13px; color:#dc2626; font-weight:bold;">⏳ {{ $coupon->urgencyText() }}</div>
            @endif
            @if ((float) $coupon->min_subtotal > 0)
                <div style="font-size:11px; color:#78716c; margin-top:4px;">{{ $ar ? 'للطلبات من '.(float) $coupon->min_subtotal.' د.ك' : 'On orders from '.(float) $coupon->min_subtotal.' KD' }}</div>
            @endif
        </td></tr>
    </table>
@endif
