@php $ar = app()->getLocale() !== 'en'; $name = fn ($p) => $ar ? $p->name_ar : ($p->name_en ?: $p->name_ar); @endphp
<h2 style="margin:0 0 6px; font-size:20px;">{{ $ar ? 'هلا '.$cart->user->name.'! نسيت شي في سلتك؟ 🧸' : 'Hi '.$cart->user->name.'! Did you forget something? 🧸' }}</h2>
<p style="margin:0 0 14px; color:#475569;">
    {{ $ar ? 'حجزنا لك الألعاب اللي اخترتها، بس الكميات محدودة وممكن تخلص. كمّل طلبك الحين وخلّها توصل لعيالك بأسرع وقت.' : 'We saved the toys you picked, but stock is limited. Complete your order now and we will deliver it fast.' }}
</p>
<table role="presentation" width="100%" cellpadding="8" style="border-collapse:collapse;">
    @foreach ($lines as $line)
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td width="72"><img src="{{ $line['product']->image_url }}" width="64" height="64" style="border-radius:12px; object-fit:cover; display:block;" alt=""></td>
            <td><b>{{ $name($line['product']) }}</b><br><span style="color:#64748b; font-size:12px;">{{ $ar ? 'الكمية' : 'Qty' }}: {{ $line['qty'] }}</span></td>
            <td align="{{ $ar ? 'left' : 'right' }}" style="color:#db2777; font-weight:bold; white-space:nowrap;">{{ number_format((float) $line['product']->price * $line['qty'], 3) }} {{ $ar ? 'د.ك' : 'KD' }}</td>
        </tr>
    @endforeach
</table>
@include('mail.partials.coupon', ['coupon' => $coupon])
@include('mail.partials.button', ['url' => route('checkout'), 'text' => $ar ? 'كمّل طلبك الحين ←' : 'Complete my order →'])
