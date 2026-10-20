@extends('layouts.store', ['noindex' => true, 'active' => 'favorites', 'title' => __('store.myFavToys')])

@section('content')
    <div class="content-90 py-8">
        <div class="bg-sky-50 border border-sky-200 rounded-2xl p-4 mb-6 flex flex-wrap items-center justify-between gap-3 text-xs">
            <span class="font-bold text-slate-700">{{ tt('سجل دخولك عشان تحفظ مفضلتك وتتابع طلباتك من أي جهاز 💙', 'Sign in to keep your wishlist and track orders on any device 💙') }}</span>
            <div class="flex gap-2">
                <a href="{{ route('login') }}" class="bg-sky-500 text-white px-4 py-2 rounded-xl font-bold">{{ __('store.login') }}</a>
                <a href="{{ route('register') }}" class="bg-amber-400 text-slate-900 px-4 py-2 rounded-xl font-bold">{{ __('store.register') }}</a>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="text-lg font-black text-slate-800 mb-6">{{ tt('الألعاب المفضلة لدي (', 'My Saved Wishlist (') }}{{ $favorites->count() }})</h3>
            @if ($favorites->isEmpty())
                <div class="text-center py-12">
                    <div class="text-5xl text-slate-300 mb-3"><i class="fa-regular fa-heart"></i></div>
                    <h4 class="text-base font-bold text-slate-700 mb-1">{{ tt('قائمة المفضلة فاضية الحين', 'Your wishlist is empty') }}</h4>
                    <p class="text-slate-400 text-xs mb-6">{{ tt('اضغط على علامة القلب ❤️ فوق أي لعبة عشان تضيفها هني وترجع لها بأي وقت.', 'Click the heart icon ❤️ on any toy to save it here.') }}</p>
                    <a href="{{ route('products.index') }}" class="bg-sky-500 text-white px-6 py-3 rounded-xl font-bold text-xs inline-block shadow-sm">{{ __('store.browseToys') }}</a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                    @foreach ($favorites as $p)
                        @include('partials.product-card', ['p' => $p])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
