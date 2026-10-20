@php
    $schema = [
        \App\Support\Seo::organization(),
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => \App\Support\Seo::brand(),
            'url' => url('/'),
            'inLanguage' => app()->getLocale(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('products.index').'?search={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];
@endphp
@extends('layouts.store', ['active' => 'home', 'title' => null, 'schema' => $schema, 'metaImage' => $slides->first()?->image, 'preloadImage' => $slides->first()?->image_url])

@php
    $rtl = is_rtl();
    $gradDir = $rtl ? 'bg-gradient-to-l' : 'bg-gradient-to-r';
    $themes = [
        'slate' => ['overlay' => 'from-slate-950/90 via-slate-900/60', 'badge' => 'bg-amber-400 text-slate-900', 'text' => 'text-slate-200', 'btn' => 'bg-sky-500 hover:bg-sky-600 text-white font-bold'],
        'sky' => ['overlay' => 'from-sky-950/90 via-sky-900/60', 'badge' => 'bg-pink-500 text-white', 'text' => 'text-sky-100', 'btn' => 'bg-pink-500 hover:bg-pink-600 text-white font-bold'],
        'indigo' => ['overlay' => 'from-indigo-950/90 via-indigo-900/60', 'badge' => 'bg-emerald-400 text-slate-900', 'text' => 'text-indigo-100', 'btn' => 'bg-amber-400 hover:bg-amber-500 text-slate-900 font-black'],
    ];
    $ageColors = [
        'pink' => ['hover:bg-pink-50', 'bg-pink-100 text-pink-600'],
        'amber' => ['hover:bg-amber-50', 'bg-amber-100 text-amber-600'],
        'sky' => ['hover:bg-sky-50', 'bg-sky-100 text-sky-600'],
        'purple' => ['hover:bg-purple-50', 'bg-purple-100 text-purple-600'],
        'emerald' => ['hover:bg-emerald-50', 'bg-emerald-100 text-emerald-600'],
        'indigo' => ['hover:bg-indigo-50', 'bg-indigo-100 text-indigo-600'],
    ];
    $prevIcon = $rtl ? 'fa-chevron-right' : 'fa-chevron-left';
    $nextIcon = $rtl ? 'fa-chevron-left' : 'fa-chevron-right';
@endphp

@section('content')
    @if ($slides->isNotEmpty())
        <section class="relative w-full h-[360px] md:h-[480px] overflow-hidden bg-slate-900 mb-10 shadow-sm">
            <div id="promo-hero-slider" class="relative w-full h-full">
                @foreach ($slides as $i => $s)
                    @php $th = $themes[$s->theme] ?? $themes['slate']; @endphp
                    <div class="hero-slide {{ $i === 0 ? 'slide-active' : 'slide-hidden' }} absolute inset-0">
                        <img src="{{ $s->image_url }}" class="w-full h-full object-cover" alt="{{ $s->tr('title') }}" width="1800" height="480" @if ($i === 0) fetchpriority="high" loading="eager" decoding="async" @else loading="lazy" decoding="async" @endif>
                        <div class="absolute inset-0 {{ $gradDir }} {{ $th['overlay'] }} to-transparent flex items-center">
                            <div class="content-90">
                                <div class="max-w-xl text-white">
                                    @if ($s->tr('badge'))
                                        <span class="{{ $th['badge'] }} px-3.5 py-1 rounded-full text-xs font-black inline-block mb-3">{{ $s->tr('badge') }}</span>
                                    @endif
                                    @if ($i === 0)
                                        <h1 class="text-2xl md:text-5xl font-black mb-4 leading-tight">{{ $s->tr('title') }}</h1>
                                    @else
                                        <h2 class="text-2xl md:text-5xl font-black mb-4 leading-tight">{{ $s->tr('title') }}</h2>
                                    @endif
                                    <p class="text-sm md:text-base {{ $th['text'] }} mb-6">{{ $s->tr('desc') }}</p>
                                    @if ($s->tr('btn'))
                                        <a href="{{ $s->link ? (str_starts_with($s->link, '/') ? url($s->link) : $s->link) : route('products.index') }}" class="{{ $th['btn'] }} px-7 py-3.5 rounded-xl shadow-md transition inline-block text-sm">{{ $s->tr('btn') }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($slides->count() > 1)
                <button aria-label="{{ tt('السابق', 'Previous') }}" onclick="changeHeroSlide(-1)" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-white/25 hover:bg-white text-white hover:text-slate-900 backdrop-blur flex items-center justify-center transition cursor-pointer"><i class="fa-solid fa-chevron-right text-sm"></i></button>
                <button aria-label="{{ tt('التالي', 'Next') }}" onclick="changeHeroSlide(1)" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-white/25 hover:bg-white text-white hover:text-slate-900 backdrop-blur flex items-center justify-center transition cursor-pointer"><i class="fa-solid fa-chevron-left text-sm"></i></button>
            @endif
        </section>
    @endif

    <div class="content-90">
        @if ($ageGroups->isNotEmpty())
            <section class="w-full mb-10">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-800">{{ setting_tr('home_age_title', __('store.ageSecTitle')) }}</h2>
                        <p class="text-slate-500 text-xs">{{ setting_tr('home_age_sub', __('store.ageSecSub')) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button aria-label="{{ tt('السابق', 'Previous') }}" onclick="scrollSlider('age-slider', 1)" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-sky-500 hover:text-white transition cursor-pointer"><i class="fa-solid {{ $prevIcon }} text-xs"></i></button>
                        <button aria-label="{{ tt('التالي', 'Next') }}" onclick="scrollSlider('age-slider', -1)" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-sky-500 hover:text-white transition cursor-pointer"><i class="fa-solid {{ $nextIcon }} text-xs"></i></button>
                    </div>
                </div>
                <div id="age-slider" class="flex gap-4 overflow-x-auto no-scrollbar pb-2 snap-x">
                    @foreach ($ageGroups as $a)
                        @php $ac = $ageColors[$a->color] ?? $ageColors['sky']; @endphp
                        <a href="{{ route('products.age', \App\Http\Controllers\StoreController::ageSlug($a->key)) }}" class="min-w-[240px] sm:min-w-[270px] flex-1 snap-center bg-white {{ $ac[0] }} p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3 transition">
                            <div class="w-12 h-12 rounded-xl {{ $ac[1] }} flex items-center justify-center text-lg font-black shrink-0">{{ $a->key }}</div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm">{{ $a->tr('title') }}</h3>
                                <p class="text-[11px] text-slate-500">{{ $a->tr('desc') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="w-full mb-12">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-xl font-black text-slate-800">{{ setting_tr('home_cats_title', __('store.catsSecTitle')) }}</h2>
                    <p class="text-slate-500 text-xs">{{ setting_tr('home_cats_sub', __('store.catsSecSub')) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button aria-label="{{ tt('السابق', 'Previous') }}" onclick="scrollSlider('cats-slider', 1)" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-sky-500 hover:text-white transition cursor-pointer"><i class="fa-solid {{ $prevIcon }} text-xs"></i></button>
                    <button aria-label="{{ tt('التالي', 'Next') }}" onclick="scrollSlider('cats-slider', -1)" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-sky-500 hover:text-white transition cursor-pointer"><i class="fa-solid {{ $nextIcon }} text-xs"></i></button>
                    <a href="{{ route('products.index') }}" class="text-sky-600 font-bold text-xs hover:underline">{{ __('store.viewAll') }}</a>
                </div>
            </div>
            <div id="cats-slider" class="flex gap-4 overflow-x-auto no-scrollbar pb-3 snap-x">
                @foreach ($navCategories as $c)
                    @include('partials.category-card', ['c' => $c])
                @endforeach
            </div>
        </section>

        @include('partials.slider-section', [
            'id' => 'slider-best',
            'title' => setting_tr('home_best_title', __('store.bestSecTitle')),
            'sub' => setting_tr('home_best_sub', __('store.bestSecSub')),
            'products' => $bestSellers,
            'viewAll' => route('products.index'),
        ])

        @if ($eduProducts->isNotEmpty())
            @include('partials.slider-section', [
                'id' => 'slider-edu',
                'title' => setting_tr('home_edu_title', __('store.eduSecTitle')),
                'sub' => setting_tr('home_edu_sub', __('store.eduSecSub')),
                'products' => $eduProducts,
                'viewAll' => $eduLink,
                'wrapClass' => 'w-full px-4 sm:px-8 py-10 bg-sky-50/70 rounded-3xl mb-12',
            ])
        @endif

        @if ($funProducts->isNotEmpty())
            @include('partials.slider-section', [
                'id' => 'slider-fun',
                'title' => setting_tr('home_fun_title', __('store.funSecTitle')),
                'sub' => setting_tr('home_fun_sub', __('store.funSecSub')),
                'products' => $funProducts,
                'viewAll' => $funLink,
                'wrapClass' => 'w-full mb-14',
            ])
        @endif

        <section id="all-toys-catalog" class="w-full px-4 sm:px-8 py-12 bg-white rounded-3xl border border-slate-100 shadow-sm">
            <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl font-black text-slate-800">{{ setting_tr('home_catalog_title', __('store.catalogSecTitle')) }}</h2>
                    <p class="text-slate-500 text-xs mt-1">{{ setting_tr('home_catalog_sub', __('store.catalogSecSub')) }}</p>
                </div>
                <a href="{{ route('products.index') }}" class="mt-3 md:mt-0 bg-slate-100 hover:bg-slate-200 text-slate-800 px-5 py-2.5 rounded-xl font-bold text-xs transition">{{ __('store.openFullFilter') }}</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                @foreach ($catalog as $p)
                    @include('partials.product-card', ['p' => $p])
                @endforeach
            </div>
        </section>

        <section class="w-full mt-12 bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-10">
            <h2 class="text-xl font-black text-slate-800 mb-3">{{ setting_tr('home_intro_title', tt('تويز لاند: ألعاب ذكية وآمنة لعيالكم في الكويت والخليج', 'Toys Land: smart, safe toys for kids in Kuwait & the GCC')) }}</h2>
            <div class="text-sm text-slate-600 leading-loose page-body">{!! nl2br(e(setting_tr('home_intro', tt(
                'في تويز لاند نختار لكم ألعاب ذكية تفتح مخ عيالكم وتنمي إبداعهم: ألعاب العلوم والتجارب، الروبوتات، مكعبات التركيب، سيارات الريموت كنترول، الدمى والدباديب الناعمة، والألعاب الحركية والخارجية لكل الأعمار من سنة إلى 12 سنة. كل الألعاب أصلية وآمنة 100% ومطابقة لمعايير السلامة، مع تغليف هدايا فاخر وتوصيل سريع داخل الكويت ولكل دول الخليج، والدفع آمن عن طريق كي نت وفيزا وأبل باي أو عند الاستلام.',
                'At Toys Land we pick smart toys that spark your children\'s minds and creativity: science kits, robots, building blocks, remote-control cars, soft dolls and teddies, and active outdoor toys for every age from 1 to 12. Every toy is authentic and 100% safe, with luxury gift wrapping, fast delivery across Kuwait and the GCC, and secure payment by KNET, Visa, Apple Pay or cash on delivery.'
            )))) !!}</div>
        </section>
    </div>
@endsection
