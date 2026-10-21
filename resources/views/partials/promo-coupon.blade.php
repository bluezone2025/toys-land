@php $promo = \App\Models\Coupon::promo(); @endphp
@if ($promo)
    <div class="bg-amber-50 border-2 border-dashed border-amber-400 rounded-2xl p-3 {{ $class ?? '' }}">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-amber-800">🎁 {{ tt('هدية لك', 'A gift for you') }} — {{ $promo->discountText() }}</p>
                @if ($promo->urgencyText())
                    <p class="text-[11px] font-black text-red-600 mt-0.5">⏳ {{ $promo->urgencyText() }}</p>
                @endif
            </div>
            <button type="button" data-copy="{{ $promo->code }}" data-copy-message="{{ tt('تم نسخ الكود ✅', 'Code copied ✅') }}" class="shrink-0 bg-white border border-amber-300 text-slate-900 font-black tracking-widest text-sm px-3 py-1.5 rounded-xl hover:bg-amber-100 cursor-pointer dir-ltr" title="{{ tt('انسخ الكود', 'Copy code') }}">
                {{ $promo->code }} <i class="fa-regular fa-copy text-xs text-amber-600"></i>
            </button>
        </div>
    </div>
@endif
