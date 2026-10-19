<?php

namespace App\Support;

use App\Models\Country;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Request-wide storefront context: language direction and the visitor's selected country/currency.
 */
class Shop
{
    public static function isRtl(): bool
    {
        return app()->getLocale() !== 'en';
    }

    /** @return Collection<int, Country> */
    public static function countries(): Collection
    {
        // Cache plain attribute arrays (the cache store refuses to unserialize model objects).
        return once(fn () => Country::hydrate(
            Cache::remember('countries', 3600, fn () => Country::active()->get()->map->getAttributes()->all())
        ));
    }

    public static function country(): Country
    {
        $code = session('country', 'KW');
        $countries = static::countries();

        return $countries->firstWhere('code', $code)
            ?? $countries->firstWhere('code', 'KW')
            ?? $countries->first()
            ?? new Country(['code' => 'KW', 'rate' => 1, 'symbol_ar' => 'د.ك', 'symbol_en' => 'KD', 'name_ar' => 'الكويت', 'name_en' => 'Kuwait']);
    }

    /** Format a KWD amount in the visitor's selected currency. */
    public static function price(float|string|null $kd, ?Country $country = null): string
    {
        $country ??= static::country();
        $converted = (float) $kd * (float) $country->rate;

        return number_format($converted, 2, '.', '').' '.$country->tr('symbol');
    }
}
