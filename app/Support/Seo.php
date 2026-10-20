<?php

namespace App\Support;

use App\Models\Concerns\HasImage;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Builds canonical / hreflang URLs and meta values. Arabic lives at the plain URL,
 * English at the same URL with ?lang=en, so crawlers can index both languages.
 */
class Seo
{
    /** Query parameters that change page content and belong in canonical URLs. */
    protected const KEEP = ['category', 'age', 'page', 'sort'];

    public static function url(string $locale, ?string $url = null): string
    {
        $url ??= request()->url();
        $query = collect(request()->query())->only(self::KEEP)->filter(fn ($v) => $v !== null && $v !== '' && $v !== 'default' && $v !== 'all')->all();
        unset($query['lang']);
        if (($query['page'] ?? null) == 1) {
            unset($query['page']);
        }
        if ($locale === 'en') {
            $query['lang'] = 'en';
        }

        return $url.($query ? '?'.http_build_query($query) : '');
    }

    /** Language switcher link: always carries ?lang= so it overrides the visitor's saved choice. */
    public static function switchUrl(string $locale): string
    {
        $url = static::url($locale);

        return $locale === 'en' ? $url : $url.(str_contains($url, '?') ? '&' : '?').'lang=ar';
    }

    public static function canonical(): string
    {
        return static::url(app()->getLocale());
    }

    public static function brand(): string
    {
        return (string) Setting::tr('store_name', __('store.brandName'));
    }

    public static function title(?string $page): string
    {
        $brand = static::brand();

        return $page ? Str::limit($page, 60, '').' | '.$brand : (Setting::tr('seo_home_title') ?: $brand.' | '.Setting::tr('store_tagline', __('store.brandSub')));
    }

    public static function description(?string $text): string
    {
        $text = $text ?: (Setting::tr('seo_home_description') ?: Setting::tr('footer_about', __('store.footerAbout')));

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $text))), 158);
    }

    public static function image(?string $image): ?string
    {
        $url = HasImage::imageUrl($image ?: Setting::get('og_image'));

        return $url ? (Str::startsWith($url, ['http://', 'https://']) ? $url : url($url)) : null;
    }

    public static function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    }

    public static function organization(): array
    {
        $sameAs = collect(['instagram', 'tiktok', 'snapchat', 'x', 'facebook'])->map(fn ($k) => Setting::get("social_{$k}"))->filter()->values()->all();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            '@id' => url('/').'#store',
            'name' => static::brand(),
            'url' => url('/'),
            'logo' => static::image(Setting::get('logo')) ?? url('/favicon.svg'),
            'image' => static::image(null),
            'telephone' => Setting::get('phone'),
            'email' => Setting::get('admin_email'),
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => Setting::tr('address'), 'addressCountry' => 'KW'],
            'priceRange' => 'KWD',
            'sameAs' => $sameAs ?: null,
        ]);
    }

    /** @param  array<int, array{0: string, 1: string}>  $items  [name, url] */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1],
            ])->all(),
        ];
    }
}
