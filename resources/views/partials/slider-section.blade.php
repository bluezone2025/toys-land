{{-- Horizontal product slider with heading. Expects: $id, $title, $sub, $products, $viewAll, optional $wrapClass --}}
@php $rtl = is_rtl(); @endphp
<section class="{{ $wrapClass ?? 'w-full mb-12' }}">
    <div class="flex justify-between items-center mb-5">
        <div>
            <h2 class="text-xl font-black text-slate-800">{{ $title }}</h2>
            @if ($sub)<p class="text-slate-500 text-xs">{{ $sub }}</p>@endif
        </div>
        <div class="flex items-center gap-2">
            <button onclick="scrollSlider('{{ $id }}', 1)" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-sky-500 hover:text-white transition cursor-pointer"><i class="fa-solid {{ $rtl ? 'fa-chevron-right' : 'fa-chevron-left' }} text-xs"></i></button>
            <button onclick="scrollSlider('{{ $id }}', -1)" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-sky-500 hover:text-white transition cursor-pointer"><i class="fa-solid {{ $rtl ? 'fa-chevron-left' : 'fa-chevron-right' }} text-xs"></i></button>
            @if ($viewAll ?? null)
                <a href="{{ $viewAll }}" class="text-sky-600 font-bold text-xs hover:underline">{{ __('store.viewAll') }}</a>
            @endif
        </div>
    </div>
    <div id="{{ $id }}" class="flex gap-5 overflow-x-auto no-scrollbar pb-3 snap-x snap-mandatory scroll-smooth">
        @foreach ($products as $p)
            <div class="shrink-0 w-full sm:w-[calc((100%-1.25rem)/2)] md:w-[calc((100%-2.5rem)/3)] lg:w-[calc((100%-3.75rem)/4)] xl:w-[calc((100%-5rem)/5)] snap-start">@include('partials.product-card', ['p' => $p])</div>
        @endforeach
    </div>
</section>
