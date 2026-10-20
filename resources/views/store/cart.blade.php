@extends('layouts.store', ['active' => 'cart', 'title' => __('store.cartTitle')])

@section('content')
    <div class="content-90 py-8">
        @if ($items->isEmpty())
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-12 text-center max-w-xl mx-auto my-6">
                <div class="text-5xl text-slate-300 mb-4"><i class="fa-solid fa-bag-shopping"></i></div>
                <h2 class="text-2xl font-black text-slate-800 mb-2">{{ __('store.emptyCart') }}</h2>
                <p class="text-slate-500 text-xs sm:text-sm mb-6">{{ __('store.emptyCartSub') }}</p>
                <a href="{{ route('products.index') }}" class="bg-sky-500 hover:bg-sky-600 text-white px-8 py-3.5 rounded-xl font-bold text-xs sm:text-sm inline-block shadow-sm transition">{{ __('store.browseToys') }}</a>
            </div>
        @else
            <h1 class="text-2xl lg:text-3xl font-black text-slate-800 mb-6">{{ __('store.cartTitle') }}</h1>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-100 p-5 sm:p-6 divide-y divide-slate-100">
                    @foreach ($items as $item)
                        <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5 w-full sm:w-auto min-w-0">
                                <img src="{{ $item->product->image_url }}" class="w-20 h-20 rounded-xl object-cover bg-slate-50 border border-slate-100 shrink-0" alt="">
                                <div>
                                    <a href="{{ route('products.show', $item->product) }}" class="font-bold text-base text-slate-800 hover:text-sky-600 transition block wrap-anywhere">{{ $item->product->tr('name') }}</a>
                                    <p class="text-[11px] text-slate-500 mb-1.5">{{ \App\Support\Cart::optionsLabel($item->options, app()->getLocale(), $item->product) }}</p>
                                    <span class="text-pink-600 font-black text-sm">{{ money($item->unit_price) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-4 border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100">
                                <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                    <form method="POST" action="{{ route('cart.update', $item->key) }}">@csrf @method('PATCH')<input type="hidden" name="delta" value="-1"><button class="px-3.5 py-1.5 hover:bg-slate-200 font-bold text-xs cursor-pointer transition">-</button></form>
                                    <span class="px-3.5 font-bold text-slate-800 text-xs">{{ $item->qty }}</span>
                                    <form method="POST" action="{{ route('cart.update', $item->key) }}">@csrf @method('PATCH')<input type="hidden" name="delta" value="1"><button class="px-3.5 py-1.5 hover:bg-slate-200 font-bold text-xs cursor-pointer transition">+</button></form>
                                </div>
                                <form method="POST" action="{{ route('cart.remove', $item->key) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:bg-red-50 p-2.5 rounded-xl text-sm cursor-pointer transition" title="{{ tt('حذف', 'Delete') }}"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sticky top-24">
                    <h3 class="font-black text-base text-slate-800 mb-4 border-b border-slate-100 pb-3">{{ __('store.orderSummary') }}</h3>
                    <div class="space-y-3 text-xs mb-6">
                        <div class="flex justify-between text-slate-600"><span>{{ __('store.itemsCount') }}</span><span class="font-bold text-slate-800">{{ $count }} {{ __('store.pieces') }}</span></div>
                        <div class="flex justify-between text-slate-600"><span>{{ __('store.shippingFee') }}</span>
                            <span class="{{ $shipping > 0 ? 'text-slate-800' : 'text-emerald-600' }} font-bold">{{ $shipping > 0 ? money($shipping) : __('store.free') }}</span>
                        </div>
                        <div class="border-t border-slate-100 pt-3 flex justify-between items-center text-base font-black text-slate-800">
                            <span>{{ __('store.total') }}:</span>
                            <span class="text-pink-600 text-2xl">{{ money($subtotal + $shipping) }}</span>
                        </div>
                    </div>
                    @include('partials.promo-coupon', ['class' => 'mb-4'])
                    <a href="{{ route('checkout') }}" class="w-full block text-center bg-sky-500 hover:bg-sky-600 text-white py-3.5 rounded-xl font-bold text-sm shadow-sm transition mb-3">{{ __('store.checkoutBtn') }}</a>
                    <a href="{{ route('products.index') }}" class="w-full block text-center bg-slate-100 hover:bg-slate-200 text-slate-700 py-3 rounded-xl font-bold text-xs transition">{{ __('store.continueShopping') }}</a>
                </div>
            </div>
        @endif
    </div>
@endsection
