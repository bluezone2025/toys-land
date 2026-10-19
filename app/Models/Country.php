<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Country extends Model
{
    use Translatable;

    protected $guarded = [];

    protected $casts = ['rate' => 'decimal:4', 'shipping_fee' => 'decimal:3', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('countries'));
        static::deleted(fn () => Cache::forget('countries'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }
}
