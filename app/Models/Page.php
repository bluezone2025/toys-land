<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasImage, Translatable;

    protected $guarded = [];

    protected $casts = ['stats' => 'array', 'cards' => 'array', 'is_active' => 'boolean'];

    /** Read a translated key from a JSON repeater row. */
    public static function rowTr(array $row, string $field): ?string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return ($row["{$field}_{$locale}"] ?? null) ?: ($row["{$field}_ar"] ?? null);
    }
}
