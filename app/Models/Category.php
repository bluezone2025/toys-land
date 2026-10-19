<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasImage, Translatable;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
