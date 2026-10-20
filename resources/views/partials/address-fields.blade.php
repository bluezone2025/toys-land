{{-- Shared address fields for checkout and "My addresses". Expects $values (array) and optional $withTitle. --}}
@php
    $v = fn ($key, $default = null) => old($key, $values[$key] ?? $default);
    $input = 'w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-sky-500 bg-white';
    $countries = \App\Support\Shop::countries();
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs" data-address-fields>
    @if ($withTitle ?? false)
        <div class="col-span-2 md:col-span-4"><label class="block font-bold text-slate-700 mb-1.5">{{ tt('اسم العنوان *', 'Address name *') }}</label>
            <input type="text" name="title" value="{{ $v('title') }}" required maxlength="100" class="{{ $input }}" placeholder="{{ tt('مثال: البيت، بيت الوالد، الشغل', 'e.g. Home, Office') }}"></div>
    @endif
    <div class="col-span-2 md:col-span-2"><label class="block font-bold text-slate-700 mb-1.5">{{ tt('الدولة *', 'Country *') }}</label>
        <select name="country" data-country-select class="{{ $input }}">
            @foreach ($countries as $c)
                <option value="{{ $c->code }}" data-dial="{{ $c->dial_code }}" @selected($v('country', \App\Support\Shop::country()->code) === $c->code)>{{ $c->flag }} {{ $c->tr('name') }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-span-2 md:col-span-2"><label class="block font-bold text-slate-700 mb-1.5">{{ tt('المنطقة / المدينة *', 'Area / City *') }}</label>
        <input type="text" name="city" value="{{ $v('city') }}" required maxlength="100" autocomplete="address-level2" class="{{ $input }}" placeholder="{{ tt('مثال: السالمية', 'e.g. Salmiya') }}"></div>
    <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('القطعة *', 'Block *') }}</label>
        <input type="text" name="block" value="{{ $v('block') }}" required maxlength="50" inputmode="numeric" class="{{ $input }}" placeholder="4"></div>
    <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('الشارع *', 'Street *') }}</label>
        <input type="text" name="street" value="{{ $v('street') }}" required maxlength="150" class="{{ $input }}" placeholder="{{ tt('رقم أو اسم الشارع', 'Street no. or name') }}"></div>
    <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('الجادة', 'Avenue') }}</label>
        <input type="text" name="avenue" value="{{ $v('avenue') }}" maxlength="100" class="{{ $input }}" placeholder="{{ tt('اختياري', 'Optional') }}"></div>
    <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('نوع السكن *', 'Property type *') }}</label>
        <select name="property_type" class="{{ $input }}">
            @foreach (\App\Models\Address::TYPES as $key => [$ar, $en])
                <option value="{{ $key }}" @selected($v('property_type', 'house') === $key)>{{ tt($ar, $en) }}</option>
            @endforeach
        </select>
    </div>
    <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('رقم المنزل / العمارة *', 'House / Building no. *') }}</label>
        <input type="text" name="apartment" value="{{ $v('apartment') }}" required maxlength="100" class="{{ $input }}" placeholder="15"></div>
    <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('الدور / الشقة', 'Floor / Flat') }}</label>
        <input type="text" name="floor" value="{{ $v('floor') }}" maxlength="100" class="{{ $input }}" placeholder="{{ tt('اختياري', 'Optional') }}"></div>
</div>
