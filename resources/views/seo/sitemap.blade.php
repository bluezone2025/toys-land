<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($entries as $entry)
@php [$url, $updated, $priority] = $entry; $image = $entry[3] ?? null; @endphp
@foreach (['ar', 'en'] as $locale)
    <url>
        <loc>{{ \App\Http\Controllers\SeoController::alternate($url, $locale) }}</loc>
        <xhtml:link rel="alternate" hreflang="ar" href="{{ $url }}"/>
        <xhtml:link rel="alternate" hreflang="en" href="{{ \App\Http\Controllers\SeoController::alternate($url, 'en') }}"/>
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $url }}"/>
@if ($updated)
        <lastmod>{{ $updated->toAtomString() }}</lastmod>
@endif
        <priority>{{ $priority }}</priority>
@if ($image && ($imageUrl = \App\Support\Seo::image($image)))
        <image:image><image:loc>{{ $imageUrl }}</image:loc></image:image>
@endif
    </url>
@endforeach
@endforeach
</urlset>
