@extends('layouts.store', [
    'active' => $page->slug,
    'title' => $page->seo('title', $page->tr('title')),
    'metaDescription' => $page->seo('description', $page->tr('subtitle') ?: collect($page->cards ?? [])->map(fn ($c) => \App\Models\Page::rowTr($c, 'body'))->implode(' ')),
    'metaImage' => $page->image,
])

@php
    use App\Models\Page;
    $colors = [
        'sky' => ['text-sky-500', 'bg-sky-50 text-sky-600'],
        'pink' => ['text-pink-500', 'bg-pink-50 text-pink-600'],
        'amber' => ['text-amber-500', 'bg-amber-50 text-amber-600'],
        'emerald' => ['text-emerald-500', 'bg-emerald-50 text-emerald-600'],
        'purple' => ['text-purple-500', 'bg-purple-50 text-purple-600'],
        'indigo' => ['text-indigo-500', 'bg-indigo-50 text-indigo-600'],
    ];
    $badgeColors = ['privacy' => 'bg-emerald-100 text-emerald-800', 'terms' => 'bg-amber-100 text-amber-800', 'shipping' => 'bg-sky-100 text-sky-800'];
    $cards = $page->cards ?? [];
    $stats = $page->stats ?? [];
@endphp

@section('content')
    <div class="content-90 py-8">
        @if ($page->slug === 'about')
            <div class="relative rounded-3xl overflow-hidden bg-slate-900 text-white mb-10 shadow-md h-64 sm:h-80 flex items-center">
                @if ($page->image_url)
                    <img src="{{ $page->image_url }}" class="absolute inset-0 w-full h-full object-cover opacity-40" alt="{{ $page->tr('title') }}">
                @endif
                <div class="relative z-10 px-6 lg:px-14 max-w-2xl">
                    @if ($page->tr('badge'))<span class="bg-amber-400 text-slate-950 px-3.5 py-1 rounded-full text-xs font-black inline-block mb-3">{{ $page->tr('badge') }}</span>@endif
                    <h1 class="text-2xl sm:text-4xl font-black mb-3">{{ $page->tr('title') }}</h1>
                    <p class="text-slate-200 text-xs sm:text-sm leading-relaxed">{{ $page->tr('subtitle') }}</p>
                </div>
            </div>

            @if ($stats)
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
                    @foreach ($stats as $s)
                        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm text-center">
                            <div class="text-3xl font-black {{ $colors[$s['color'] ?? 'sky'][0] }} mb-1 dir-ltr">{{ $s['value'] ?? '' }}</div>
                            <p class="text-xs font-bold text-slate-600">{{ Page::rowTr($s, 'label') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($cards)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    @foreach ($cards as $c)
                        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                            <div class="w-12 h-12 rounded-2xl {{ $colors[$c['color'] ?? 'sky'][1] }} flex items-center justify-center text-2xl mb-4"><i class="{{ $c['icon'] ?? 'fa-solid fa-star' }}"></i></div>
                            <h3 class="font-black text-base text-slate-800 mb-2">{{ Page::rowTr($c, 'title') }}</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">{{ Page::rowTr($c, 'body') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($page->tr('body'))
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-10 text-sm text-slate-600 leading-relaxed page-body">{!! $page->tr('body') !!}</div>
            @endif
        @else
            <div class="max-w-4xl mx-auto bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-10">
                <div class="border-b border-slate-100 pb-6 mb-6">
                    @if ($page->tr('badge'))<span class="{{ $badgeColors[$page->slug] ?? 'bg-sky-100 text-sky-800' }} px-3 py-1 rounded-full text-xs font-black inline-block mb-2">{{ $page->tr('badge') }}</span>@endif
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-800">{{ $page->tr('title') }}</h1>
                    @if ($page->tr('subtitle'))<p class="text-slate-500 text-xs mt-2">{{ $page->tr('subtitle') }}</p>@endif
                </div>

                @if ($page->slug === 'shipping')
                    <div class="overflow-x-auto border border-slate-200 rounded-2xl mb-6">
                        <table class="w-full text-xs {{ is_rtl() ? 'text-right' : 'text-left' }}">
                            <thead class="bg-slate-100 text-slate-800 font-black">
                                <tr>
                                    <th class="p-3.5">{{ tt('الدولة / الوجهة', 'Destination') }}</th>
                                    <th class="p-3.5">{{ tt('مدة التوصيل', 'Delivery Time') }}</th>
                                    <th class="p-3.5">{{ tt('رسوم التوصيل', 'Shipping Fee') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @php $threshold = (float) setting('free_shipping_threshold', 15); @endphp
                                @foreach ($countries as $c)
                                    <tr>
                                        <td class="p-3.5 font-bold">{{ $c->flag }} {{ $c->tr('name') }}</td>
                                        <td class="p-3.5">{{ $c->tr('delivery') }}</td>
                                        <td class="p-3.5 font-bold {{ (float) $c->shipping_fee == 0 ? 'text-emerald-600' : '' }}">
                                            @if ((float) $c->shipping_fee == 0)
                                                {{ tt('مجاناً', 'FREE') }}
                                            @else
                                                {{ \App\Support\Shop::price($c->shipping_fee, $c) }}@if ($threshold > 0) — <span class="text-emerald-600 font-black">{{ tt('مجاناً للطلبات فوق ', 'FREE over ') }}{{ \App\Support\Shop::price($threshold, $c) }}</span>@endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($cards)
                    @if ($page->slug === 'terms')
                        <div class="space-y-5 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            @foreach ($cards as $c)
                                <div class="p-5 rounded-2xl border border-slate-100 bg-slate-50/60">
                                    <h3 class="font-black text-slate-800 text-base mb-1.5">@if (! empty($c['icon']))<i class="{{ $c['icon'] }} {{ $colors[$c['color'] ?? 'sky'][0] }}"></i> @endif{{ Page::rowTr($c, 'title') }}</h3>
                                    <p>{{ Page::rowTr($c, 'body') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            @foreach ($cards as $c)
                                <div class="p-5 rounded-2xl border border-slate-100 bg-slate-50/60">
                                    <i class="{{ $c['icon'] ?? 'fa-solid fa-circle-info' }} text-2xl {{ $colors[$c['color'] ?? 'sky'][0] }} mb-3"></i>
                                    <h3 class="font-black text-slate-800 text-base mb-1.5">{{ Page::rowTr($c, 'title') }}</h3>
                                    <p>{{ Page::rowTr($c, 'body') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                @if ($page->tr('body'))
                    <div class="text-xs sm:text-sm text-slate-600 leading-relaxed page-body {{ $cards ? 'mt-6' : '' }}">{!! $page->tr('body') !!}</div>
                @endif
            </div>
        @endif
    </div>
@endsection
