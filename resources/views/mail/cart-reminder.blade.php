@php $ar = app()->getLocale() !== 'en'; @endphp
@include('mail.layout', [
    'badge' => $ar ? '🛒 سلتك تنتظرك' : '🛒 Cart waiting',
    'badgeColor' => '#7dd3fc',
    'unsubscribeUrl' => $unsubscribeUrl,
    'content' => view('mail.partials.cart-body', compact('cart', 'lines', 'coupon'))->render(),
])
