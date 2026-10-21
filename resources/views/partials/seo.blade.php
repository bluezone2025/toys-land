@php
    use App\Support\Seo;
    $seoTitle = Seo::title($title ?? null);
    $seoDescription = Seo::description($metaDescription ?? null);
    $seoImage = Seo::image($metaImage ?? null);
    $canonical = Seo::canonical();
    $noindex = ($noindex ?? false) || request()->filled('search');
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large' }}">
@unless ($noindex)
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" hreflang="ar" href="{{ Seo::url('ar') }}">
    <link rel="alternate" hreflang="en" href="{{ Seo::url('en') }}">
    <link rel="alternate" hreflang="x-default" href="{{ Seo::url('ar') }}">
@endunless
<meta name="theme-color" content="#0ea5e9">
<link rel="icon" href="{{ setting('favicon') ? \App\Models\Concerns\HasImage::imageUrl(setting('favicon')) : asset('favicon.svg') }}">
<link rel="apple-touch-icon" href="{{ setting('favicon') ? \App\Models\Concerns\HasImage::imageUrl(setting('favicon')) : asset('favicon.svg') }}">

<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:site_name" content="{{ Seo::brand() }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'ar_KW' }}">
<meta property="og:locale:alternate" content="{{ app()->getLocale() === 'en' ? 'ar_KW' : 'en_US' }}">
@if ($seoImage)
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">

@if (setting('google_site_verification'))
    <meta name="google-site-verification" content="{{ setting('google_site_verification') }}">
@endif
@if (setting('bing_site_verification'))
    <meta name="msvalidate.01" content="{{ setting('bing_site_verification') }}">
@endif

@foreach (array_filter($schema ?? []) as $block)
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" type="application/ld+json">{!! Seo::json($block) !!}</script>
@endforeach

@if ($ga = setting('ga4_id'))
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($ga) }}"></script>
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@json($ga));</script>
@endif
@if ($pixel = setting('facebook_pixel_id'))
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',@json($pixel));fbq('track','PageView');</script>
@endif
