@php $fav = \App\Support\Favorites::has($p->id); $rtl = is_rtl(); @endphp
<div class="toy-card bg-white rounded-2xl shadow-sm hover:shadow-md overflow-hidden border border-slate-100 flex flex-col justify-between h-full relative">
    <div>
        <div class="relative overflow-hidden bg-slate-50">
            @if ($p->tr('badge'))
                <span class="absolute top-3 {{ $rtl ? 'right-3' : 'left-3' }} bg-white/95 text-sky-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full shadow-sm z-10">{{ $p->tr('badge') }}</span>
            @endif
            @if ($p->ageLabel())
                <span class="absolute bottom-2 {{ $rtl ? 'left-2' : 'right-2' }} bg-slate-900/75 text-white text-[10px] font-medium px-2 py-0.5 rounded-md z-10">{{ $p->ageLabel() }}</span>
            @endif

            <button onclick="toggleFavorite({{ $p->id }}, event)" class="absolute top-3 {{ $rtl ? 'left-3' : 'right-3' }} w-8 h-8 bg-white/95 backdrop-blur rounded-full flex items-center justify-center shadow-sm z-20 hover:scale-110 transition cursor-pointer" title="{{ __('store.favorites') }}">
                <i class="fav-icon-{{ $p->id }} {{ $fav ? 'fa-solid text-pink-500' : 'fa-regular text-slate-400' }} fa-heart text-sm"></i>
            </button>

            <a href="{{ route('products.show', $p) }}" class="block">
                <img src="{{ $p->image_url }}" alt="{{ $p->tr('name') }}" loading="lazy" class="w-full h-48 object-cover hover:scale-105 transition duration-300">
            </a>
        </div>
        <div class="p-4">
            <a href="{{ route('products.show', $p) }}" class="font-bold text-base text-slate-800 hover:text-sky-600 transition block mb-1 line-clamp-1">{{ $p->tr('name') }}</a>
            <p class="text-slate-500 text-xs mb-3 line-clamp-2">{{ $p->tr('desc') }}</p>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-pink-600 font-black text-lg">{{ money($p->price) }}</span>
                    @if ($p->compare_price > $p->price)
                        <span class="text-slate-400 line-through text-[11px] block">{{ money($p->compare_price) }}</span>
                    @endif
                </div>
                @if ($p->inStock())
                    <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded">{{ __('store.inStock') }}</span>
                @else
                    <span class="text-[10px] text-red-600 font-bold bg-red-50 px-2 py-0.5 rounded">{{ tt('نفدت الكمية', 'Out of Stock') }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="p-4 pt-0 flex gap-2">
        <a href="{{ route('products.show', $p) }}" class="flex-1 bg-slate-100 text-slate-700 py-2 rounded-xl font-bold hover:bg-slate-200 transition text-center text-xs">{{ __('store.details') }}</a>
        @if ($p->inStock())
            <button onclick="quickAddToCart({{ $p->id }})" class="flex-1 bg-sky-500 text-white py-2 rounded-xl font-bold hover:bg-sky-600 transition text-xs cursor-pointer">{{ __('store.addToCart') }}</button>
        @else
            <button disabled class="flex-1 bg-slate-200 text-slate-400 py-2 rounded-xl font-bold text-xs cursor-not-allowed">{{ __('store.addToCart') }}</button>
        @endif
    </div>
</div>
