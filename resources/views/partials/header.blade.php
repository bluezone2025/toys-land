@php
    $user = auth()->user();
    $nav = fn ($name) => ($active ?? '') === $name ? 'text-sky-600 font-black' : 'text-slate-600 hover:text-sky-600';
@endphp
<div class="w-full bg-gradient-to-r from-sky-500 via-pink-500 to-amber-400 text-white text-xs sm:text-sm font-bold py-2.5 px-4 sm:px-10 text-center shadow-inner flex justify-between items-center">
    <span class="mx-auto md:mx-0 truncate">{{ setting_tr('announcement', __('store.freeShipping')) }}</span>
    <div class="hidden md:flex items-center gap-3 shrink-0">
        <form method="POST" action="{{ route('country.switch') }}">
            @csrf
            <select name="country" aria-label="{{ __('store.countryCurrency') }}" onchange="this.form.submit()" class="bg-white/20 text-white border border-white/40 rounded-lg px-2.5 py-1 text-xs font-bold focus:outline-none cursor-pointer">
                @foreach ($countries as $c)
                    <option value="{{ $c->code }}" @selected($country->code === $c->code) class="text-slate-800">{{ $c->tr('name') }} ({{ $c->tr('symbol') }})</option>
                @endforeach
            </select>
        </form>
        <a href="{{ \App\Support\Seo::switchUrl($rtl ? 'en' : 'ar') }}" hreflang="{{ $rtl ? 'en' : 'ar' }}" class="bg-white/20 hover:bg-white/30 text-white border border-white/40 rounded-lg px-3 py-1 text-xs font-bold transition cursor-pointer">
            {{ $rtl ? 'English' : 'العربية' }}
        </a>
    </div>
</div>

<header class="w-full bg-white shadow-sm sticky top-0 z-40 border-b border-gray-100">
    <div class="w-full px-4 sm:px-10 h-20 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2.5 sm:gap-3">
            <button aria-label="{{ tt('القائمة', 'Menu') }}" onclick="openMobileDrawer()" class="md:hidden w-10 h-10 bg-slate-100 text-slate-700 rounded-xl flex items-center justify-center hover:bg-sky-50 hover:text-sky-600 transition cursor-pointer shrink-0" title="Menu">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>

            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 sm:w-11 sm:h-11 bg-sky-500 text-white rounded-2xl flex items-center justify-center text-lg sm:text-xl font-black shadow-sm group-hover:rotate-6 transition shrink-0">
                    <i class="fa-solid fa-shapes text-yellow-300"></i>
                </div>
                <div>
                    <span class="text-base sm:text-2xl font-black text-slate-800 tracking-tight block leading-none">{{ $brand }} 🧸</span>
                    <span class="text-[10px] sm:text-[11px] font-semibold text-sky-600 block mt-0.5">{{ setting_tr('store_tagline', __('store.brandSub')) }}</span>
                </div>
            </a>
        </div>

        <nav class="hidden md:flex items-center gap-6 lg:gap-7 font-bold text-sm">
            <a href="{{ route('home') }}" class="{{ $nav('home') }} transition">{{ __('store.home') }}</a>
            <a href="{{ route('products.index') }}" class="{{ $nav('products') }} transition">{{ __('store.allToys') }}</a>
            <a href="{{ route('account.tab', 'favorites') }}" class="{{ $nav('favorites') }} transition flex items-center gap-1.5">{{ __('store.favorites') }} <span data-fav-count class="bg-pink-100 text-pink-600 text-[10px] px-1.5 rounded-full">{{ $favCount }}</span></a>
            <a href="{{ route('pages.about') }}" class="{{ $nav('about') }} transition">{{ __('store.about') }}</a>
            <a href="{{ route('faq') }}" class="{{ $nav('faq') }} transition">{{ __('store.faq') }}</a>
            <a href="{{ route('contact') }}" class="{{ $nav('contact') }} transition">{{ __('store.contact') }}</a>
        </nav>

        <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
            <a href="{{ route('account.tab', 'favorites') }}" class="relative p-2.5 bg-pink-50 text-pink-600 rounded-xl hover:bg-pink-100 transition" title="{{ __('store.favorites') }}">
                <i class="fa-solid fa-heart text-base"></i>
                <span data-fav-count class="absolute -top-1 -right-1 bg-pink-600 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">{{ $favCount }}</span>
            </a>
            <a href="{{ route('cart') }}" data-cart-icon class="relative p-2.5 bg-slate-100 text-slate-700 rounded-xl hover:bg-sky-50 hover:text-sky-600 transition" title="{{ __('store.cartTitle') }}">
                <i class="fa-solid fa-bag-shopping text-base"></i>
                <span data-cart-count class="absolute -top-1 -right-1 bg-sky-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">{{ $cartCount }}</span>
            </a>
            <div class="hidden md:block">
                @if ($user)
                    <a href="{{ route('account') }}" class="bg-sky-50 text-sky-700 px-3.5 py-2 rounded-xl font-bold hover:bg-sky-100 transition text-sm flex items-center gap-1.5"><i class="fa-regular fa-user"></i> <span>{{ $user->name }}</span></a>
                @else
                    <a href="{{ route('login') }}" class="bg-sky-500 text-white px-4 py-2 rounded-xl font-bold hover:bg-sky-600 transition text-sm">{{ __('store.login') }}</a>
                @endif
            </div>
        </div>
    </div>
</header>

<!-- القائمة الجانبية للموبايل -->
<div id="drawer-overlay" onclick="closeMobileDrawer()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 hidden"></div>
<aside id="mobile-drawer" aria-hidden="true" class="invisible fixed top-0 {{ $rtl ? 'right-0 translate-x-full' : 'left-0 -translate-x-full' }} w-80 max-w-[85vw] h-full bg-white z-50 shadow-2xl transition-[transform,visibility] duration-300 flex flex-col justify-between overflow-y-auto">
    <div>
        <div class="p-5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white">
            <div class="flex items-center justify-between mb-4">
                <span class="font-black text-lg">{{ $brand }} 🧸</span>
                <button aria-label="{{ tt('إغلاق القائمة', 'Close menu') }}" onclick="closeMobileDrawer()" class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            @if ($user)
                <a href="{{ route('account') }}" class="flex items-center gap-3 bg-white/15 p-3 rounded-2xl hover:bg-white/25 transition">
                    <div class="w-11 h-11 rounded-xl bg-white text-sky-600 flex items-center justify-center font-black text-lg shrink-0"><i class="fa-solid fa-user"></i></div>
                    <div class="overflow-hidden">
                        <h4 class="font-bold text-sm truncate">{{ $user->name }}</h4>
                        <p class="text-[11px] text-sky-100 truncate">{{ $user->email }}</p>
                    </div>
                </a>
            @else
                <div class="bg-white/15 p-3.5 rounded-2xl text-center">
                    <div class="flex gap-2">
                        <a href="{{ route('login') }}" class="flex-1 bg-white text-sky-600 py-2 rounded-xl text-xs font-black">{{ __('store.login') }}</a>
                        <a href="{{ route('register') }}" class="flex-1 bg-amber-400 text-slate-900 py-2 rounded-xl text-xs font-black">{{ __('store.register') }}</a>
                    </div>
                </div>
            @endif
        </div>

        <div class="p-4 border-b border-slate-100">
            <form action="{{ route('products.index') }}" method="GET" class="relative">
                <input type="text" name="search" placeholder="{{ __('store.searchPlaceholder') }}" class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-sky-500">
            </form>
        </div>

        <div class="p-4 border-b border-slate-100 bg-slate-50/60 space-y-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">{{ __('store.countryCurrency') }}</label>
                <form method="POST" action="{{ route('country.switch') }}">
                    @csrf
                    <select name="country" aria-label="{{ __('store.countryCurrency') }}" onchange="this.form.submit()" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 cursor-pointer">
                        @foreach ($countries as $c)
                            <option value="{{ $c->code }}" @selected($country->code === $c->code)>{{ $c->tr('name') }} ({{ $c->tr('symbol') }})</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">{{ __('store.siteLang') }}</label>
                <a href="{{ \App\Support\Seo::switchUrl($rtl ? 'en' : 'ar') }}" hreflang="{{ $rtl ? 'en' : 'ar' }}" class="block text-center w-full bg-sky-100 text-sky-700 py-2 rounded-xl text-xs font-bold hover:bg-sky-200 transition cursor-pointer">
                    {{ __('store.switchLangBtn') }}
                </a>
            </div>
        </div>

        <div class="p-4 border-b border-slate-100 space-y-1 text-xs font-bold text-slate-700">
            <p class="text-[11px] text-slate-500 mb-2 px-2">{{ __('store.myAccountPages') }}</p>
            <a href="{{ route('home') }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600"><i class="fa-solid fa-house w-5 text-sky-500 text-center"></i> {{ __('store.home') }}</a>
            <a href="{{ route('account') }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600"><i class="fa-solid fa-user-gear w-5 text-indigo-500 text-center"></i> {{ __('store.profileOrders') }}</a>
            <a href="{{ route('account.tab', 'favorites') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600">
                <span class="flex items-center gap-3"><i class="fa-solid fa-heart w-5 text-pink-500 text-center"></i> {{ __('store.myFavToys') }}</span>
                <span data-fav-count class="bg-pink-100 text-pink-600 px-2 py-0.5 rounded-full text-[10px]">{{ $favCount }}</span>
            </a>
            <a href="{{ route('cart') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600">
                <span class="flex items-center gap-3"><i class="fa-solid fa-bag-shopping w-5 text-emerald-500 text-center"></i> {{ __('store.cartTitle') }}</span>
                <span data-cart-count class="bg-sky-100 text-sky-600 px-2 py-0.5 rounded-full text-[10px]">{{ $cartCount }}</span>
            </a>
            <a href="{{ route('invoice.latest') }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600"><i class="fa-solid fa-file-invoice w-5 text-purple-500 text-center"></i> {{ __('store.lastInvoice') }}</a>
            <a href="{{ route('tracking') }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600"><i class="fa-solid fa-truck-fast w-5 text-amber-500 text-center"></i> {{ __('store.liveTracking') }}</a>
            <a href="{{ route('faq') }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600"><i class="fa-solid fa-circle-question w-5 text-sky-500 text-center"></i> {{ __('store.faq') }}</a>
            <a href="{{ route('pages.about') }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600"><i class="fa-solid fa-circle-info w-5 text-sky-500 text-center"></i> {{ __('store.about') }}</a>
            <a href="{{ route('contact') }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-sky-50 hover:text-sky-600"><i class="fa-solid fa-headset w-5 text-sky-500 text-center"></i> {{ __('store.contact') }}</a>
        </div>

        <div class="p-4 space-y-2 text-xs font-bold text-slate-700">
            <p class="text-[11px] text-slate-500 mb-2 px-2">{{ __('store.toyCategories') }}</p>
            <a href="{{ route('products.index') }}" class="flex items-center gap-3 p-2 rounded-xl hover:bg-sky-50">🌈 {{ __('store.catAll') }}</a>
            @foreach ($navCategories as $c)
                <a href="{{ route('category.show', $c) }}" class="flex items-center gap-3 p-2 rounded-xl hover:bg-sky-50">
                    <img src="{{ \App\Support\Img::url($c->image, 96) }}" width="32" height="32" loading="lazy" class="w-8 h-8 rounded-lg object-cover shrink-0" alt="{{ $c->tr('title') }}">
                    <span>{{ $c->tr('title') }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="p-4 border-t border-slate-100 bg-slate-50 text-center text-xs text-slate-500">
        @if ($user)
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full bg-red-50 text-red-600 py-2 rounded-xl font-bold mb-2 cursor-pointer">{{ __('store.logout') }}</button>
            </form>
        @endif
        {{ setting_tr('rights', __('store.rights')) }}
    </div>
</aside>
