@extends('layouts.store', ['active' => 'products', 'title' => $pageTitle])

@php $rtl = is_rtl(); @endphp

@section('content')
    <div class="content-90 py-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-black text-slate-800">{{ $pageTitle }}</h1>
                <p class="text-slate-500 text-xs mt-1">{{ tt('نقي القسم بالصورة أو تصفح كل الألعاب الموجودة', 'Select a category card below or browse all toys') }}</p>
            </div>
            <form method="GET" action="{{ route('products.index') }}" class="flex gap-2 w-full md:w-auto">
                @if ($selected !== 'all')<input type="hidden" name="category" value="{{ $selected }}">@endif
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('store.searchPlaceholder') }}" class="flex-1 md:w-72 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-sky-500 bg-white">
                <select name="sort" onchange="this.form.submit()" class="border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold bg-white">
                    <option value="default">{{ tt('الترتيب الافتراضي', 'Default Sort') }}</option>
                    <option value="price-low" @selected($sort === 'price-low')>{{ tt('الأرخص أولاً', 'Price: Low to High') }}</option>
                    <option value="price-high" @selected($sort === 'price-high')>{{ tt('الأغلى أولاً', 'Price: High to Low') }}</option>
                    <option value="newest" @selected($sort === 'newest')>{{ tt('الأحدث', 'Newest') }}</option>
                </select>
                <button class="bg-sky-500 hover:bg-sky-600 text-white px-4 rounded-xl text-xs font-bold cursor-pointer"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
            <a href="{{ route('products.index') }}" class="flex items-center gap-2.5 p-2 rounded-2xl border transition {{ $selected === 'all' && ! $age && ! $search ? 'border-sky-500 bg-sky-50 shadow-sm ring-2 ring-sky-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                <img src="https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=600&q=80" class="w-11 h-11 rounded-xl object-cover shrink-0" alt="">
                <div class="overflow-hidden">
                    <span class="font-black text-xs text-slate-800 block truncate">{{ tt('كل الألعاب', 'All Toys') }}</span>
                    <span class="text-[10px] text-slate-500 block truncate">{{ tt('جميع أقسام المتجر', 'Full store catalog') }}</span>
                </div>
            </a>
            @foreach ($navCategories as $c)
                <a href="{{ route('products.index', ['category' => $c->slug]) }}" class="flex items-center gap-2.5 p-2 rounded-2xl border transition {{ $selected === $c->slug && ! $age ? 'border-sky-500 bg-sky-50 shadow-sm ring-2 ring-sky-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                    <img src="{{ $c->image_url }}" class="w-11 h-11 rounded-xl object-cover shrink-0" alt="">
                    <div class="overflow-hidden">
                        <span class="font-black text-xs text-slate-800 block truncate">{{ $c->tr('title') }}</span>
                        <span class="text-[10px] text-slate-500 block truncate">{{ $c->tr('desc') }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($products->isEmpty())
            <div class="bg-white p-10 rounded-2xl text-center font-bold text-slate-500">{{ tt('ماكو ألعاب بهذا الفلتر حالياً.', 'No toys match this filter.') }}</div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                @foreach ($products as $p)
                    @include('partials.product-card', ['p' => $p])
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
