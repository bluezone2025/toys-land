<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    public static function allCached(): array
    {
        return Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allCached()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /** Translated setting: "{key}_ar" / "{key}_en". */
    public static function tr(string $key, mixed $default = null): mixed
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return static::get("{$key}_{$locale}") ?? static::get("{$key}_ar", $default);
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings');
    }
}
