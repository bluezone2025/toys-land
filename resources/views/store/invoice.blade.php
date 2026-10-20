@extends('layouts.store', ['noindex' => true, 'active' => 'invoice', 'title' => tt('فاتورة', 'Invoice').' #'.$order->number, 'showWelcome' => false])

@php
    $rtl = is_rtl();
    $alignStart = $rtl ? 'text-right' : 'text-left';
    $alignEnd = $rtl ? 'text-left' : 'text-right';
    $locale = app()->getLocale();
    $country = $order->country;
    // Show amounts in the currency the customer used at checkout.
    $fmt = fn ($kd) => number_format((float) $kd * (float) $order->currency_rate, 2, '.', '').' '.($country?->tr('symbol') ?? 'KD');
    $methodNames = [
        'knet' => tt('كي نت (KNet - MyFatoorah)', 'KNet (MyFatoorah)'),
        'card' => tt('بطاقة ائتمانية (Visa / MasterCard)', 'Credit Card (Visa / MasterCard)'),
        'applepay' => tt('أبل باي (Apple Pay - MyFatoorah)', 'Apple Pay (MyFatoorah)'),
        'cod' => tt('الدفع عند الاستلام', 'Cash on Delivery'),
    ];
    $types = ['house' => tt('منزل', 'House'), 'apartment' => tt('شقة', 'Apartment'), 'office' => tt('مكتب', 'Office')];
@endphp

@push('head')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" defer></script>
@endpush

@section('content')
    <div class="content-90 py-8">
        <div class="no-print bg-white rounded-2xl p-4 shadow-sm border border-slate-200 mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="font-black text-sm text-slate-800">{{ tt('طلبك معتمد وجاهز! تقدر تحفظ الفاتورة الحين:', 'Your order is confirmed! Download your invoice:') }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button onclick="downloadInvoiceAsImage()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 shadow-sm transition cursor-pointer">
                    <i class="fa-solid fa-image"></i> {{ tt('حفظ كصورة (PNG)', 'Save as Image (PNG)') }}
                </button>
                <button onclick="downloadInvoiceAsPDF()" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 shadow-sm transition cursor-pointer">
                    <i class="fa-solid fa-file-pdf"></i> {{ tt('تحميل كملف (PDF)', 'Download PDF') }}
                </button>
                <button onclick="window.print()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition cursor-pointer">
                    <i class="fa-solid fa-print"></i> {{ tt('طباعة', 'Print') }}
                </button>
                <a href="{{ route('tracking', ['order' => $order->number, 'phone' => $order->phone]) }}" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 shadow-sm transition">
                    <i class="fa-solid fa-truck-fast"></i> {{ __('store.liveTracking') }}
                </a>
            </div>
        </div>

        <div id="printable-invoice" class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden max-w-4xl mx-auto">
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 border-b-4 border-sky-500">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-sky-500 text-white rounded-2xl flex items-center justify-center text-3xl font-black shadow-md">
                        <i class="fa-solid fa-shapes text-yellow-300"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">{{ $brand }} 🧸</h1>
                        <p class="text-sky-300 text-xs font-semibold mt-0.5">{{ setting_tr('store_tagline', __('store.brandSub')) }}</p>
                        <p class="text-slate-500 text-[11px] mt-1">{{ setting_tr('address', tt('الكويت - الري - الأفنيوز مول', 'Kuwait - Al-Rai - The Avenues Mall')) }} | {{ tt('هاتف:', 'Tel:') }} <span class="dir-ltr">{{ setting('phone', '+965 22225555') }}</span></p>
                    </div>
                </div>
                <div class="{{ $alignEnd }} bg-white/10 backdrop-blur px-5 py-3.5 rounded-2xl border border-white/15">
                    @if ($order->payment_status === 'paid')
                        <span class="bg-emerald-400 text-slate-950 text-[11px] font-black px-2.5 py-0.5 rounded-full inline-block mb-1">✓ {{ tt('مدفوعة ومؤكدة', 'PAID & VERIFIED') }}</span>
                    @elseif ($order->payment_method === 'cod')
                        <span class="bg-amber-300 text-slate-950 text-[11px] font-black px-2.5 py-0.5 rounded-full inline-block mb-1">{{ tt('مؤكدة - الدفع عند الاستلام', 'CONFIRMED - PAY ON DELIVERY') }}</span>
                    @else
                        <span class="bg-red-400 text-white text-[11px] font-black px-2.5 py-0.5 rounded-full inline-block mb-1">{{ tt('بانتظار الدفع', 'AWAITING PAYMENT') }}</span>
                    @endif
                    <h2 class="text-lg font-black text-white">{{ tt('فاتورة الطلب', 'Order Invoice') }}</h2>
                    <p class="text-amber-300 font-mono font-bold text-sm">#{{ $order->number }}</p>
                    <p class="text-slate-300 text-[11px] mt-0.5">{{ $order->created_at->timezone(config('app.display_timezone'))->format('Y/m/d h:i A') }}</p>
                </div>
            </div>
            <div class="p-6 sm:p-10 space-y-8">
                <div class="bg-sky-50/80 border border-sky-200 rounded-2xl p-5 flex items-center gap-4">
                    <div class="text-3xl sm:text-4xl">🎉</div>
                    <div>
                        <h3 class="font-black text-slate-800 text-sm sm:text-base">{{ tt('مشكورين وايد على ثقتكم وتسوقكم من متجر '.$brand.'!', 'Thank you for shopping at '.$brand.'!') }}</h3>
                        <p class="text-slate-600 text-xs mt-1 leading-relaxed">{{ tt('نورتوا متجرنا! تم استلام طلبكم بنجاح وقاعدين نجهز الألعاب ونغلفها بكل حب عشان تفرح قلوب عيالكم.', 'We have received your order and our team is carefully preparing and wrapping your toys for fast delivery.') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80">
                        <h4 class="font-black text-xs text-sky-600 uppercase mb-3 flex items-center gap-2"><i class="fa-solid fa-user-check"></i> {{ tt('بيانات العميل (المشتري)', 'Customer Details') }}</h4>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 pb-1.5"><span class="text-slate-500">{{ tt('الاسم الكامل:', 'Full Name:') }}</span><span class="font-black text-slate-800">{{ $order->name }}</span></div>
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 pb-1.5"><span class="text-slate-500">{{ tt('رقم الهاتف:', 'Phone:') }}</span><span class="font-bold text-slate-800 dir-ltr">{{ $order->phone }}</span></div>
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 pb-1.5"><span class="text-slate-500">{{ tt('البريد الإلكتروني:', 'Email:') }}</span><span class="font-bold text-slate-700">{{ $order->email ?: tt('غير مسجل', 'N/A') }}</span></div>
                            <div class="flex justify-between gap-3 pt-0.5"><span class="text-slate-500">{{ tt('وسيلة الدفع:', 'Payment Method:') }}</span><span class="font-black text-emerald-600">{{ $methodNames[$order->payment_method] ?? $order->payment_method }}</span></div>
                        </div>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80">
                        <h4 class="font-black text-xs text-pink-600 uppercase mb-3 flex items-center gap-2"><i class="fa-solid fa-location-dot"></i> {{ tt('عنوان التوصيل التفصيلي', 'Delivery Address') }}</h4>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 pb-1.5"><span class="text-slate-500">{{ tt('الدولة والمنطقة:', 'Country & City:') }}</span><span class="font-black text-slate-800">{{ $country?->tr('name') }} - {{ $order->city }}</span></div>
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 pb-1.5"><span class="text-slate-500">{{ tt('تفاصيل العنوان:', 'Address:') }}</span><span class="font-bold text-slate-800">{{ $order->addressLine() }}</span></div>
                            <div class="flex justify-between gap-3 pt-0.5"><span class="text-slate-500">{{ tt('ملاحظات العميل:', 'Notes:') }}</span><span class="font-medium text-slate-600">{{ $order->notes ?: tt('لا توجد ملاحظات', 'No extra notes') }}</span></div>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="font-black text-sm text-slate-800 mb-3 flex items-center gap-2"><i class="fa-solid fa-box-open text-sky-500"></i> {{ tt('تفاصيل الألعاب والمنتجات المطلوبة', 'Ordered Items & Toys') }}</h4>
                    <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                        <table class="w-full {{ $alignStart }} text-xs">
                            <thead class="bg-slate-100 text-slate-700 font-black border-b border-slate-200">
                                <tr>
                                    <th class="p-3.5">#</th>
                                    <th class="p-3.5">{{ tt('المنتج والتفاصيل', 'Item & Options') }}</th>
                                    <th class="p-3.5 text-center">{{ tt('الكمية', 'Qty') }}</th>
                                    <th class="p-3.5 text-center">{{ tt('سعر الوحدة', 'Unit Price') }}</th>
                                    <th class="p-3.5 {{ $alignEnd }}">{{ tt('الإجمالي', 'Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($order->items as $i => $item)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="p-3.5 font-bold text-slate-500">{{ $i + 1 }}</td>
                                        <td class="p-3.5">
                                            <div class="font-black text-slate-800 text-sm">{{ $item->tr('name') }}</div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $item->tr('options') }}</div>
                                        </td>
                                        <td class="p-3.5 text-center font-bold">{{ $item->qty }}</td>
                                        <td class="p-3.5 text-center font-bold text-slate-700">{{ $fmt($item->unit_price) }}</td>
                                        <td class="p-3.5 {{ $alignEnd }} font-black text-pink-600">{{ $fmt($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center pt-2">
                    <div class="md:col-span-6 text-xs text-slate-500 space-y-2 bg-slate-50 p-5 rounded-2xl border border-slate-200/70">
                        <p class="font-bold text-slate-700">📌 {{ tt('سياسة الضمان والاستبدال:', 'Warranty & Return Policy:') }}</p>
                        <p class="leading-relaxed">{{ setting_tr('invoice_policy', tt('تعتبر هذه الفاتورة سند ضمان رسمي لمنتجاتكم لمدة 14 يوماً للاستبدال أو الاسترجاع.', 'This invoice serves as an official 14-day warranty and return receipt.')) }}</p>
                        <div class="pt-2 flex items-center gap-3 text-slate-500">
                            <i class="fa-solid fa-barcode text-3xl text-slate-700"></i>
                            <span class="font-mono text-[11px]">{{ $order->payment_id ? 'MF-'.$order->payment_id : 'REF-'.$order->number }}</span>
                        </div>
                    </div>
                    <div class="md:col-span-6 bg-slate-900 text-white rounded-2xl p-6 space-y-3 shadow-md">
                        <div class="flex justify-between text-xs text-slate-300"><span>{{ tt('المجموع الفرعي للمنتجات:', 'Items Subtotal:') }}</span><span class="font-bold text-white">{{ $fmt($order->subtotal) }}</span></div>
                        @if ($order->discount > 0)
                            <div class="flex justify-between text-xs text-emerald-400"><span>{{ tt('كود الخصم المستخدم', 'Promo Code') }} (<b>{{ $order->coupon_code }}</b>):</span><span class="font-bold">- {{ $fmt($order->discount) }}</span></div>
                        @endif
                        <div class="flex justify-between text-xs text-slate-300 border-b border-slate-700 pb-3"><span>{{ tt('رسوم التوصيل وتغليف الهدايا:', 'Delivery & Gift Wrapping:') }}</span>
                            <span class="text-amber-300 font-bold">{{ $order->shipping > 0 ? $fmt($order->shipping) : tt('مجاناً (0.00)', 'FREE (0.00)') }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <span class="font-black text-base text-sky-400">{{ $order->payment_status === 'paid' ? tt('الإجمالي النهائي المدفوع:', 'Grand Total Paid:') : tt('الإجمالي النهائي:', 'Grand Total:') }}</span>
                            <span class="font-black text-2xl text-amber-300">{{ $fmt($order->total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-100 px-6 py-4 text-center text-[11px] text-slate-500 font-bold border-t border-slate-200">
                {{ tt('شكراً لتسوقكم من '.$brand.' — ', 'Thank you for shopping with '.$brand.' — ') }}{{ preg_replace('#^https?://#', '', config('app.url')) }} 🎈
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function downloadInvoiceAsImage() {
        showToast(@json(tt('جاري تجهيز وتحميل صورة الفاتورة... 📸', 'Generating PNG Invoice... 📸')));
        html2canvas(document.getElementById('printable-invoice'), { scale: 2, useCORS: true, backgroundColor: '#ffffff' }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Invoice-{{ $order->number }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }
    function downloadInvoiceAsPDF() {
        showToast(@json(tt('جاري تجهيز وتحميل ملف الـ PDF... 📄', 'Generating PDF Invoice... 📄')));
        html2pdf().set({
            margin: 8,
            filename: 'Invoice-{{ $order->number }}.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        }).from(document.getElementById('printable-invoice')).save();
    }
</script>
@endpush
