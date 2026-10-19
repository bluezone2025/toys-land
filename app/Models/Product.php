<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasImage, Translatable;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:3',
        'compare_price' => 'decimal:3',
        'gallery' => 'array',
        'is_best_seller' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function ageGroups(): BelongsToMany
    {
        return $this->belongsToMany(AgeGroup::class)->orderBy('sort');
    }

    /** Editions and add-ons offered on this product. */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort')->orderBy('id');
    }

    /** "3-5، 6-8" */
    public function ageLabel(): ?string
    {
        $keys = $this->ageGroups->pluck('key');

        return $keys->isEmpty() ? null : $keys->implode(is_rtl() ? '، ' : ', ').' '.tt('سنوات', 'Years');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSorted(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    public function galleryUrls(): array
    {
        return collect([$this->image, ...($this->gallery ?? [])])
            ->filter()->unique()->map(fn ($p) => static::imageUrl($p))->values()->all();
    }
}
