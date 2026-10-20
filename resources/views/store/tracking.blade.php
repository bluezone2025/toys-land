@extends('layouts.store', ['noindex' => true, 'active' => 'tracking', 'title' => __('store.liveTracking')])

@php $rtl = is_rtl(); @endphp

@section('content')
    <div class="content-90 py-8">
        @if (! $order)
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 lg:p-10 max-w-xl mx-auto">
                <h1 class="text-xl lg:text-2xl font-black text-slate-800 mb-2">{{ tt('تتبع حالة طلبك المباشر 🛵', 'Live Order Tracking 🛵') }}</h1>
                <p class="text-slate-500 text-xs mb-6">{{ tt('اكتب رقم الطلب ورقم الهاتف اللي سجلت فيه الطلب', 'Enter your order number and the phone number used at checkout') }}</p>
                @if ($notFound)
                    <p class="bg-red-50 text-red-600 text-xs font-bold rounded-xl p-3 mb-4">{{ tt('ما لقينا طلب بهالبيانات، تأكد من الرقم وحاول مرة ثانية.', 'No order matches these details. Please check and try again.') }}</p>
                @endif
                <form method="GET" class="space-y-3.5 text-xs">
                    <div><label for="f-order" class="block font-bold text-slate-700 mb-1.5">{{ tt('رقم الطلب', 'Order Number') }}</label><input id="f-order" name="order" value="{{ request('order') }}" required placeholder="TOY-123456" class="w-full border border-slate-200 rounded-xl p-3 uppercase focus:outline-none focus:border-sky-500"></div>
                    <div><label for="f-phone" class="block font-bold text-slate-700 mb-1.5">{{ tt('رقم الهاتف', 'Phone Number') }}</label><input id="f-phone" name="phone" value="{{ request('phone') }}" required class="w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-sky-500"></div>
                    <button class="w-full bg-sky-500 hover:bg-sky-600 text-white py-3 rounded-xl font-bold cursor-pointer">{{ tt('تتبع الطلب', 'Track Order') }}</button>
                </form>
            </div>
        @else
            @php
                $curr = $order->trackingStep();
                $steps = [
                    ['emoji' => '📝', 'title' => tt('تم استلام الطلب', 'Order Received'), 'desc' => tt('سجلنا طلبك وراجعناه بنجاح.', 'Order registered & verified.')],
                    ['emoji' => '🎁', 'title' => tt('قيد التحضير والتغليف', 'Preparing & Wrapping'), 'desc' => tt('يتم تجهيز الألعاب وتغليف الهدايا الآن.', 'Wrapping your toys with care.')],
                    ['emoji' => '🚚', 'title' => tt('في الطريق إليك', 'Out for Delivery'), 'desc' => tt('مندوب التوصيل في طريقه لعنوانك.', 'Driver is on the way to you.')],
                    ['emoji' => '✅', 'title' => tt('تم الاستلام', 'Delivered'), 'desc' => tt('تم تسليم الطلب بنجاح، عليهم بالعافية!', 'Delivered! Enjoy your toys!')],
                ];
            @endphp
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 lg:p-10">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 border-b border-slate-100 pb-5 gap-3">
                    <div>
                        <h1 class="text-xl lg:text-2xl font-black text-slate-800">{{ tt('تتبع حالة طلبك المباشر 🛵', 'Live Order Tracking 🛵') }}</h1>
                        <p class="text-sky-600 font-bold text-xs mt-1">{{ tt('رقم الطلب: #', 'Order ID: #') }}{{ $order->number }}</p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('invoice.show', $order) }}" class="px-4 py-2 bg-slate-100 rounded-xl text-[11px] font-bold hover:bg-sky-100">🧾 {{ tt('عرض الفاتورة', 'View Invoice') }}</a>
                    </div>
                </div>
                @if ($order->status === 'cancelled')
                    <div class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
                        <div class="text-4xl mb-2">❌</div>
                        <h3 class="font-black text-red-700">{{ tt('تم إلغاء هذا الطلب', 'This order has been cancelled') }}</h3>
                        <p class="text-xs text-red-600 mt-1">{{ tt('للاستفسار تواصل معانا عبر الواتساب', 'Contact us on WhatsApp for any questions') }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        @foreach ($steps as $i => $s)
                            @php $n = $i + 1; $isDone = $n <= $curr; $isCurrent = $n === $curr; @endphp
                            <div class="p-5 rounded-2xl border transition {{ $isCurrent ? 'border-sky-500 bg-sky-50/50 shadow-sm' : ($isDone ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-100 opacity-50') }}">
                                <div class="text-3xl mb-3">{{ $s['emoji'] }}</div>
                                <h3 class="font-bold text-base text-slate-800 mb-1">{{ $s['title'] }}</h3>
                                <p class="text-xs text-slate-500 mb-3">{{ $s['desc'] }}</p>
                                @if ($isDone)
                                    <span class="inline-block bg-emerald-600 text-white text-[10px] px-2.5 py-0.5 rounded-full font-bold">{{ tt('مكتمل ✓', 'Completed ✓') }}</span>
                                @else
                                    <span class="text-[10px] text-slate-500 font-bold">{{ tt('بالانتظار', 'Pending') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
