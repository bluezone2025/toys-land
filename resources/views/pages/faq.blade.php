@extends('layouts.store', ['active' => 'faq', 'title' => __('store.faq')])

@php $whatsapp = preg_replace('/\D/', '', (string) setting('whatsapp', '96599999999')); @endphp

@section('content')
    <div class="content-90 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-10">
                <span class="bg-sky-100 text-sky-700 px-3.5 py-1 rounded-full text-xs font-black inline-block mb-2">{{ tt('مركز المساعدة والدعم 💡', 'Help & Support Center 💡') }}</span>
                <h1 class="text-2xl sm:text-4xl font-black text-slate-800 mb-2">{{ tt('الأسئلة الشائعة (FAQ)', 'Frequently Asked Questions') }}</h1>
                <p class="text-slate-500 text-xs sm:text-sm">{{ tt('كل الإجابات الواضحة عن التوصيل، الدفع، تغليف الهدايا، والاستبدال', 'Everything you need to know about delivery, payment, gift wrapping, and returns') }}</p>
            </div>
            <div class="space-y-3.5 mb-10">
                @foreach ($faqs as $i => $f)
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <button onclick="toggleFaq({{ $i }})" class="w-full p-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition cursor-pointer">
                            <span class="font-black text-sm sm:text-base text-slate-800 {{ is_rtl() ? 'text-right' : 'text-left' }}">{{ $f->tr('q') }}</span>
                            <span id="faq-icon-{{ $i }}" class="w-8 h-8 rounded-xl {{ $i === 0 ? 'bg-sky-500 text-white' : 'bg-sky-50 text-sky-600' }} flex items-center justify-center shrink-0 transition-transform duration-200">
                                <i class="fa-solid {{ $i === 0 ? 'fa-minus' : 'fa-plus' }} text-xs"></i>
                            </span>
                        </button>
                        <div id="faq-ans-{{ $i }}" class="px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3 {{ $i === 0 ? '' : 'hidden' }}">
                            {!! nl2br(e($f->tr('a'))) !!}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="bg-gradient-to-r from-sky-500 to-indigo-600 rounded-3xl p-6 sm:p-8 text-white text-center shadow-md">
                <h3 class="font-black text-lg mb-2">{{ tt('عندك سؤال ثاني ما لقيت إجابته؟', 'Still have questions?') }}</h3>
                <p class="text-sky-100 text-xs mb-5">{{ tt('فريق خدمة العملاء جاهز للرد عليك فوراً عبر الواتساب أو صفحة التواصل', 'Our customer care team is ready to assist you via WhatsApp or Contact page') }}</p>
                <div class="flex justify-center gap-3 flex-wrap">
                    <a href="{{ route('contact') }}" class="bg-white text-sky-700 px-6 py-2.5 rounded-xl font-black text-xs hover:bg-sky-50 transition">{{ __('store.contact') }}</a>
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="bg-emerald-500 text-white px-6 py-2.5 rounded-xl font-black text-xs hover:bg-emerald-600 transition flex items-center gap-1.5"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function toggleFaq(idx) {
        const ans = document.getElementById('faq-ans-' + idx);
        const icon = document.getElementById('faq-icon-' + idx);
        const open = ans.classList.toggle('hidden') === false;
        icon.innerHTML = '<i class="fa-solid ' + (open ? 'fa-minus' : 'fa-plus') + ' text-xs"></i>';
        icon.classList.toggle('bg-sky-500', open);
        icon.classList.toggle('text-white', open);
        icon.classList.toggle('bg-sky-50', !open);
        icon.classList.toggle('text-sky-600', !open);
    }
</script>
@endpush
