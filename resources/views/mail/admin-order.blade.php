@include('mail.layout', [
    'badge' => '🔔 طلب جديد',
    'badgeColor' => '#fbbf24',
    'content' => view('mail.partials.admin-order-body', ['order' => $order, 'adminUrl' => $adminUrl, 'invoiceUrl' => $invoiceUrl])->render(),
])
