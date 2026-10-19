<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasImage
{
    /**
     * The admin upload field cannot display remote (seed) URLs and submits them as empty;
     * keep the existing URL unless a new file was actually uploaded.
     */
    public static function bootHasImage(): void
    {
        static::saving(function ($model) {
            if ($model->isDirty('image') && blank($model->image)
                && Str::startsWith((string) $model->getOriginal('image'), ['http://', 'https://'])) {
                $model->image = $model->getOriginal('image');
            }
        });
    }

    /**
     * Images are either uploaded files on the public disk or absolute URLs (seed data).
     */
    public static function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : Storage::disk('public')->url($path);
    }

    public function getImageUrlAttribute(): ?string
    {
        return static::imageUrl($this->image);
    }
}
