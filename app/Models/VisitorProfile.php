<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row per browser (first-party visitor cookie): where they came from, how far they got
 * in the funnel and how likely they are to buy. The admin journey timeline hangs off it.
 */
class VisitorProfile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cart_value' => 'decimal:3',
        'total_spent' => 'decimal:3',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public const STAGES = [0 => 'يتصفح', 1 => 'مهتم', 2 => 'أضاف للسلة', 3 => 'بدأ الدفع', 4 => 'عميل'];

    public const STAGE_COLORS = [0 => 'gray', 1 => 'info', 2 => 'warning', 3 => 'danger', 4 => 'success'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'visitor_id', 'visitor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class, 'visitor_id', 'visitor_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'visitor_id', 'visitor_id');
    }

    public function displayName(): string
    {
        return $this->name ?: $this->user?->name ?: 'زائر #'.strtoupper(substr($this->visitor_id, 0, 6));
    }

    /** Page views and events merged, newest first. */
    public function timeline(int $limit = 300)
    {
        $visits = $this->visits()->latest('id')->limit($limit)->get()->map(fn (Visit $v) => (object) [
            'at' => $v->created_at, 'type' => 'page_view', 'path' => $v->path, 'product' => null, 'value' => null, 'meta' => [],
            'source' => $v->utm_source ?: $v->referrer,
        ]);
        $events = $this->events()->with('product:id,name_ar,slug')->latest('id')->limit($limit)->get()->map(fn (AnalyticsEvent $e) => (object) [
            'at' => $e->created_at, 'type' => $e->type, 'path' => $e->path, 'product' => $e->product, 'value' => $e->value, 'meta' => $e->meta ?? [],
            'source' => null, 'delivery' => $e->delivery,
        ]);

        return $visits->concat($events)->sortByDesc('at')->take($limit)->values();
    }
}
