<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @PwaHead
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name') }}</title>
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @fluxAppearance
</head>
<body class="h-full bg-zinc-50 antialiased dark:bg-zinc-900" x-data="{ open: false }">

    {{-- Mobile backdrop --}}
    <div
        x-show="open"
        x-on:click="open = false"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-20 bg-black/60 lg:hidden"
        style="display: none"
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-zinc-900 transition-transform duration-300 ease-in-out lg:translate-x-0 dark:bg-zinc-950"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        {{-- Brand --}}
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-4">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-zinc-900 shadow-sm">
                <flux:icon name="wifi" class="size-5" />
            </div>
            <span class="min-w-0 flex-1 truncate text-sm font-bold tracking-tight text-white">
                {{ config('app.name') }}
            </span>
            <button
                x-on:click="open = false"
                class="flex size-9 items-center justify-center rounded-lg text-zinc-400 hover:bg-white/10 hover:text-white lg:hidden"
            >
                <flux:icon name="x-mark" class="size-5" />
                <span class="sr-only">Close</span>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex flex-1 flex-col overflow-y-auto px-3 py-4">

            {{-- Main items (RBAC-gated, same permission model as DCM) --}}
            @php
                $navItems = array_values(array_filter([
                    ['icon' => 'home', 'label' => 'Dashboard', 'href' => route('dashboard'), 'route' => 'dashboard', 'visible' => true],
                    ['icon' => 'document-text', 'label' => 'Bill View', 'href' => route('billing.bill-view'), 'route' => 'billing.bill-view', 'visible' => \App\Support\BillingScope::hasAnyScope()],
                    ['icon' => 'banknotes', 'label' => 'Money Receipt', 'href' => route('billing.money-receipt'), 'route' => 'billing.money-receipt', 'visible' => \App\Support\AccessHelper::any(['money-receipt-entry', 'money-receipt-entry-admin', 'super-admin', 'perm_all_manager'])],
                    ['icon' => 'chart-bar', 'label' => 'Collection Report', 'href' => route('reports.collection'), 'route' => 'reports.collection', 'visible' => \App\Support\AccessHelper::any(['report_mac-payment', 'super-admin', 'perm_all_manager'])],
                ], fn ($item) => $item['visible']));
            @endphp

            <p class="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Main</p>
            <div class="flex flex-col gap-0.5">
                @foreach($navItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        wire:navigate
                        x-on:click="open = false"
                        @class([
                            'flex min-h-[44px] items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150',
                            'bg-white/10 text-white'  => request()->routeIs($item['route']),
                            'text-zinc-400 hover:bg-white/5 hover:text-white' => ! request()->routeIs($item['route']),
                        ])
                    >
                        <flux:icon name="{{ $item['icon'] }}" class="size-5 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- Spacer --}}
            <div class="flex-1"></div>
        </nav>
    </aside>

    {{-- Main content --}}
    <div class="flex min-h-screen flex-col lg:pl-64">

        {{-- Top header --}}
        <header class="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-3 border-b border-zinc-200 bg-white px-4 dark:border-zinc-800 dark:bg-zinc-900 sm:px-6">

            {{-- Hamburger (mobile only) --}}
            <button
                x-on:click="open = true"
                class="flex size-11 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 lg:hidden"
            >
                <flux:icon name="bars-3" class="size-6" />
                <span class="sr-only">Open menu</span>
            </button>

            {{-- Page title --}}
            <div class="flex min-w-0 flex-1 items-center">
                @isset($heading)
                    <flux:heading class="truncate text-sm sm:text-base">{{ $heading }}</flux:heading>
                @endisset
            </div>

            {{-- Profile dropdown --}}
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" class="flex min-h-[44px] items-center gap-2 px-2 pr-3">
                    <flux:avatar name="{{ auth()->user()->name }}" size="sm" />
                    <span class="hidden max-w-[140px] truncate text-sm font-medium sm:block">
                        {{ auth()->user()->name }}
                    </span>
                    <flux:icon name="chevron-down" class="size-3.5 shrink-0 text-zinc-400" />
                </flux:button>

                <flux:menu>
                    <div class="border-b border-zinc-100 px-3 py-2.5 dark:border-zinc-800">
                        <p class="truncate text-xs font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ auth()->user()->name }}
                        </p>
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                            {{ auth()->user()->email }}
                        </p>
                    </div>
                    <flux:menu.item icon="user">Profile</flux:menu.item>
                    <flux:separator />
                    <flux:menu.item
                        icon="arrow-right-start-on-rectangle"
                        x-on:click="document.getElementById('logout-form').submit()"
                        class="text-red-600 dark:text-red-400"
                    >
                        Log out
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">
                @csrf
            </form>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>

    @fluxScripts
    @RegisterServiceWorkerScript
</body>
</html>
