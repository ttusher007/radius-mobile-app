<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#18181b">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name') }}</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @fluxStyles
</head>
<body class="h-full bg-zinc-50 antialiased dark:bg-zinc-900">
    {{ $slot }}
    @fluxScripts
</body>
</html>
