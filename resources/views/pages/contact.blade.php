@extends('layouts.store', ['active' => 'contact', 'title' => __('store.contact'), 'metaDescription' => tt('تواصل مع ', 'Contact ').\App\Support\Seo::brand().' — '.setting('phone').' — '.setting_tr('address', ''), 'schema' => [\App\Support\Seo::organization()]])

@php
    $whatsapp = preg_replace('/\D/', '', (string) setting('whatsapp', '96599999999'));
    $input = 'w-full border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-sky-500';
@endphp

@section('content')
    <div class="content-90 py-8">
        <div class="mb-8">
            <span class="bg-sky-100 text-sky-700 px-3 py-1 rounded-full text-xs font-black inline-block mb-2">{{ tt('نرد عليك بأسرع وقت 📞', 'Get In Touch 📞') }}</span>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-800">{{ tt('تواصل معانا وموقع معرضنا', 'Contact Us & Showroom Location') }}</h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">{{ tt('نسعد بخدمتكم والرد على جميع استفساراتكم وطلباتكم الخاصة على مدار الساعة', 'We are happy to assist you with orders, gift inquiries, and support') }}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3.5">
                <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-phone-volume"></i></div>
                <div><h3 class="font-bold text-slate-800 text-xs">{{ tt('الخط الساخن المباشر', 'Direct Hotline') }}</h3><a href="tel:{{ preg_replace('/[^\d+]/', '', (string) setting('phone', '+96522225555')) }}" class="text-sky-600 font-black text-sm dir-ltr block mt-0.5">{{ setting('phone', '+965 2222 5555') }}</a></div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3.5">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-2xl shrink-0"><i class="fa-brands fa-whatsapp"></i></div>
                <div><h3 class="font-bold text-slate-800 text-xs">{{ tt('واتساب خدمة العملاء', 'WhatsApp Support') }}</h3><a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="text-emerald-600 font-black text-sm dir-ltr block mt-0.5">+{{ $whatsapp }}</a></div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3.5">
                <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-xl flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-location-dot"></i></div>
                <div><h3 class="font-bold text-slate-800 text-xs">{{ tt('عنوان المعرض الرئيسي', 'Showroom Address') }}</h3><p class="text-slate-700 font-bold text-xs mt-0.5">{{ setting_tr('address', tt('الكويت - الري - الأفنيوز مول', 'Kuwait - Al-Rai - The Avenues')) }}</p></div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3.5">
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl shrink-0"><i class="fa-regular fa-clock"></i></div>
                <div><h3 class="font-bold text-slate-800 text-xs">{{ tt('ساعات عمل المعرض', 'Working Hours') }}</h3><p class="text-slate-700 font-bold text-xs mt-0.5">{{ setting_tr('hours', tt('يومياً: 10 صباحاً - 11 مساءً', 'Daily: 10:00 AM - 11:00 PM')) }}</p></div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-white p-6 lg:p-8 rounded-3xl shadow-sm border border-slate-100">
            <div class="lg:col-span-6">
                <h3 class="text-lg font-black text-slate-800 mb-2">{{ tt('دز لنا رسالة أو استفسار مباشر', 'Send Us a Direct Message') }}</h3>
                <p class="text-xs text-slate-400 mb-5">{{ tt('عب البيانات تحت وراح يتواصل معاك فريقنا بأقرب وقت', 'Fill out the form below and our team will get back to you shortly') }}</p>
                @if ($errors->any())
                    <div class="bg-red-50 text-red-600 rounded-xl p-3 mb-4 text-xs font-bold">@foreach ($errors->all() as $e)<p>• {{ $e }}</p>@endforeach</div>
                @endif
                <form method="POST" action="{{ route('contact.store') }}" class="space-y-3.5 text-xs">
                    @csrf
                    <div class="hidden"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div><label class="block font-bold text-slate-700 mb-1">{{ tt('الاسم الكامل *', 'Full Name *') }}</label><input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="120" class="{{ $input }}"></div>
                        <div><label class="block font-bold text-slate-700 mb-1">{{ tt('رقم النقال *', 'Mobile Number *') }}</label><input type="tel" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required maxlength="20" class="{{ $input }}"></div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">{{ tt('موضوع الرسالة', 'Inquiry Type') }}</label>
                        <select name="subject" class="{{ $input }} bg-white">
                            <option>{{ tt('استفسار عن طلب قائم', 'Order Inquiry') }}</option>
                            <option>{{ tt('طلب تجهيز هدايا خاص أو كميات', 'Special Gift / Bulk Order') }}</option>
                            <option>{{ tt('مقترح أو ملاحظة', 'Suggestion or Feedback') }}</option>
                        </select>
                    </div>
                    <div><label class="block font-bold text-slate-700 mb-1">{{ tt('تفاصيل الرسالة *', 'Message *') }}</label><textarea name="message" rows="4" required maxlength="3000" class="{{ $input }}">{{ old('message') }}</textarea></div>
                    <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white px-6 py-3.5 rounded-xl font-black cursor-pointer shadow-sm transition">{{ tt('إرسال الرسالة الآن 🚀', 'Send Message 🚀') }}</button>
                </form>
            </div>
            <div class="lg:col-span-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-800 mb-2">{{ tt('لوكيشن المعرض على الخريطة', 'Showroom Map Location') }}</h3>
                    <p class="text-xs text-slate-400 mb-4">{{ setting_tr('map_note', tt('حياكم بمعرضنا في الأفنيوز مول - المرحلة الرابعة - قسم الأطفال', 'Visit us at The Avenues Mall - Phase 4 - Kids Zone')) }}</p>
                </div>
                <div class="rounded-2xl overflow-hidden border border-slate-200 h-80 lg:h-full min-h-[280px] shadow-inner">
                    <iframe width="100%" height="100%" frameborder="0" loading="lazy" src="https://maps.google.com/maps?q={{ urlencode(setting('map_query', 'The Avenues Kuwait')) }}&t=&z=14&ie=UTF8&iwloc=&output=embed"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection
