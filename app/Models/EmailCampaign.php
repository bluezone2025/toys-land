<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaign extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'include_guests' => 'boolean', 'last_run_at' => 'datetime'];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isDue(): bool
    {
        return $this->is_active && $this->repeat_hours
            && (! $this->last_run_at || $this->last_run_at->lte(now()->subHours($this->repeat_hours)));
    }

    public function nextRunAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->is_active || ! $this->repeat_hours) {
            return null;
        }

        return $this->last_run_at ? $this->last_run_at->copy()->addHours($this->repeat_hours) : now();
    }
}
