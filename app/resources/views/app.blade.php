@php
    // Share tags come from the server, not from the SSR node, so they survive an SSR outage (plan door 8).
    $meta = $page['props']['meta'];
    $url = rtrim((string) config('app.url'), '/').$meta['path'];
@endphp
<!DOCTYPE html>
<html lang="pt-BR" data-direction="plenario">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $meta['title'] }} - Mandato Aberto</title>
        <meta name="description" content="{{ $meta['description'] }}">
        <link rel="canonical" href="{{ $url }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Mandato Aberto">
        <meta property="og:locale" content="pt_BR">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ $meta['description'] }}">
        <meta property="og:url" content="{{ $url }}">
        @if (! empty($meta['noindex']))
        <meta name="robots" content="noindex">
        @endif
        @if (! empty($meta['image']))
        {{-- The card of this page (share-cards door 7) --}}
        <meta property="og:image" content="{{ $meta['image']['url'] }}">
        <meta property="og:image:width" content="{{ $meta['image']['width'] }}">
        <meta property="og:image:height" content="{{ $meta['image']['height'] }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:alt" content="{{ $meta['image']['alt'] }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image:alt" content="{{ $meta['image']['alt'] }}">
        @else
        <meta name="twitter:card" content="summary">
        @endif
        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="ma-page">
        @inertia
    </body>
</html>
