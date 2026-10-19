<a href="{{ route('category.show', $c) }}" class="cat-card min-w-[210px] sm:min-w-[230px] flex-1 snap-center bg-white hover:border-sky-500 border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden transition group flex flex-col justify-between">
    <div class="relative h-36 w-full overflow-hidden bg-slate-100">
        <img src="{{ $c->image_url }}" alt="{{ $c->tr('title') }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
        @if ($c->tr('badge'))
            <span class="absolute bottom-2 {{ is_rtl() ? 'right-2.5' : 'left-2.5' }} bg-white/95 text-sky-700 text-[10px] font-black px-2.5 py-0.5 rounded-full shadow-sm">{{ $c->tr('badge') }}</span>
        @endif
    </div>
    <div class="p-4 text-center">
        <h3 class="font-black text-slate-800 group-hover:text-sky-600 transition text-sm sm:text-base mb-1">{{ $c->tr('title') }}</h3>
        <p class="text-[11px] text-slate-500 line-clamp-1">{{ $c->tr('desc') }}</p>
    </div>
</a>
