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

    /** Optional search-engine title/description; left empty they are generated automatically. */
    public static function seoSection(): \Filament\Schemas\Components\Section
    {
        return \Filament\Schemas\Components\Section::make('تحسين الظهور في محركات البحث (SEO)')
            ->description('اختياري: إذا تركتها فارغة يتم إنشاؤها تلقائياً من الاسم والوصف.')
            ->columns(2)->columnSpanFull()->collapsible()->collapsed()
            ->schema([
                TextInput::make('seo_title_ar')->label('عنوان الصفحة في Google (عربي)')->maxLength(60)->helperText('حتى 60 حرف'),
                TextInput::make('seo_title_en')->label('SEO title (English)')->maxLength(60)->extraInputAttributes(['dir' => 'ltr']),
                Textarea::make('seo_description_ar')->label('وصف الصفحة في Google (عربي)')->rows(2)->maxLength(160)->helperText('حتى 160 حرف'),
                Textarea::make('seo_description_en')->label('SEO description (English)')->rows(2)->maxLength(160)->extraInputAttributes(['dir' => 'ltr']),
            ]);
    }

    public static function image(string $directory, string $field = 'image', string $label = 'الصورة'): FileUpload
    {
        return FileUpload::make($field)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->imageEditor()
            ->automaticallyResizeImagesMode('contain')
            ->automaticallyResizeImagesToWidth('1600')
            ->automaticallyResizeImagesToHeight('1600')
            ->automaticallyUpscaleImagesWhenResizing(false)
            ->maxSize(4096)
            ->helperText('يفضل صورة بعرض 1200 بكسل أو أكثر (حد أقصى 4MB)');
    }
}
