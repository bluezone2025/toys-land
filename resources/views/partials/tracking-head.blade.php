{{--
    Hybrid tracking bootstrap. Order matters: the dataLayer and the Consent Mode v2 defaults
    exist before Google Tag Manager or any pixel loads. Pixels themselves are loaded by
    toysland-tracker (resources/js/tracker.js) after the page has rendered.
--}}
@php
    $tracking = \App\Support\Tracking::scriptConfig();
    $nonce = \Illuminate\Support\Facades\Vite::cspNonce();
    $gtm = $tracking['pixels']['gtm'] ?? null;
@endphp
<script nonce="{{ $nonce }}">
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    (function () {
        var cfg = @json($tracking['consent']);
        var saved = (document.cookie.match(new RegExp('(?:^|; )' + cfg.cookie + '=([^;]*)')) || [])[1];
        var state = !cfg.banner || saved === 'granted' || (!saved && cfg['default'] !== 'denied') ? 'granted' : 'denied';
        gtag('consent', 'default', {
            ad_storage: state, analytics_storage: state, ad_user_data: state, ad_personalization: state,
            functionality_storage: 'granted', security_storage: 'granted', wait_for_update: 500
        });
        gtag('set', 'ads_data_redaction', state === 'denied');
        gtag('set', 'url_passthrough', true);
    })();
</script>
<script type="application/json" id="tl-config">@json($tracking)</script>
@if ($gtm)
    <script nonce="{{ $nonce }}">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;var n=d.querySelector('[nonce]');n&&j.setAttribute('nonce',n.nonce||n.getAttribute('nonce'));f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json($gtm));</script>
@endif
