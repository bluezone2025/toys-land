{{-- Cookie consent (Google Consent Mode v2). Hidden until toysland-tracker finds no saved choice. --}}
<div id="consent-banner" role="dialog" aria-live="polite" aria-label="{{ tt('ملفات تعريف الارتباط', 'Cookies') }}"
     class="hidden no-print fixed bottom-4 inset-x-4 sm:inset-x-auto {{ is_rtl() ? 'sm:right-6' : 'sm:left-6' }} sm:max-w-sm z-[60] bg-white rounded-3xl shadow-2xl border border-sky-100 p-4">
    <p class="text-sm font-black text-slate-800 mb-1">🍪 {{ tt('نستخدم الكوكيز عشان تجربة أحلى', 'We use cookies for a better experience') }}</p>
    <p class="text-[11px] text-slate-600 mb-3 leading-relaxed">{{ tt('تساعدنا نعرض لك ألعاب تناسب عيالك ونقيس أداء عروضنا. تقدر تغيّر اختيارك وقت ما تبي.', 'They help us show toys that suit your kids and measure our offers. You can change your choice anytime.') }}
        <a href="{{ route('pages.privacy') }}" class="text-sky-600 font-bold hover:underline">{{ tt('سياسة الخصوصية', 'Privacy policy') }}</a></p>
    <div class="flex gap-2">
        <button type="button" data-consent="granted" class="flex-1 bg-sky-500 hover:bg-sky-600 text-white py-2 rounded-xl text-xs font-black transition cursor-pointer">{{ tt('موافق', 'Accept') }}</button>
        <button type="button" data-consent="denied" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 rounded-xl text-xs font-bold transition cursor-pointer">{{ tt('الضرورية فقط', 'Essential only') }}</button>
    </div>
</div>
