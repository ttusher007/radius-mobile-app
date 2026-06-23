<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard — {{ config('app.name') }}</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-full bg-zinc-50 antialiased dark:bg-zinc-900">
    <div class="mx-auto flex min-h-full w-full max-w-lg flex-col px-4 py-8">
        <flux:heading size="xl" class="mb-2">Dashboard</flux:heading>
        <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
            Signed in as {{ auth()->user()->name }}
        </flux:text>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" class="min-h-[44px] w-full">
                Sign out
            </flux:button>
        </form>
    </div>

    @fluxScripts
</body>
</html>
