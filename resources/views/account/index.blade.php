@extends('layouts.store', ['active' => $tab === 'favorites' ? 'favorites' : 'profile', 'title' => __('store.profile')])

@php
    $rtl = is_rtl();
    $tabs = [
        'orders' => tt('📦 طلباتي الحالية والسابقة', '📦 Current & Past Orders'),
        'favorites' => tt('❤️ ألعابي المفضلة', '❤️ My Wishlist').' ('.$favorites->count().')',
        'addresses' => tt('📍 عناوين التوصيل', '📍 Delivery Addresses'),
        'account' => tt('⚙️ تعديل البيانات الشخصية', '⚙️ Edit Personal Info'),
    ];
    $statusLabels = [
        'pending' => [tt('تم استلام الطلب 📝', 'Received 📝'), 'bg-slate-100 text-slate-700'],
        'processing' => [tt('قيد التحضير 🎁', 'Preparing 🎁'), 'bg-amber-100 text-amber-700'],
        'shipped' => [tt('قيد التوصيل 🚚', 'Out for Delivery 🚚'), 'bg-sky-100 text-sky-700'],
        'delivered' => [tt('تم الاستلام ✅', 'Delivered ✅'), 'bg-emerald-100 text-emerald-700'],
        'cancelled' => [tt('ملغي ❌', 'Cancelled ❌'), 'bg-red-100 text-red-700'],
    ];
@endphp

@section('content')
    <div class="content-90 py-8">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-sky-500 to-indigo-600 text-white flex items-center justify-center text-2xl font-black shadow-sm shrink-0">
                    <i class="fa-regular fa-user"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-800">{{ tt('هلا والله، ', 'Welcome, ') }}{{ $user->name }} 👋</h1>
                    <p class="text-slate-500 text-xs mt-0.5">{{ $user->email }}@if ($user->phone) | <span class="dir-ltr">{{ $user->phone }}</span>@endif</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 w-full md:w-auto justify-end">
                @if ($orders->isNotEmpty())
                    <a href="{{ route('invoice.show', $orders->first()) }}" class="bg-sky-50 text-sky-700 hover:bg-sky-100 px-4 py-2.5 rounded-xl font-bold text-xs transition">🧾 {{ __('store.lastInvoice') }}</a>
                @endif
                @if ($user->is_admin)
                    <a href="{{ url('/admin') }}" class="bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-4 py-2.5 rounded-xl font-bold text-xs transition">🛠️ {{ tt('لوحة التحكم', 'Admin Panel') }}</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bg-red-50 text-red-600 px-4 py-2.5 rounded-xl font-bold text-xs hover:bg-red-100 transition cursor-pointer">{{ __('store.logout') }}</button>
                </form>
            </div>
        </div>

        <div class="flex gap-2 border-b border-slate-200 mb-6 overflow-x-auto no-scrollbar text-xs">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('account', ['tab' => $key]) }}" class="{{ $tab === $key ? 'px-5 py-3 font-black text-xs border-b-2 border-sky-500 text-sky-600 bg-sky-50/50 rounded-t-xl' : 'px-5 py-3 font-bold text-xs border-b-2 border-transparent text-slate-600 hover:text-slate-900' }} transition whitespace-nowrap">{{ $label }}</a>
            @endforeach
        </div>

        @if ($tab === 'orders')
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-black text-slate-800 mb-5">{{ tt('سجل الطلبات الحالية والسابقة ومتابعتها', 'Orders History & Live Tracking') }}</h3>
                @if ($orders->isEmpty())
                    <div class="text-center py-12">
                        <div class="text-5xl text-slate-300 mb-3"><i class="fa-solid fa-box-open"></i></div>
                        <h4 class="text-base font-bold text-slate-700 mb-4">{{ tt('ما عندك طلبات للحين', "You haven't placed any orders yet") }}</h4>
                        <a href="{{ route('products.index') }}" class="bg-sky-500 text-white px-6 py-3 rounded-xl font-bold text-xs inline-block shadow-sm">{{ __('store.browseToys') }}</a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($orders as $o)
                            <div class="border border-slate-100 rounded-2xl p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 text-xs">
                                <div>
                                    <div class="flex items-center gap-2.5 mb-1.5">
                                        <span class="font-black text-sky-600 text-sm">#{{ $o->number }}</span>
                                        <span class="{{ $statusLabels[$o->status][1] }} text-[10px] font-bold px-2.5 py-0.5 rounded-full">{{ $statusLabels[$o->status][0] }}</span>
                                    </div>
                                    <p class="text-slate-500 font-medium">{{ tt('التاريخ:', 'Date:') }} {{ $o->created_at->format('Y/m/d') }} | {{ tt('الإجمالي:', 'Total:') }} <span class="font-black text-pink-600">{{ money($o->total) }}</span> ({{ $o->items_sum_qty }} {{ __('store.pieces') }})</p>
                                </div>
                                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                                    <a href="{{ route('tracking', ['order' => $o->number, 'phone' => $o->phone]) }}" class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2.5 rounded-xl font-bold transition shadow-sm">🛵 {{ tt('تتبع الطلب', 'Track Order') }}</a>
                                    <a href="{{ route('invoice.show', $o) }}" class="bg-white border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold hover:bg-slate-100 transition">🧾 {{ tt('عرض الفاتورة', 'View Invoice') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @elseif ($tab === 'favorites')
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-black text-slate-800">{{ tt('الألعاب المفضلة لدي (', 'My Saved Wishlist (') }}{{ $favorites->count() }})</h3>
                    @if ($favorites->isNotEmpty())
                        <form method="POST" action="{{ route('favorites.clear') }}">
                            @csrf @method('DELETE')
                            <button class="text-xs font-bold text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-xl transition cursor-pointer"><i class="fa-solid fa-trash-can"></i> {{ tt('حذف الكل من المفضلة', 'Clear All Wishlist') }}</button>
                        </form>
                    @endif
                </div>
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
                            <div class="relative">
                                @include('partials.product-card', ['p' => $p])
                                <button onclick="toggleFavorite({{ $p->id }}, event); this.parentElement.remove();" class="mt-2 w-full bg-red-50 hover:bg-red-100 text-red-600 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-trash-can"></i> {{ tt('حذف من المفضلة', 'Remove from Wishlist') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @elseif ($tab === 'addresses')
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-black text-slate-800 mb-6">{{ tt('عناوين التوصيل المسجلة', 'Saved Delivery Addresses') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs mb-6">
                    @foreach ($addresses as $addr)
                        <div class="border-2 {{ $addr->is_default ? 'border-sky-500 bg-sky-50/30' : 'border-slate-200 bg-white' }} rounded-2xl p-5 relative">
                            @if ($addr->is_default)
                                <span class="absolute top-4 {{ $rtl ? 'left-4' : 'right-4' }} bg-sky-500 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-lg">{{ tt('العنوان الأساسي', 'Default') }}</span>
                            @endif
                            <h4 class="font-black text-slate-800 text-sm mb-1.5">{{ $addr->title }}</h4>
                            <p class="text-slate-600 font-medium">{{ $addr->details }}</p>
                            <p class="text-slate-400 text-[11px] mt-1.5">{{ $addr->area }}</p>
                            <div class="flex gap-4 mt-3">
                                @unless ($addr->is_default)
                                    <form method="POST" action="{{ route('addresses.default', $addr) }}">@csrf @method('PATCH')<button class="text-sky-600 hover:underline text-[11px] font-bold cursor-pointer">{{ tt('اجعله الأساسي', 'Make Default') }}</button></form>
                                @endunless
                                <form method="POST" action="{{ route('addresses.destroy', $addr) }}">@csrf @method('DELETE')<button class="text-red-500 hover:underline text-[11px] font-bold cursor-pointer">{{ tt('حذف العنوان', 'Delete Address') }}</button></form>
                            </div>
                        </div>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('addresses.store') }}" class="bg-slate-50 p-5 rounded-2xl border border-slate-200/80 space-y-3 text-xs max-w-xl">
                    @csrf
                    <h4 class="font-black text-slate-800 text-sm">{{ tt('+ إضافة عنوان توصيل يديد', '+ Add New Delivery Address') }}</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="text" name="title" required maxlength="100" placeholder="{{ tt('اسم العنوان (مثال: بيت الوالد، الشاليه)', 'Address Title (e.g. Home)') }}" class="border border-slate-200 rounded-xl p-2.5 bg-white">
                        <input type="text" name="area" required maxlength="100" placeholder="{{ tt('المنطقة / المحافظة', 'City / Area') }}" class="border border-slate-200 rounded-xl p-2.5 bg-white">
                    </div>
                    <input type="text" name="details" required maxlength="255" placeholder="{{ tt('القطعة، الشارع، رقم المنزل أو الشقة', 'Block, Street, House/Apt No.') }}" class="w-full border border-slate-200 rounded-xl p-2.5 bg-white">
                    <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-xl font-bold cursor-pointer">{{ tt('حفظ العنوان', 'Save Address') }}</button>
                </form>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-xs">
                    <h3 class="text-lg font-black text-slate-800 mb-5">{{ tt('تعديل البيانات الشخصية', 'Edit Personal Details') }}</h3>
                    @if ($errors->profile->any())
                        <div class="bg-red-50 text-red-600 rounded-xl p-3 mb-4 font-bold">@foreach ($errors->profile->all() as $e)<p>• {{ $e }}</p>@endforeach</div>
                    @endif
                    <form method="POST" action="{{ route('account.update') }}" class="space-y-4">
                        @csrf @method('PUT')
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('الاسم الكامل', 'Full Name') }}</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full border border-slate-200 rounded-xl p-3"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('البريد الإلكتروني', 'Email Address') }}</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border border-slate-200 rounded-xl p-3"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('رقم النقال', 'Mobile Number') }}</label><input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border border-slate-200 rounded-xl p-3"></div>
                        <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-3 rounded-xl font-bold transition cursor-pointer shadow-sm">{{ tt('حفظ التعديلات ✅', 'Save Changes ✅') }}</button>
                    </form>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-xs">
                    <h3 class="text-lg font-black text-slate-800 mb-5">{{ tt('تغيير كلمة المرور', 'Change Password') }}</h3>
                    @if ($errors->password->any())
                        <div class="bg-red-50 text-red-600 rounded-xl p-3 mb-4 font-bold">@foreach ($errors->password->all() as $e)<p>• {{ $e }}</p>@endforeach</div>
                    @endif
                    <form method="POST" action="{{ route('account.password') }}" class="space-y-4">
                        @csrf @method('PUT')
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('كلمة المرور الحالية', 'Current Password') }}</label><input type="password" name="current_password" required class="w-full border border-slate-200 rounded-xl p-3"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('كلمة المرور الجديدة', 'New Password') }}</label><input type="password" name="password" required class="w-full border border-slate-200 rounded-xl p-3"></div>
                        <div><label class="block font-bold text-slate-700 mb-1.5">{{ tt('تأكيد كلمة المرور', 'Confirm Password') }}</label><input type="password" name="password_confirmation" required class="w-full border border-slate-200 rounded-xl p-3"></div>
                        <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white px-6 py-3 rounded-xl font-bold transition cursor-pointer shadow-sm">{{ tt('تحديث كلمة المرور 🔒', 'Update Password 🔒') }}</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
