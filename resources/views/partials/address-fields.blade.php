{{-- Shared address fields for checkout and "My addresses". Expects $values (array) and optional $withTitle. --}}
@php
    $v = fn ($key, $default = null) => old($key, $values[$key] ?? $default);
    $input = 'w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-sky-500 bg-white';
    $countries = \App\Support\Shop::countries();
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs" data-address-fields>
    @if ($withTitle ?? false)
        <div class="col-span-2 md:col-span-4"><label for="f-title" class="block font-bold text-slate-700 mb-1.5">{{ tt('اسم العنوان *', 'Address name *') }}</label>
            <input id="f-title" type="text" name="title" value="{{ $v('title') }}" required maxlength="100" class="{{ $input }}" placeholder="{{ tt('مثال: البيت، بيت الوالد، الشغل', 'e.g. Home, Office') }}"></div>
    @endif
    <div class="col-span-2 md:col-span-2"><label for="f-country" class="block font-bold text-slate-700 mb-1.5">{{ tt('الدولة *', 'Country *') }}</label>
        <select id="f-country" name="country" data-country-select class="{{ $input }}">
            @foreach ($countries as $c)
                <option value="{{ $c->code }}" data-dial="{{ $c->dial_code }}" @selected($v('country', \App\Support\Shop::country()->code) === $c->code)>{{ $c->flag }} {{ $c->tr('name') }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-span-2 md:col-span-2"><label for="f-city" class="block font-bold text-slate-700 mb-1.5">{{ tt('المنطقة / المدينة *', 'Area / City *') }}</label>
        <input id="f-city" type="text" name="city" value="{{ $v('city') }}" required maxlength="100" autocomplete="address-level2" class="{{ $input }}" placeholder="{{ tt('مثال: السالمية', 'e.g. Salmiya') }}"></div>
    <div><label for="f-block" class="block font-bold text-slate-700 mb-1.5">{{ tt('القطعة *', 'Block *') }}</label>
        <input id="f-block" type="text" name="block" value="{{ $v('block') }}" required maxlength="50" inputmode="numeric" class="{{ $input }}" placeholder="4"></div>
    <div><label for="f-street" class="block font-bold text-slate-700 mb-1.5">{{ tt('الشارع *', 'Street *') }}</label>
        <input id="f-street" type="text" name="street" value="{{ $v('street') }}" required maxlength="150" class="{{ $input }}" placeholder="{{ tt('رقم أو اسم الشارع', 'Street no. or name') }}"></div>
    <div><label for="f-avenue" class="block font-bold text-slate-700 mb-1.5">{{ tt('الجادة', 'Avenue') }}</label>
        <input id="f-avenue" type="text" name="avenue" value="{{ $v('avenue') }}" maxlength="100" class="{{ $input }}" placeholder="{{ tt('اختياري', 'Optional') }}"></div>
    <div><label for="f-property-type" class="block font-bold text-slate-700 mb-1.5">{{ tt('نوع السكن *', 'Property type *') }}</label>
        <select id="f-property-type" name="property_type" class="{{ $input }}">
            @foreach (\App\Models\Address::TYPES as $key => [$ar, $en])
                <option value="{{ $key }}" @selected($v('property_type', 'house') === $key)>{{ tt($ar, $en) }}</option>
            @endforeach
        </select>
    </div>
    <div><label for="f-apartment" class="block font-bold text-slate-700 mb-1.5">{{ tt('رقم المنزل / العمارة *', 'House / Building no. *') }}</label>
        <input id="f-apartment" type="text" name="apartment" value="{{ $v('apartment') }}" required maxlength="100" class="{{ $input }}" placeholder="15"></div>
    <div><label for="f-floor" class="block font-bold text-slate-700 mb-1.5">{{ tt('الدور / الشقة', 'Floor / Flat') }}</label>
        <input id="f-floor" type="text" name="floor" value="{{ $v('floor') }}" maxlength="100" class="{{ $input }}" placeholder="{{ tt('اختياري', 'Optional') }}"></div>
</div>
