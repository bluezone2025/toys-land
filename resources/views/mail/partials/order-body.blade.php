@php $ar = app()->getLocale() !== 'en'; @endphp
<h2 style="margin:0 0 6px; font-size:20px;">{{ $ar ? 'شكراً لطلبك يا '.$order->name.'! 🎉' : 'Thank you for your order, '.$order->name.'! 🎉' }}</h2>
<p style="margin:0 0 12px; color:#475569;">
    {{ $ar ? 'استلمنا طلبك بنجاح وقاعدين نجهز الألعاب ونغلفها بكل حب. هذي فاتورتك، وتقدر تتابع حالة الطلب أول بأول.' : 'We have received your order and our team is preparing and wrapping your toys with care. Here is your invoice — you can track your order any time.' }}
</p>
@include('mail.partials.invoice', ['order' => $order])
@include('mail.partials.button', ['url' => $invoiceUrl, 'text' => $ar ? '🧾 عرض وتحميل الفاتورة' : '🧾 View & download invoice'])
<p style="text-align:center; margin:-8px 0 0;"><a href="{{ $trackUrl }}" style="color:#0ea5e9; font-weight:bold;">{{ $ar ? '🛵 تتبع طلبك' : '🛵 Track your order' }}</a></p>
<p style="color:#64748b; font-size:12px; margin-top:20px;">{{ setting_tr('invoice_policy', $ar ? 'تعتبر هذه الفاتورة سند ضمان رسمي لمدة 14 يوماً للاستبدال أو الاسترجاع.' : 'This invoice serves as an official 14-day warranty and return receipt.') }}</p>
