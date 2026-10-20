@extends('layouts.store', ['noindex' => true, 'active' => 'register', 'title' => __('store.register'), 'showWelcome' => false])

@section('content')
    <div class="content-90 py-12 flex justify-center">
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8 w-full max-w-sm">
            <h1 class="text-xl font-black mb-5 text-center">{{ __('store.register') }}</h1>
            @if ($errors->any())
                <div class="bg-red-50 text-red-600 rounded-xl p-3 mb-4 text-xs font-bold space-y-1">@foreach ($errors->all() as $e)<p>• {{ $e }}</p>@endforeach</div>
            @endif
            <form method="POST" action="{{ route('register') }}" class="space-y-3.5 text-xs">
                @csrf
                <div><label class="block font-bold mb-1">{{ tt('الاسم الكامل', 'Full Name') }}</label><input name="name" type="text" value="{{ old('name') }}" required class="w-full border border-slate-200 rounded-xl p-2.5"></div>
                <div><label class="block font-bold mb-1">{{ tt('البريد الإلكتروني', 'Email') }}</label><input name="email" type="email" value="{{ old('email') }}" required class="w-full border border-slate-200 rounded-xl p-2.5"></div>
                <div><label class="block font-bold mb-1">{{ tt('رقم النقال', 'Mobile Number') }}</label><input name="phone" type="tel" value="{{ old('phone') }}" class="w-full border border-slate-200 rounded-xl p-2.5"></div>
                <div><label class="block font-bold mb-1">{{ tt('كلمة المرور', 'Password') }}</label><input name="password" type="password" required class="w-full border border-slate-200 rounded-xl p-2.5"></div>
                <div><label class="block font-bold mb-1">{{ tt('تأكيد كلمة المرور', 'Confirm Password') }}</label><input name="password_confirmation" type="password" required class="w-full border border-slate-200 rounded-xl p-2.5"></div>
                <button class="w-full bg-sky-500 hover:bg-sky-600 text-white py-3 rounded-xl font-bold cursor-pointer">{{ __('store.register') }}</button>
            </form>
            <p class="text-center mt-5 text-[11px]">{{ tt('عندك حساب؟', 'Already have an account?') }} <a href="{{ route('login') }}" class="text-sky-600 font-bold underline">{{ __('store.login') }}</a></p>
        </div>
    </div>
@endsection
