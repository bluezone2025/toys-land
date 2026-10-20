@php
    $productUrl = \App\Support\Seo::canonical();
    $firstCategory = $product->categories->first();
    $schema = [
        array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->tr('name'),
            'description' => \Illuminate\Support\Str::limit(strip_tags((string) $product->tr('desc')), 500),
            'image' => array_map(fn ($u) => \App\Support\Seo::image($u), $product->galleryUrls()),
            'sku' => $product->sku ?: (string) $product->id,
            'url' => $productUrl,
            'brand' => ['@type' => 'Brand', 'name' => \App\Support\Seo::brand()],
            'category' => $firstCategory?->tr('title'),
            'audience' => $product->ageGroups->isNotEmpty() ? ['@type' => 'PeopleAudience', 'suggestedMinAge' => (int) $product->ageGroups->first()->key] : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => $productUrl,
                'price' => number_format((float) $product->price, 3, '.', ''),
                'priceCurrency' => 'KWD',
                'availability' => $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@id' => url('/').'#store'],
            ],
        ]),
        \App\Support\Seo::breadcrumbs(array_values(array_filter([
            [__('store.home'), \App\Support\Seo::url(app()->getLocale(), route('home'))],
            $firstCategory ? [$firstCategory->tr('title'), \App\Support\Seo::url(app()->getLocale(), route('category.show', $firstCategory))] : null,
            [$product->tr('name'), $productUrl],
        ]))),
    ];
@endphp
@extends('layouts.store', [
    'active' => 'products',
    'title' => $product->seo('title', $product->tr('name')),
    'metaDescription' => $product->seo('description', $product->tr('desc')),
    'metaImage' => $product->image,
    'ogType' => 'product',
    'schema' => $schema,
])

@php $rtl = is_rtl(); $gallery = $product->galleryUrls(); @endphp

@section('content')
    <div class="content-90 py-8">
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-sky-600 font-bold mb-6 text-xs">
            <i class="fa-solid {{ $rtl ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i> {{ tt('ردة حق قائمة الألعاب', 'Back to All Toys') }}
        </a>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 lg:p-10 mb-12">
            <form method="POST" action="{{ route('cart.add') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="product-form">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="lg:col-span-5 relative">
                    @if ($product->tr('badge'))
                        <span class="absolute top-3 {{ $rtl ? 'right-3' : 'left-3' }} bg-white/95 text-sky-700 font-bold text-xs px-3 py-1 rounded-full shadow-sm z-10">{{ $product->tr('badge') }}</span>
                    @endif
                    <button type="button" aria-label="{{ __('store.favorites') }}" onclick="toggleFavorite({{ $product->id }}, event)" class="absolute top-3 {{ $rtl ? 'left-3' : 'right-3' }} w-9 h-9 bg-white/95 rounded-full flex items-center justify-center shadow-sm z-10 hover:scale-110 transition cursor-pointer">
                        <i class="fav-icon-{{ $product->id }} {{ \App\Support\Favorites::has($product->id) ? 'fa-solid text-pink-500' : 'fa-regular text-slate-400' }} fa-heart text-sm"></i>
                    </button>
                    <button type="button" onclick="openLightbox(window.currentImage || 0)" class="block w-full cursor-zoom-in group relative" title="{{ tt('تكبير الصورة', 'Zoom image') }}">
                        <img id="main-image" src="{{ $product->image_url }}" class="w-full h-[300px] sm:h-[380px] object-cover rounded-2xl bg-slate-50" alt="{{ $product->tr('name') }}">
                        <span class="absolute bottom-3 {{ $rtl ? 'left-3' : 'right-3' }} bg-white/90 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-full shadow-sm opacity-90 group-hover:opacity-100 transition">
                            <i class="fa-solid fa-magnifying-glass-plus"></i> {{ count($gallery) > 1 ? tt('شوف كل الصور', 'View all photos').' ('.count($gallery).')' : tt('تكبير', 'Zoom') }}
                        </span>
                    </button>
                    @if (count($gallery) > 1)
                        <div class="flex gap-2 mt-3 overflow-x-auto no-scrollbar">
                            @foreach ($gallery as $i => $img)
                                <button type="button" onclick="document.getElementById('main-image').src='{{ $img }}'; window.currentImage = {{ $i }};" class="shrink-0 w-16 h-16 rounded-xl overflow-hidden border border-slate-200 hover:border-sky-500 cursor-pointer"><img src="{{ $img }}" class="w-full h-full object-cover" alt=""></button>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="lg:col-span-7 min-w-0">
                    @if ($product->ageLabel())
                        <span class="bg-sky-50 text-sky-700 px-2.5 py-1 rounded-md text-xs font-bold inline-block mb-2">{{ tt('العمر المناسب: ', 'Recommended Age: ') }}{{ $product->ageLabel() }}</span>
                    @endif
                    <h1 class="text-2xl lg:text-3xl font-black text-slate-800 mb-2 wrap-anywhere">{{ $product->tr('name') }}</h1>
                    <p class="text-slate-600 leading-relaxed mb-5 text-sm wrap-anywhere">{!! nl2br(e($product->tr('desc'))) !!}</p>

                    @if ($editions->isNotEmpty() || $addons->isNotEmpty())
                        <div class="border-t border-b border-slate-100 py-5 space-y-5 mb-5">
                            @if ($editions->isNotEmpty())
                                <div>
                                    <label class="block font-bold text-slate-800 mb-2 text-xs">{{ tt('1. نقي إصدار اللعبة:', '1. Select Edition:') }}</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                                        @foreach ($editions as $i => $o)
                                            <label class="border border-slate-200 rounded-xl p-3 flex items-center gap-2 cursor-pointer transition">
                                                <input type="radio" name="edition" value="{{ $o->id }}" data-extra="{{ $o->price }}" class="option-radio" @checked($i === 0) onchange="calcCustomPrice()">
                                                <span class="font-bold">{{ $o->tr('name') }}@if ($o->price > 0) (+{{ money($o->price) }})@endif</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if ($addons->isNotEmpty())
                                <div>
                                    <label class="block font-bold text-slate-800 mb-2 text-xs">{{ tt('2. إضافات وتغليف الهدايا:', '2. Gift Wrapping & Add-ons:') }}</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                        @foreach ($addons as $o)
                                            <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-200 transition">
                                                <input type="checkbox" name="addons[]" value="{{ $o->id }}" data-extra="{{ $o->price }}" class="option-check" onchange="calcCustomPrice()">
                                                <span>{{ $o->icon }} {{ $o->tr('name') }} (+{{ money($o->price) }})</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <span class="text-slate-400 text-xs block">{{ tt('السعر مع الإضافات:', 'Total Price:') }}</span>
                            <span id="dynamic-price" class="text-2xl lg:text-3xl font-black text-pink-600">{{ money($product->price) }}</span>
                            @if ($product->compare_price > $product->price)
                                <span class="text-slate-400 line-through text-sm {{ $rtl ? 'mr-2' : 'ml-2' }}">{{ money($product->compare_price) }}</span>
                            @endif
                        </div>
                        @if ($product->inStock())
                            <div class="flex items-center gap-3 w-full sm:w-auto">
                                <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50 shrink-0">
                                    <button type="button" onclick="const q=document.getElementById('qty'); q.value=Math.max(1, +q.value-1)" class="px-3.5 py-3 hover:bg-slate-200 font-bold text-xs cursor-pointer">-</button>
                                    <input id="qty" name="qty" type="number" aria-label="{{ tt('الكمية', 'Quantity') }}" min="1" max="{{ $product->stock }}" value="1" class="w-10 text-center bg-transparent font-bold text-xs focus:outline-none">
                                    <button type="button" onclick="const q=document.getElementById('qty'); q.value=Math.min({{ $product->stock }}, +q.value+1)" class="px-3.5 py-3 hover:bg-slate-200 font-bold text-xs cursor-pointer">+</button>
                                </div>
                                <button type="submit" class="w-full sm:w-auto min-w-[220px] bg-sky-500 hover:bg-sky-600 text-white py-3.5 px-6 rounded-xl font-bold text-sm transition cursor-pointer">{{ __('store.addToCart') }} 🛒</button>
                            </div>
                        @else
                            <span class="bg-red-50 text-red-600 font-bold text-sm px-6 py-3.5 rounded-xl">{{ tt('نفدت الكمية حالياً', 'Currently out of stock') }}</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- معرض صور المنتج -->
        <div id="lightbox" data-images='@json($gallery)' class="hidden fixed inset-0 z-[60] bg-slate-950/90 backdrop-blur-sm items-center justify-center p-4 flex-col">
            <button aria-label="{{ tt('إغلاق', 'Close') }}" type="button" onclick="closeLightbox()" class="absolute top-4 {{ $rtl ? 'left-4' : 'right-4' }} w-11 h-11 rounded-full bg-white/15 hover:bg-white text-white hover:text-slate-900 flex items-center justify-center transition cursor-pointer z-10" title="{{ tt('إغلاق', 'Close') }}">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <div class="relative w-full max-w-4xl flex items-center justify-center">
                @if (count($gallery) > 1)
                    <button aria-label="{{ $rtl ? tt('السابق', 'Previous') : tt('التالي', 'Next') }}" type="button" onclick="lightboxStep({{ $rtl ? -1 : 1 }})" class="absolute right-0 sm:-right-14 w-11 h-11 rounded-full bg-white/15 hover:bg-white text-white hover:text-slate-900 flex items-center justify-center transition cursor-pointer z-10"><i class="fa-solid fa-chevron-right"></i></button>
                    <button aria-label="{{ $rtl ? tt('التالي', 'Next') : tt('السابق', 'Previous') }}" type="button" onclick="lightboxStep({{ $rtl ? 1 : -1 }})" class="absolute left-0 sm:-left-14 w-11 h-11 rounded-full bg-white/15 hover:bg-white text-white hover:text-slate-900 flex items-center justify-center transition cursor-pointer z-10"><i class="fa-solid fa-chevron-left"></i></button>
                @endif
                <img id="lightbox-img" src="{{ $gallery[0] ?? '' }}" class="max-h-[75vh] max-w-full rounded-2xl shadow-2xl object-contain select-none" alt="{{ $product->tr('name') }}">
            </div>
            <p id="lightbox-counter" dir="ltr" class="text-white/80 text-xs font-bold mt-3">1 / {{ count($gallery) }}</p>
            @if (count($gallery) > 1)
                <div class="flex gap-2 mt-3 overflow-x-auto no-scrollbar max-w-full px-2">
                    @foreach ($gallery as $img)
                        <button type="button" data-thumb class="shrink-0 w-14 h-14 rounded-xl overflow-hidden ring-sky-400 cursor-pointer"><img src="{{ $img }}" class="w-full h-full object-cover" alt=""></button>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($related->isNotEmpty())
            @include('partials.slider-section', [
                'id' => 'related-slider',
                'title' => tt('ألعاب ثانية يمكن تعجب ولدك أو بنتك', 'You May Also Like'),
                'sub' => null,
                'products' => $related,
                'wrapClass' => 'pt-4',
            ])
        @endif
    </div>
@endsection

@push('scripts')
<script>
    const basePriceKD = {{ (float) $product->price }};
    const rate = {{ (float) \App\Support\Shop::country()->rate }};
    const symbol = @json(\App\Support\Shop::country()->tr('symbol'));
    function calcCustomPrice() {
        let extra = 0;
        document.querySelectorAll('#product-form .option-radio:checked, #product-form .option-check:checked').forEach(el => extra += parseFloat(el.dataset.extra) || 0);
        document.getElementById('dynamic-price').innerText = ((basePriceKD + extra) * rate).toFixed(2) + ' ' + symbol;
    }
    document.addEventListener('DOMContentLoaded', calcCustomPrice);
</script>
@endpush
