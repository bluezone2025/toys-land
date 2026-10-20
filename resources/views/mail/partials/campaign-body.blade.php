@php $ar = app()->getLocale() !== 'en'; $tr = fn ($f) => $ar ? $campaign->{$f.'_ar'} : ($campaign->{$f.'_en'} ?: $campaign->{$f.'_ar'}); @endphp
<p style="margin:0 0 10px; font-size:16px;"><b>{{ $ar ? 'هلا '.$name.' 👋' : 'Hi '.$name.' 👋' }}</b></p>
<div style="color:#334155;">{!! str_replace(['{name}', '{الاسم}'], e($name), $tr('body')) !!}</div>
@include('mail.partials.coupon', ['coupon' => $coupon])
@if ($tr('button_text'))
    @include('mail.partials.button', ['url' => $campaign->button_url ?: url('/'), 'text' => $tr('button_text')])
@endif
