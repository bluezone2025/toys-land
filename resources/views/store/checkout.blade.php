@extends('layouts.store', ['active' => 'checkout', 'title' => tt('إتمام الطلب', 'Checkout'), 'showWelcome' => false])

@php
    $rtl = is_rtl();
    $input = 'w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-sky-500';
    $user = auth()->user();
    $addr = $user?->addresses()->orderByDesc('is_default')->first();
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
    <div class="content-90 py-8">
        <h1 class="text-2xl font-black text-slate-800 mb-6">{{ tt('إتمام الطلب والدفع الآمن', 'Secure Checkout & Delivery') }}</h1>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 mb-6 text-xs font-bold space-y-1">
                @foreach ($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            @csrf
            <div class="lg:col-span-8 space-y-6">
                <!-- الخطوة 1: بيانات المستخدم -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-4 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="w-8 h-8 rounded-xl bg-sky-500 text-white flex items-center justify-center font-black text-sm shadow-sm">1</span>
                        <div>
                            <h2 class="text-base sm:text-lg font-black text-slate-800">{{ tt('بيانات العميل وعنوان التوصيل', 'Customer & Delivery Address') }}</h2>
                            <p class="text-[11px] text-slate-400">{{ tt('يرجى تعبئة البيانات بدقة لضمان سرعة وصول المندوب', 'Please fill in your details accurately for fast delivery') }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs pt-2">
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('الاسم الكامل *', 'Full Name *') }}</label><input type="text" name="name" value="{{ old('name', $user?->name) }}" required maxlength="120" class="{{ $input }}" placeholder="{{ tt('الاسم الكريم', 'Enter full name') }}"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('رقم النقال *', 'Mobile Number *') }}</label><input type="tel" name="phone" value="{{ old('phone', $user?->phone) }}" required maxlength="20" class="{{ $input }} dir-ltr {{ $rtl ? 'text-right' : '' }}" placeholder="965xxxxxxxx"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('البريد الإلكتروني (اختياري)', 'Email Address (Optional)') }}</label><input type="email" name="email" value="{{ old('email', $user?->email) }}" class="{{ $input }}" placeholder="name@example.com"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('الدولة *', 'Country *') }}</label>
                            <select name="country" id="c-country" onchange="refreshSummary()" class="{{ $input }} bg-white">
                                @foreach ($countries as $c)
                                    <option value="{{ $c->code }}" @selected(old('country', \App\Support\Shop::country()->code) === $c->code)>{{ $c->tr('name') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('المنطقة / المدينة *', 'City / Area *') }}</label><input type="text" name="city" value="{{ old('city', $addr?->area) }}" required maxlength="100" class="{{ $input }}" placeholder="{{ tt('مثال: السالمية', 'e.g. Salmiya') }}"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('القطعة *', 'Block *') }}</label><input type="text" name="block" value="{{ old('block') }}" required maxlength="50" class="{{ $input }}" placeholder="{{ tt('رقم القطعة', 'Block No.') }}"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('نوع السكن *', 'Property Type *') }}</label>
                            <select name="property_type" class="{{ $input }} bg-white">
                                <option value="house" @selected(old('property_type') === 'house')>{{ tt('منزل / بيت', 'House / Villa') }}</option>
                                <option value="apartment" @selected(old('property_type') === 'apartment')>{{ tt('شقة', 'Apartment') }}</option>
                                <option value="office" @selected(old('property_type') === 'office')>{{ tt('مكتب', 'Office') }}</option>
                            </select>
                        </div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('رقم المنزل / الشقة *', 'House / Apt No. *') }}</label><input type="text" name="apartment" value="{{ old('apartment') }}" required maxlength="100" class="{{ $input }}" placeholder="{{ tt('الرقم', 'Number') }}"></div>
                    </div>
                    @if ($addr)
                        <p class="text-[11px] text-slate-500 bg-slate-50 rounded-xl p-2.5">📍 {{ tt('عنوانك المحفوظ:', 'Saved address:') }} <b>{{ $addr->title }}</b> — {{ $addr->area }}، {{ $addr->details }}</p>
                    @endif
                    <div class="text-xs"><label class="block font-bold text-slate-700 mb-1.5">{{ tt('ملاحظات الطلب أو التغليف (اختياري)', 'Delivery or Gift Notes (Optional)') }}</label><textarea name="notes" rows="2" maxlength="1000" class="{{ $input }}" placeholder="{{ tt('اكتب أي ملاحظة للمندوب أو عبارة على كرت الهدية...', 'Any special instructions or gift card message...') }}">{{ old('notes') }}</textarea></div>
                </div>

                <!-- الخطوة 2: طرق الدفع -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 space-y-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-sky-500 text-white flex items-center justify-center font-black text-sm shadow-sm">2</span>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-slate-800">{{ tt('نقي طريقة الدفع الآمنة (MyFatoorah)', 'Select Secure Payment Method (MyFatoorah)') }}</h2>
                                <p class="text-[11px] text-slate-400">{{ tt('جميع المعاملات مشفرة ومحمية بنسبة 100%', 'All transactions are 100% encrypted and secure') }}</p>
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
                        <div class="flex items-center gap-2 text-lg text-slate-400">
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
                            <img src="{{ $item->product->image_url }}" class="w-10 h-10 rounded-lg object-cover shrink-0" alt="">
                            <span class="flex-1 font-bold text-slate-700 line-clamp-1">{{ $item->product->tr('name') }} × {{ $item->qty }}</span>
                            <span class="font-black text-slate-800 shrink-0">{{ money($item->total) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1.5">{{ tt('عندك كود خصم؟', 'Have a promo code?') }}@if (setting('promo_hint_code')) ({{ tt('جرب:', 'Try:') }} {{ setting('promo_hint_code') }})@endif</label>
                    <div class="flex gap-2">
                        <input type="text" name="coupon" id="coupon-input" value="{{ old('coupon', session('coupon')) }}" class="flex-1 min-w-0 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold uppercase bg-white">
                        <button type="button" onclick="refreshSummary(true)" class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer">{{ tt('تطبيق', 'Apply') }}</button>
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
                    <span id="chk-total" class="text-pink-600 text-2xl">{{ money($subtotal) }}</span>
                </div>
                <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white py-4 rounded-2xl font-black text-sm cursor-pointer shadow-md transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ tt('اعتمد الطلب وأصدر الفاتورة 🧾', 'Confirm Order & Issue Invoice 🧾') }}</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    async function refreshSummary(showMsg = false) {
        const res = await fetch(@json(route('checkout.summary')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ coupon: document.getElementById('coupon-input').value, country: document.getElementById('c-country').value }),
        });
        const d = await res.json();
        document.getElementById('chk-subtotal').innerText = d.subtotal;
        document.getElementById('chk-total').innerText = d.total;
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
    document.addEventListener('DOMContentLoaded', () => refreshSummary());
</script>
@endpush
