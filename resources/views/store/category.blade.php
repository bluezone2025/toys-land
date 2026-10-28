@php
    $schema = [\App\Support\Seo::breadcrumbs([
        [__('store.home'), \App\Support\Seo::url(app()->getLocale(), route('home'))],
        [$category->tr('title'), \App\Support\Seo::url(app()->getLocale(), route('category.show', $category))],
    ])];
@endphp
@extends('layouts.store', [
    'active' => 'products',
    'title' => $category->seo('title', $category->tr('title')),
    'metaDescription' => $category->seo('description', $category->tr('desc').' — '.$category->tr('title').' | '.setting_tr('store_tagline', __('store.brandSub'))),
    'metaImage' => $category->image,
    'schema' => $schema,
])

@section('content')
    <div class="content-90 py-8">
        <div class="relative rounded-3xl overflow-hidden bg-slate-900 text-white mb-8 shadow-md h-56 sm:h-64 flex items-center">
            <img src="{{ \App\Support\Img::url($category->image, 1200) }}" srcset="{{ \App\Support\Img::srcset($category->image, [640, 1200]) }}" sizes="100vw" fetchpriority="high" class="absolute inset-0 w-full h-full object-cover opacity-45" alt="{{ $category->tr('title') }}">
            <div class="relative z-10 px-6 lg:px-12 max-w-2xl">
                @if ($category->tr('badge'))
                    <span class="bg-amber-400 text-slate-950 px-3 py-0.5 rounded-full text-xs font-black inline-block mb-2">{{ $category->tr('badge') }}</span>
                @endif
                <h1 class="text-2xl md:text-4xl font-black mb-2">{{ $category->tr('title') }}</h1>
                <p class="text-slate-100 text-xs md:text-sm">{{ $category->tr('desc') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-8">
            @foreach ($navCategories as $c)
                <a href="{{ route('category.show', $c) }}" class="flex items-center gap-3 p-2.5 rounded-2xl border transition {{ $c->id === $category->id ? 'border-sky-500 bg-sky-50 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                    <img src="{{ \App\Support\Img::url($c->image, 96) }}" width="48" height="48" loading="lazy" class="w-12 h-12 rounded-xl object-cover shrink-0" alt="{{ $c->tr('title') }}">
                    <div class="overflow-hidden">
                        <h4 class="font-black text-xs text-slate-800 truncate">{{ $c->tr('title') }}</h4>
                        <p class="text-[10px] text-slate-500 truncate">{{ $c->tr('desc') }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6 flex flex-wrap gap-3 items-center justify-between">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-slate-700 text-xs"><i class="fa-solid fa-filter text-sky-500"></i> {{ tt('فلتر العمر:', 'Age Filter:') }}</span>
                <a href="{{ route('category.show', $category) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold {{ ! $age ? 'bg-sky-500 text-white' : 'bg-slate-100 text-slate-700' }}">{{ tt('الكل', 'All') }}</a>
                @foreach ($ageGroups as $a)
                    <a href="{{ route('category.age', [$category, \App\Http\Controllers\StoreController::ageSlug($a->key)]) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold {{ $age === $a->key ? 'bg-sky-500 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $a->key }}</a>
                @endforeach
            </div>
            <form method="GET">
                <select name="sort" aria-label="{{ tt('ترتيب حسب', 'Sort by') }}" data-submit-on-change class="border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold bg-slate-50">
                    <option value="default">{{ tt('الترتيب الافتراضي', 'Default Sort') }}</option>
                    <option value="price-low" @selected($sort === 'price-low')>{{ tt('الأرخص أولاً', 'Price: Low to High') }}</option>
                    <option value="price-high" @selected($sort === 'price-high')>{{ tt('الأغلى أولاً', 'Price: High to Low') }}</option>
                </select>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5" data-list-name="{{ $category->tr('title') }}">
            @forelse ($products as $p)
                @include('partials.product-card', ['p' => $p])
            @empty
                <div class="col-span-full bg-white p-10 rounded-2xl text-center font-bold text-slate-500">{{ tt('ماكو ألعاب بهذا الفلتر حالياً.', 'No toys match this filter.') }}</div>
            @endforelse
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    </div>
@endsection
