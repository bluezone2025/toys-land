@include('mail.layout', [
    'unsubscribeUrl' => $unsubscribeUrl,
    'content' => view('mail.partials.campaign-body', compact('campaign', 'name', 'coupon'))->render(),
])
