<?php

use App\Models\Setting;
use App\Support\Shop;

if (! function_exists('tt')) {
    /** Inline bilingual UI string: tt('عربي', 'English'). */
    function tt(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}

if (! function_exists('money')) {
    /** Format a KWD amount in the visitor's currency. */
    function money(float|string|null $kd): string
    {
        return Shop::price($kd);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('setting_tr')) {
    function setting_tr(string $key, mixed $default = null): mixed
    {
        return Setting::tr($key, $default);
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return Shop::isRtl();
    }
}
