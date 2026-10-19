<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/**
 * Shared admin form fields for bilingual (Arabic / English) content.
 */
class Fields
{
    /** @return array{0: TextInput|Textarea, 1: TextInput|Textarea} */
    public static function bilingual(string $field, string $label, bool $required = false, bool $textarea = false, int $max = 255): array
    {
        $make = fn (string $suffix, string $lang) => ($textarea ? Textarea::make("{$field}_{$suffix}")->rows(3) : TextInput::make("{$field}_{$suffix}")->maxLength($max))
            ->label("{$label} ({$lang})")
            ->required($required);

        return [$make('ar', 'عربي'), $make('en', 'English')->extraInputAttributes(['dir' => 'ltr'])];
    }

    public static function image(string $directory, string $field = 'image', string $label = 'الصورة'): FileUpload
    {
        return FileUpload::make($field)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->imageEditor()
            ->maxSize(4096)
            ->helperText('يفضل صورة بعرض 1200 بكسل أو أكثر (حد أقصى 4MB)');
    }
}
