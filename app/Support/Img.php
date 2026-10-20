<?php

namespace App\Support;

use App\Models\Concerns\HasImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Serves resized WebP copies of uploaded images so phones download a few KB
 * instead of the full upload. Copies are made once, on first use, under
 * storage/app/public/resized/{width}/.
 */
class Img
{
    public static function url(?string $path, int $width): ?string
    {
        if (! $path) {
            return null;
        }

        // Remote seed images (Unsplash) resize through their own URL parameters.
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return Str::contains($path, 'images.unsplash.com')
                ? preg_replace('/([?&])w=\d+/', '${1}w='.$width, $path).(Str::contains($path, 'fm=') ? '' : '&fm=webp')
                : $path;
        }

        $disk = Storage::disk('public');
        $target = 'resized/'.$width.'/'.preg_replace('/\.\w+$/', '', $path).'.webp';
        if ($disk->exists($target)) {
            return $disk->url($target);
        }

        try {
            if (! $disk->exists($path) || ! function_exists('imagewebp') || ! ($binary = $disk->get($path))) {
                return HasImage::imageUrl($path);
            }
            $source = @imagecreatefromstring($binary);
            if (! $source) {
                return HasImage::imageUrl($path);
            }
            $w = imagesx($source);
            $h = imagesy($source);
            if ($w > $width) {
                $resized = imagescale($source, $width, (int) round($h * $width / $w), IMG_BICUBIC);
                imagedestroy($source);
                $source = $resized;
            }
            imagepalettetotruecolor($source);
            imagealphablending($source, true);
            imagesavealpha($source, true);

            ob_start();
            imagewebp($source, null, 78);
            $disk->put($target, ob_get_clean());
            imagedestroy($source);

            return $disk->url($target);
        } catch (Throwable $e) {
            report($e);

            return HasImage::imageUrl($path);
        }
    }

    /** "url 400w, url 800w" for responsive <img srcset>. */
    public static function srcset(?string $path, array $widths): string
    {
        return collect($widths)->map(fn ($w) => static::url($path, $w).' '.$w.'w')->implode(', ');
    }
}
