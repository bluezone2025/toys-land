<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $guarded = [];

    protected $casts = ['is_default' => 'boolean'];

    public const TYPES = ['house' => ['منزل', 'House'], 'apartment' => ['شقة', 'Apartment'], 'office' => ['مكتب', 'Office']];

    /** "Salmiya, Block 4, Street 10, Avenue 2, House 15, Floor 2" in the current language. */
    public static function format(array $a): string
    {
        $parts = [
            $a['city'] ?? $a['area'] ?? null,
            filled($a['block'] ?? null) ? tt('قطعة ', 'Block ').$a['block'] : null,
            filled($a['street'] ?? null) ? tt('شارع ', 'Street ').$a['street'] : null,
            filled($a['avenue'] ?? null) ? tt('جادة ', 'Avenue ').$a['avenue'] : null,
            filled($a['building'] ?? $a['apartment'] ?? null) ? tt(self::TYPES[$a['property_type'] ?? 'house'][0] ?? 'منزل', self::TYPES[$a['property_type'] ?? 'house'][1] ?? 'House').' '.($a['building'] ?? $a['apartment']) : null,
            filled($a['floor'] ?? null) ? tt('الدور/الشقة ', 'Floor/Flat ').$a['floor'] : null,
        ];

        return collect($parts)->filter()->implode(tt('، ', ', '));
    }

    public function summary(): string
    {
        return static::format($this->toArray()) ?: (string) $this->details;
    }

    /** Values in the shape of the checkout / address form fields. */
    public function formValues(): array
    {
        return [
            'title' => $this->title, 'country' => $this->country_code, 'city' => $this->area, 'block' => $this->block,
            'street' => $this->street, 'avenue' => $this->avenue, 'property_type' => $this->property_type,
            'apartment' => $this->building, 'floor' => $this->floor,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
