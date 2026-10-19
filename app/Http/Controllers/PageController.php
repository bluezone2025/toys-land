<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Page;
use App\Support\Shop;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('pages.show', ['page' => $page, 'countries' => Shop::countries()]);
    }

    public function faq()
    {
        return view('pages.faq', ['faqs' => Faq::active()->get()]);
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function storeContact(Request $request)
    {
        // Honeypot field: bots fill it, people never see it.
        if ($request->filled('website')) {
            return back();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);
        ContactMessage::create($data + ['email' => auth()->user()?->email]);

        return back()->with('toast', tt('تم استلام رسالتك بنجاح! سنتواصل معك قريباً 💬', 'Message sent successfully! 💬'));
    }

    public function locale(string $locale)
    {
        session(['locale' => in_array($locale, ['ar', 'en']) ? $locale : 'ar']);

        return back();
    }

    public function country(Request $request)
    {
        $code = (string) $request->input('country');
        if (Shop::countries()->firstWhere('code', $code)) {
            session(['country' => $code]);
        }

        return back();
    }
}
