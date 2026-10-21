@extends('layouts.store', ['noindex' => true, 'floatingBar' => true, 'active' => 'checkout', 'title' => tt('إتمام الطلب', 'Checkout'), 'showWelcome' => false])

@php
    $rtl = is_rtl();
    $input = 'w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-sky-500';
    $user = auth()->user();
    $savedAddresses = $user ? $user->addresses()->orderByDesc('is_default')->latest('id')->get() : collect();
    $addressValues = $savedAddresses->first()?->formValues() ?? [];
    $phoneNumber = \App\Http\Controllers\CheckoutController::localNumber($user?->phone);
    $methods = [
        'knet' => ['icon' => '<span class="text-[10px] tracking-widest leading-none">KNET</span><i class="fa-solid fa-money-check text-sm mt-1"></i>', 'box' => 'bg-gradient-to-br from-blue-600 to-sky-500 flex-col font-black', 'title' => tt('كي نت (KNet)', 'KNet Local Debit'), 'tag' => tt('الأسرع بالكويت', 'Popular'), 'desc' => tt('دفع فوري آمن عبر بطاقات البنوك الكويتية', 'Instant payment via Kuwaiti bank cards')],
        'card' => ['icon' => '<i class="fa-brands fa-cc-visa"></i>', 'box' => 'bg-gradient-to-br from-indigo-600 to-purple-600 text-xl', 'title' => 'Visa / MasterCard', 'tag' => null, 'desc' => tt('البطاقات الائتمانية ومسبقة الدفع الخليجية والعالمية', 'All GCC & International credit/debit cards')],
        'applepay' => ['icon' => '<i class="fa-brands fa-apple-pay"></i>', 'box' => 'bg-slate-900 text-2xl', 'title' => 'Apple Pay', 'tag' => null, 'desc' => tt('دفع سريع بلمسة واحدة لأجهزة الآيفون والماك', 'One-touch fast checkout via Apple Wallet')],
        'cod' => ['icon' => '<i class="fa-solid fa-hand-holding-dollar"></i>', 'box' => 'bg-gradient-to-br from-emerald-500 to-teal-600 text-xl', 'title' => tt('الدفع عند الاستلام', 'Cash on Delivery'), 'tag' => null, 'desc' => tt('ادفع كاش أو رابط لمندوب التوصيل عند باب بيتك', 'Pay cash directly when your order arrives')],
    ];
    $methods = array_intersect_key($methods, array_flip($enabledMethods));
    $firstMethod = old('payment_method', array_key_first($methods));
@endphp

@section('content')
    <div class="content-90 py-8 pb-28 lg:pb-8">
        <h1 class="text-2xl font-black text-slate-800 mb-6">{{ tt('إتمام الطلب والدفع الآمن', 'Secure Checkout & Delivery') }}</h1>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 mb-6 text-xs font-bold space-y-1">
                @foreach ($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            @csrf
            <div class="lg:col-span-8 space-y-6 min-w-0">
                <!-- الخطوة 1: بيانات العميل -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-8 space-y-4 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="w-8 h-8 rounded-xl bg-sky-500 text-white flex items-center justify-center font-black text-sm shadow-sm">1</span>
                        <div>
                            <h2 class="text-base sm:text-lg font-black text-slate-800">{{ tt('بيانات العميل', 'Customer Details') }}</h2>
                            <p class="text-[11px] text-slate-500">{{ tt('نرسل الفاتورة على بريدك ونتواصل معك على رقمك لتأكيد التوصيل', 'We email your invoice and call this number to arrange delivery') }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div><label for="f-name" class="block font-bold text-slate-700 mb-1.5">{{ tt('الاسم الكامل *', 'Full Name *') }}</label><input id="f-name" type="text" name="name" value="{{ old('name', $user?->name) }}" required maxlength="120" autocomplete="name" class="{{ $input }}" placeholder="{{ tt('الاسم الكريم', 'Enter full name') }}"></div>
                        <div>@include('partials.phone-field', ['value' => $phoneNumber])</div>
                        <div><label for="f-email" class="block font-bold text-slate-700 mb-1.5">{{ tt('البريد الإلكتروني *', 'Email Address *') }}</label><input id="f-email" type="email" name="email" value="{{ old('email', $user?->email) }}" required maxlength="150" autocomplete="email" class="{{ $input }} dir-ltr" placeholder="name@example.com"></div>
                    </div>
                </div>

                <!-- الخطوة 2: عنوان التوصيل -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-8 space-y-4 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="w-8 h-8 rounded-xl bg-sky-500 text-white flex items-center justify-center font-black text-sm shadow-sm">2</span>
                        <div>
                            <h2 class="text-base sm:text-lg font-black text-slate-800">{{ tt('عنوان التوصيل', 'Delivery Address') }}</h2>
                            <p class="text-[11px] text-slate-500">{{ tt('يرجى تعبئة العنوان بدقة لضمان سرعة وصول المندوب', 'Please fill in your address accurately for fast delivery') }}</p>
                        </div>
                    </div>

                    {{-- Saved addresses: from the account, or remembered on this device for guests (filled by JS). --}}
                    <div id="saved-addresses" class="{{ $savedAddresses->isEmpty() ? 'hidden' : '' }}">
                        <p class="text-xs font-black text-slate-700 mb-2">📍 {{ tt('عناوينك المحفوظة — اختر واحد', 'Your saved addresses — pick one') }}</p>
                        <div id="saved-addresses-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach ($savedAddresses as $saved)
                                <button type="button" data-fill='@json($saved->formValues())' class="saved-address text-start border-2 border-slate-200 hover:border-sky-400 rounded-2xl p-3 transition cursor-pointer">
                                    <span class="font-black text-sm text-slate-800 flex items-center gap-2">{{ $saved->title }} @if ($saved->is_default)<span class="bg-sky-100 text-sky-700 text-[10px] px-2 py-0.5 rounded-md">{{ tt('الأساسي', 'Default') }}</span>@endif</span>
                                    <span class="block text-[11px] text-slate-500 mt-1">{{ $saved->summary() }}</span>
                                </button>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-slate-500 mt-3 mb-1">{{ tt('أو اكتب عنوان جديد:', 'Or enter a new address:') }}</p>
                    </div>

                    @include('partials.address-fields', ['values' => $addressValues])

                    @auth
                        <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                            <input type="checkbox" name="save_address" value="1" @checked(old('save_address', $savedAddresses->isEmpty())) class="w-4 h-4 accent-sky-500">
                            {{ tt('احفظ هذا العنوان في حسابي', 'Save this address to my account') }}
                        </label>
                    @endauth
                    <div class="text-xs"><label for="f-notes" class="block font-bold text-slate-700 mb-1.5">{{ tt('ملاحظات الطلب أو التغليف (اختياري)', 'Delivery or Gift Notes (Optional)') }}</label><textarea id="f-notes" name="notes" rows="2" maxlength="1000" class="{{ $input }}" placeholder="{{ tt('اكتب أي ملاحظة للمندوب أو عبارة على كرت الهدية...', 'Any special instructions or gift card message...') }}">{{ old('notes') }}</textarea></div>
                </div>

                <!-- الخطوة 2: طرق الدفع -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-sky-500 text-white flex items-center justify-center font-black text-sm shadow-sm">3</span>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-slate-800">{{ tt('نقي طريقة الدفع الآمنة (MyFatoorah)', 'Select Secure Payment Method (MyFatoorah)') }}</h2>
                                <p class="text-[11px] text-slate-500">{{ tt('جميع المعاملات مشفرة ومحمية بنسبة 100%', 'All transactions are 100% encrypted and secure') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full text-[11px] font-bold">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>SSL 256-Bit Verified</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($methods as $key => $m)
                            <label class="cursor-pointer block relative">
                                <input type="radio" name="payment_method" value="{{ $key }}" @checked($firstMethod === $key) class="pay-radio sr-only">
                                <div class="pay-method-card border-2 border-slate-200 rounded-2xl p-4 flex items-center justify-between gap-3 bg-white hover:border-sky-300">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-12 h-12 rounded-2xl {{ $m['box'] }} text-white flex items-center justify-center shadow-sm shrink-0">{!! $m['icon'] !!}</div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-black text-slate-800 text-sm">{{ $m['title'] }}</h4>
                                                @if ($m['tag'])<span class="bg-sky-100 text-sky-700 text-[10px] font-black px-2 py-0.5 rounded-md">{{ $m['tag'] }}</span>@endif
                                                @if ($key === 'card')<i class="fa-brands fa-cc-mastercard text-amber-500 text-sm"></i>@endif
                                            </div>
                                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $m['desc'] }}</p>
                                        </div>
                                    </div>
                                    <div class="pay-check-circle w-6 h-6 rounded-full border-2 border-slate-300 flex items-center justify-center text-transparent shrink-0 transition">
                                        <i class="fa-solid fa-check text-xs"></i>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/70 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                        <span class="flex items-center gap-2 font-bold text-slate-700">
                            <i class="fa-solid fa-lock text-sky-500"></i>
                            {{ tt('بوابة دفع إلكترونية معتمدة رسمياً عبر نظام MyFatoorah الكويت والخليج', 'Official Payment Gateway powered by MyFatoorah GCC') }}
                        </span>
                        <div class="flex items-center gap-2 text-lg text-slate-500">
                            <i class="fa-brands fa-cc-visa"></i>
                            <i class="fa-brands fa-cc-mastercard"></i>
                            <i class="fa-brands fa-cc-apple-pay"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ملخص الحساب وكود الخصم -->
            <div class="lg:col-span-4 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4 sticky top-24">
                <h3 class="font-black text-base border-b border-slate-100 pb-3">{{ tt('ملخص الحساب وكود الخصم', 'Summary & Promo Code') }}</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto no-scrollbar">
                    @foreach ($items as $item)
                        <div class="flex items-center gap-2.5 text-[11px]">
                            <img src="{{ \App\Support\Img::url($item->product->image, 96) }}" width="40" height="40" class="w-10 h-10 rounded-lg object-cover shrink-0" alt="{{ $item->product->tr('name') }}">
                            <span class="flex-1 font-bold text-slate-700 line-clamp-1">{{ $item->product->tr('name') }} × {{ $item->qty }}</span>
                            <span class="font-black text-slate-800 shrink-0">{{ money($item->total) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200">
                    <label for="coupon-input" class="block text-[11px] font-bold text-slate-600 mb-1.5">{{ tt('عندك كود خصم؟', 'Have a promo code?') }}@if (setting('promo_hint_code')) ({{ tt('جرب:', 'Try:') }} {{ setting('promo_hint_code') }})@endif</label>
                    <div class="flex gap-2">
                        <input type="text" name="coupon" id="coupon-input" value="{{ old('coupon', session('coupon')) }}" class="flex-1 min-w-0 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold uppercase bg-white">
                        <button type="button" data-call="refreshSummary" data-args="[true]" class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer">{{ tt('تطبيق', 'Apply') }}</button>
                    </div>
                    <p id="coupon-msg" class="text-[11px] font-bold mt-2 hidden"></p>
                </div>
                <div class="space-y-2.5 text-xs border-b border-slate-100 pb-3">
                    <div class="flex justify-between text-slate-600"><span>{{ tt('المجموع الفرعي:', 'Subtotal:') }}</span><span id="chk-subtotal" class="font-bold">{{ money($subtotal) }}</span></div>
                    <div id="chk-discount-row" class="flex justify-between text-emerald-600 hidden"><span>{{ tt('الخصم', 'Discount') }} (<span id="chk-code-label"></span>):</span><span id="chk-discount" class="font-bold"></span></div>
                    <div class="flex justify-between text-slate-600"><span>{{ tt('التوصيل وتغليف الهدايا:', 'Delivery & Gift Wrap:') }}</span><span id="chk-shipping" class="text-emerald-600 font-bold">{{ __('store.free') }}</span></div>
                </div>
                <div class="flex justify-between items-center font-black text-base pt-1">
                    <span>{{ __('store.total') }}:</span>
                    <span id="chk-total" data-total class="text-pink-600 text-2xl">{{ money($subtotal) }}</span>
                </div>
                <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white py-4 rounded-2xl font-black text-sm cursor-pointer shadow-md transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ tt('الدفع وإصدار الفاتورة 🧾', 'Pay & Issue Invoice 🧾') }}</span>
                </button>
            </div>
            <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] px-4 py-3 flex items-center gap-3">
                <div class="shrink-0">
                    <span class="block text-[10px] text-slate-500 font-bold">{{ __('store.total') }}</span>
                    <span data-total class="text-pink-600 font-black text-lg">{{ money($subtotal) }}</span>
                </div>
                <button type="submit" class="flex-1 bg-sky-500 hover:bg-sky-600 text-white py-3.5 rounded-2xl font-black text-sm shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-lock"></i> {{ tt('الدفع وإصدار الفاتورة', 'Pay & Issue Invoice') }}
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    const form = document.getElementById('checkout-form');
    const countrySelect = form.querySelector('[data-country-select]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const ADDRESS_KEYS = ['country', 'city', 'block', 'street', 'avenue', 'property_type', 'apartment', 'floor'];

    async function refreshSummary(showMsg = false) {
        const res = await fetch(@json(route('checkout.summary')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ coupon: document.getElementById('coupon-input').value, country: countrySelect.value }),
        });
        const d = await res.json();
        document.getElementById('chk-subtotal').innerText = d.subtotal;
        document.querySelectorAll('[data-total]').forEach(el => el.innerText = d.total);
        const ship = document.getElementById('chk-shipping');
        ship.innerText = d.shipping;
        ship.className = (d.shipping_free ? 'text-emerald-600' : 'text-slate-800') + ' font-bold';
        document.getElementById('chk-discount-row').classList.toggle('hidden', !d.coupon);
        document.getElementById('chk-code-label').innerText = d.coupon || '';
        document.getElementById('chk-discount').innerText = '- ' + d.discount;
        const msg = document.getElementById('coupon-msg');
        if (showMsg || d.coupon) {
            msg.innerText = d.coupon_message;
            msg.className = 'text-[11px] font-bold mt-2 ' + (d.coupon ? 'text-emerald-600' : 'text-red-500');
            if (!document.getElementById('coupon-input').value) msg.classList.add('hidden');
        }
    }

    function syncDialCode() {
        const dial = countrySelect.selectedOptions[0]?.dataset.dial;
        if (dial) document.querySelectorAll('[data-dial-code]').forEach(el => el.innerText = '+' + dial);
    }

    function fillAddress(values, button) {
        ADDRESS_KEYS.forEach(key => {
            const field = form.elements[key];
            if (field && values[key] !== undefined && values[key] !== null) field.value = values[key];
        });
        document.querySelectorAll('.saved-address').forEach(b => b.classList.toggle('border-sky-500', b === button));
        document.querySelectorAll('.saved-address').forEach(b => b.classList.toggle('bg-sky-50', b === button));
        syncDialCode();
        refreshSummary();
    }

    // Guests: remember the last few addresses on this device only.
    @guest
    (() => {
        let saved = [];
        try { saved = JSON.parse(localStorage.getItem('kids_addresses') || '[]'); } catch (e) {}
        const list = document.getElementById('saved-addresses-list');
        saved.forEach(values => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'saved-address text-start border-2 border-slate-200 hover:border-sky-400 rounded-2xl p-3 transition cursor-pointer';
            b.dataset.fill = JSON.stringify(values);
            const title = document.createElement('span');
            title.className = 'font-black text-sm text-slate-800 block';
            title.textContent = values.city || '';
            const line = document.createElement('span');
            line.className = 'block text-[11px] text-slate-500 mt-1';
            line.textContent = [values.block && @json(tt('قطعة ', 'Block ')) + values.block, values.street && @json(tt('شارع ', 'Street ')) + values.street, values.apartment].filter(Boolean).join(@json(tt('، ', ', ')));
            b.append(title, line);
            list.appendChild(b);
        });
        if (saved.length) document.getElementById('saved-addresses').classList.remove('hidden');
        form.addEventListener('submit', () => {
            const values = Object.fromEntries(ADDRESS_KEYS.map(k => [k, form.elements[k]?.value || '']));
            const key = v => [v.city, v.block, v.street, v.apartment].join('|');
            const next = [values, ...saved.filter(v => key(v) !== key(values))].slice(0, 3);
            try { localStorage.setItem('kids_addresses', JSON.stringify(next)); } catch (e) {}
        });
    })();
    @endguest

    document.addEventListener('click', e => {
        const b = e.target.closest('.saved-address');
        if (b) fillAddress(JSON.parse(b.dataset.fill), b);
    });
    countrySelect.addEventListener('change', () => { syncDialCode(); refreshSummary(); });
    document.addEventListener('DOMContentLoaded', () => { syncDialCode(); refreshSummary(); });
</script>
@endpush
