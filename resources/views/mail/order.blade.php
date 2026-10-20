@php $ar = app()->getLocale() !== 'en'; @endphp
@include('mail.layout', [
    'badge' => $order->payment_status === 'paid' ? ($ar ? 'مدفوع ✓' : 'PAID ✓') : ($ar ? 'تم استلام الطلب' : 'ORDER RECEIVED'),
    'content' => view('mail.partials.order-body', ['order' => $order, 'invoiceUrl' => $invoiceUrl, 'trackUrl' => $trackUrl])->render(),
])
