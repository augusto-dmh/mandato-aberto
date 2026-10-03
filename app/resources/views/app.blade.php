@php
    // Share tags come from the server, not from the SSR node, so they survive an SSR outage (plan door 8).
    $meta = $page['props']['meta'];
    $url = rtrim((string) config('app.url'), '/').$meta['path'];
    // A page with `hydrate: false` links no client script once SSR has rendered its body; without SSR the
    // client entry renders it, so the page is never empty (app-home door 2).
    $hydrate = ($meta['hydrate'] ?? true) || app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch() === null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR" data-direction="plenario">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $meta['title'] }} - Mandato Aberto</title>
        <meta name="description" content="{{ $meta['description'] }}">
        @isset($meta['robots'])
        <meta name="robots" content="{{ $meta['robots'] }}">
        @endisset
        <link rel="canonical" href="{{ $url }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Mandato Aberto">
        <meta property="og:locale" content="pt_BR">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ $meta['description'] }}">
        <meta property="og:url" content="{{ $url }}">
        <meta name="twitter:card" content="summary">
        @if ($hydrate)
        @vite(['resources/js/app.js'])
        @else
        @vite('resources/css/app.css')
        @endif
        @inertiaHead
    </head>
    <body class="ma-page">
        @inertia
    </body>
</html>
