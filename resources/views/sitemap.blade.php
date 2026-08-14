{{-- El prologo XML va pegado al borde y sin salto previo: un solo byte de
     espacio en blanco delante y el navegador rechaza el documento entero. --}}
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
@if ($entry['lastmod'])
        <lastmod>{{ $entry['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $entry['changefreq'] }}</changefreq>
        <priority>{{ $entry['priority'] }}</priority>
@foreach ($entry['alternates'] as $code => $url)
        <xhtml:link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}"/>
@endforeach
@if (isset($entry['alternates'][App\Support\Locale::default()]))
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $entry['alternates'][App\Support\Locale::default()] }}"/>
@endif
    </url>
@endforeach
</urlset>
