{{-- Phone with the country dial code shown separately; it follows the selected country. --}}
@php $dial = \App\Support\Shop::countries()->firstWhere('code', old('country', $country ?? \App\Support\Shop::country()->code))?->dial_code ?? '965'; @endphp
<label class="block font-bold text-slate-700 mb-1.5">{{ tt('رقم الهاتف *', 'Phone number *') }}</label>
<div class="flex rounded-xl border border-slate-200 bg-white overflow-hidden focus-within:border-sky-500" dir="ltr">
    <span class="flex items-center gap-1 px-3 bg-slate-50 border-e border-slate-200 font-bold text-slate-600 shrink-0" data-dial-code>+{{ $dial }}</span>
    <input type="tel" name="phone" value="{{ old('phone', $value ?? '') }}" required maxlength="15" inputmode="tel" autocomplete="tel-national"
           class="flex-1 min-w-0 p-3 focus:outline-none" placeholder="9999 9999">
</div>
