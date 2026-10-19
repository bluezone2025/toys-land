<?php

namespace App\Models\Concerns;

trait Translatable
{
    /**
     * Read the "{field}_{locale}" column for the current locale, falling back to Arabic.
     */
    public function tr(string $field): ?string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return $this->{"{$field}_{$locale}"} ?: $this->{"{$field}_ar"};
    }
}
