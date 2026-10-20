<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" title="WhatsApp" class="no-print fixed {{ ($floatingBar ?? false) ? 'bottom-24 lg:bottom-6' : 'bottom-6' }} {{ $rtl ? 'left-6' : 'right-6' }} bg-green-500 text-white w-14 h-14 rounded-full flex items-center justify-center text-3xl shadow-lg hover:bg-green-600 hover:scale-105 transition z-40">
    <i class="fa-brands fa-whatsapp"></i>
</a>

<div id="toast" class="no-print fixed {{ ($floatingBar ?? false) ? 'bottom-24 lg:bottom-6' : 'bottom-6' }} {{ $rtl ? 'right-6' : 'left-6' }} bg-slate-900 text-white px-6 py-3.5 rounded-2xl shadow-xl transform translate-y-24 opacity-0 transition-all duration-300 z-50 flex items-center gap-3">
    <i class="fa-solid fa-face-smile-wink text-yellow-300 text-xl"></i>
    <span id="toast-message" class="font-bold text-sm"></span>
</div>

<footer class="w-full bg-slate-900 text-white pt-12 pb-6 mt-16">
    <div class="content-90 grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 mb-3">
                <div class="w-10 h-10 bg-sky-500 text-white rounded-xl flex items-center justify-center text-lg font-black">
                    <i class="fa-solid fa-shapes"></i>
                </div>
                <span class="text-xl font-black text-white">{{ $brand }} 🧸</span>
            </a>
            <p class="text-slate-400 text-xs leading-relaxed">{{ setting_tr('footer_about', __('store.footerAbout')) }}</p>
            <address class="not-italic text-slate-300 text-xs mt-4 space-y-0.5">
                @if (setting('phone'))
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) setting('phone')) }}" class="flex items-center gap-2 py-1 hover:text-white"><i class="fa-solid fa-phone text-sky-400 w-4"></i> <span class="dir-ltr">{{ setting('phone') }}</span></a>
                @endif
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center gap-2 py-1 hover:text-white"><i class="fa-brands fa-whatsapp text-emerald-400 w-4"></i> <span class="dir-ltr">+{{ $whatsapp }}</span></a>
                @if (setting('admin_email'))
                    <a href="mailto:{{ setting('admin_email') }}" class="flex items-center gap-2 py-1 hover:text-white"><i class="fa-solid fa-envelope text-sky-400 w-4"></i> {{ setting('admin_email') }}</a>
                @endif
                @if (setting_tr('address'))
                    <span class="flex items-center gap-2"><i class="fa-solid fa-location-dot text-pink-400 w-4"></i> {{ setting_tr('address') }}</span>
                @endif
            </address>
            <div class="flex gap-3 mt-4 text-lg text-slate-400">
                @foreach (['instagram' => 'fa-instagram', 'tiktok' => 'fa-tiktok', 'snapchat' => 'fa-snapchat', 'x' => 'fa-x-twitter', 'facebook' => 'fa-facebook', 'youtube' => 'fa-youtube', 'linkedin' => 'fa-linkedin'] as $key => $icon)
                    @if (setting("social_{$key}"))
                        <a href="{{ setting("social_{$key}") }}" target="_blank" rel="noopener me" aria-label="{{ ucfirst($key) }}" class="inline-block py-1.5 hover:text-white transition"><i class="fa-brands {{ $icon }}"></i></a>
                    @endif
                @endforeach
            </div>
        </div>
        <div>
            <h2 class="font-bold mb-3 text-sky-400 text-sm">{{ __('store.quickLinks') }}</h2>
            <ul class="space-y-1 text-slate-300 text-xs">
                <li><a href="{{ route('home') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.home') }}</a></li>
                <li><a href="{{ route('products.index') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.allToys') }}</a></li>
                <li><a href="{{ route('pages.about') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.about') }}</a></li>
                <li><a href="{{ route('faq') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.faq') }}</a></li>
                <li><a href="{{ route('contact') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.contact') }}</a></li>
            </ul>
        </div>
        <div>
            <h2 class="font-bold mb-3 text-sky-400 text-sm">{{ __('store.custService') }}</h2>
            <ul class="space-y-1 text-slate-300 text-xs">
                <li><a href="{{ route('pages.terms') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.terms') }}</a></li>
                <li><a href="{{ route('pages.privacy') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.privacy') }}</a></li>
                <li><a href="{{ route('pages.shipping') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.shipping') }}</a></li>
                <li><a href="{{ route('tracking') }}" class="inline-block py-1.5 hover:text-white transition">{{ __('store.liveTracking') }}</a></li>
            </ul>
        </div>
        <div>
            <h2 class="font-bold mb-3 text-sky-400 text-sm">{{ __('store.safePay') }}</h2>
            <p class="text-slate-400 text-xs mb-3">{{ __('store.safePayDesc') }}</p>
            <div class="flex gap-2.5 text-2xl text-slate-300">
                <i class="fa-brands fa-cc-visa"></i>
                <i class="fa-brands fa-cc-mastercard"></i>
                <i class="fa-brands fa-cc-apple-pay"></i>
                <i class="fa-solid fa-credit-card text-sky-400"></i>
            </div>
        </div>
    </div>
    <div class="border-t border-slate-800 text-center pt-4 text-slate-400 text-xs">
        {{ setting_tr('rights', __('store.rights')) }}
    </div>
</footer>
