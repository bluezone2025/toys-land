@extends('layouts.store', ['active' => 'login', 'title' => __('store.login'), 'showWelcome' => false])

@section('content')
    <div class="content-90 py-12 flex justify-center">
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8 w-full max-w-sm">
            <h1 class="text-xl font-black mb-5 text-center">{{ __('store.login') }}</h1>
            @if ($errors->any())
                <div class="bg-red-50 text-red-600 rounded-xl p-3 mb-4 text-xs font-bold">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login') }}" class="space-y-3.5 text-xs">
                @csrf
                <div><label class="block font-bold mb-1">{{ tt('البريد الإلكتروني', 'Email') }}</label><input name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full border border-slate-200 rounded-xl p-2.5"></div>
                <div><label class="block font-bold mb-1">{{ tt('كلمة المرور', 'Password') }}</label><input name="password" type="password" required class="w-full border border-slate-200 rounded-xl p-2.5"></div>
                <label class="flex items-center gap-2 text-slate-600"><input type="checkbox" name="remember" value="1"> {{ tt('تذكرني', 'Remember me') }}</label>
                <button class="w-full bg-sky-500 hover:bg-sky-600 text-white py-3 rounded-xl font-bold cursor-pointer">{{ __('store.login') }}</button>
            </form>
            <p class="text-center mt-5 text-[11px]">{{ tt('ما عندك حساب؟', 'New customer?') }} <a href="{{ route('register') }}" class="text-sky-600 font-bold underline">{{ __('store.register') }}</a></p>
        </div>
    </div>
@endsection
