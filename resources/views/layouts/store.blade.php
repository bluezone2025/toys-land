@php
    $rtl = is_rtl();
    $country = \App\Support\Shop::country();
    $brand = setting_tr('store_name', __('store.brandName'));
    $whatsapp = preg_replace('/\D/', '', (string) setting('whatsapp', '96599999999'));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - ' : '' }}{{ $brand }}</title>
    <meta name="description" content="{{ $metaDescription ?? setting_tr('footer_about', __('store.footerAbout')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.storeRoutes = { favorite: @json(route('favorites.toggle')), cartAdd: @json(route('cart.add')) };
    </script>
    @stack('head')
</head>
<body class="antialiased min-h-screen flex flex-col justify-between"
      data-flash="{{ session('toast') }}" data-welcome-version="{{ setting('welcome_version', '1') }}">

    @if (setting('welcome_enabled', '1') === '1' && ($showWelcome ?? true))
        <!-- بوب أب فيديو ترحيبي -->
        <div id="welcome-modal" class="hidden no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-lg w-full border-4 border-yellow-300 relative">
                <button onclick="closeWelcomeModal()" class="absolute top-4 left-4 z-20 w-9 h-9 bg-slate-900/60 hover:bg-slate-900 text-white rounded-full flex items-center justify-center font-black transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
                <div class="relative bg-slate-900 h-60">
                    @php $welcomeVideo = \App\Models\Setting::get('welcome_video'); @endphp
                    @if ($welcomeVideo)
                        <video autoplay muted loop playsinline class="w-full h-full object-cover">
                            <source src="{{ \App\Models\Concerns\HasImage::imageUrl($welcomeVideo) }}" type="video/mp4">
                        </video>
                    @else
                        <img src="https://images.unsplash.com/photo-1587654780291-39c9404d746b?auto=format&fit=crop&w=900&q=80" class="w-full h-full object-cover" alt="">
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent flex items-end p-5">
                        <div>
                            <span class="bg-yellow-400 text-slate-950 text-[11px] font-black px-2.5 py-0.5 rounded-full">{{ setting_tr('welcome_badge', __('store.welcomeBadge')) }}</span>
                            <h2 class="text-xl sm:text-2xl font-black text-white mt-1">{{ setting_tr('welcome_title', __('store.welcomeTitle')) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="p-5 text-center bg-sky-50/50">
                    <p class="text-slate-600 text-xs sm:text-sm font-medium mb-4">{{ setting_tr('welcome_desc', __('store.welcomeDesc')) }}</p>
                    <button onclick="closeWelcomeModal()" class="w-full bg-sky-500 hover:bg-sky-600 text-white py-3 rounded-xl font-black text-sm shadow-md transition cursor-pointer">{{ setting_tr('welcome_btn', __('store.welcomeBtn')) }}</button>
                </div>
            </div>
        </div>
    @endif

    <div id="shared-header" class="w-full">
        @include('partials.header')
    </div>

    <main class="flex-grow w-full">
        @yield('content')
    </main>

    <div id="shared-footer" class="w-full">
        @include('partials.footer')
    </div>

    @php
        $reminderItems = ! in_array(request()->route()?->getName(), ['cart', 'checkout', 'invoice.show'], true) && session('cart_restored')
            ? \App\Support\Cart::items() : collect();
    @endphp
    @if ($reminderItems->isNotEmpty())
        <!-- تذكير السلة للزائر الراجع -->
        <div id="cart-reminder" class="no-print slide-up fixed bottom-24 {{ $rtl ? 'left-4 sm:left-6' : 'right-4 sm:right-6' }} z-50 w-[calc(100%-2rem)] max-w-sm bg-white rounded-3xl shadow-2xl border border-sky-100 overflow-hidden">
            <div class="bg-gradient-to-r from-sky-500 via-pink-500 to-amber-400 px-4 py-2.5 flex items-center justify-between text-white">
                <span class="font-black text-sm flex items-center gap-2"><i class="fa-solid fa-bag-shopping wiggle"></i> {{ tt('لا تنسى سلتك! 🧸', "Don't forget your cart! 🧸") }}</span>
                <button onclick="dismissCartReminder()" class="w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-xs"></i></button>
            </div>
            <div class="p-4">
                <p class="text-xs text-slate-600 mb-3">{{ tt('لسه عندك '.$reminderItems->sum('qty').' قطعة بالسلة تنتظرك، كمّل طلبك قبل ما تخلص الكمية.', 'You still have '.$reminderItems->sum('qty').' item(s) waiting in your cart. Complete your order before they sell out.') }}</p>
                <div class="flex items-center gap-2 mb-4">
                    @foreach ($reminderItems->take(4) as $item)
                        <img src="{{ $item->product->image_url }}" class="w-12 h-12 rounded-xl object-cover border border-slate-100" alt="">
                    @endforeach
                    @if ($reminderItems->count() > 4)
                        <span class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 text-xs font-black flex items-center justify-center">+{{ $reminderItems->count() - 4 }}</span>
                    @endif
                </div>
                @include('partials.promo-coupon', ['class' => 'mb-4'])
                <div class="flex gap-2">
                    <a href="{{ route('checkout') }}" class="flex-1 text-center bg-sky-500 hover:bg-sky-600 text-white py-2.5 rounded-xl text-xs font-black transition">{{ tt('كمّل الطلب', 'Checkout') }}</a>
                    <a href="{{ route('cart') }}" class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-xl text-xs font-bold transition">{{ tt('شوف السلة', 'View cart') }}</a>
                </div>
            </div>
        </div>
    @endif

    @stack('scripts')
</body>
</html>
