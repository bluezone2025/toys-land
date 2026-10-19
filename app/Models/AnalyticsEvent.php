<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['meta' => 'array', 'value' => 'decimal:3'];

    public static function record(string $type, array $data = []): void
    {
        static::create([
            'type' => $type,
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
            ...$data,
        ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
