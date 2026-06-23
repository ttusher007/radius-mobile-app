<div class="space-y-5 sm:space-y-6">

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        @foreach([
            ['label' => 'Active Customers', 'value' => '—', 'icon' => 'users',              'ring' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'],
            ['label' => 'Due Payments',     'value' => '—', 'icon' => 'banknotes',          'ring' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'],
            ['label' => 'New Today',        'value' => '—', 'icon' => 'user-plus',          'ring' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'],
            ['label' => 'Expired',          'value' => '—', 'icon' => 'exclamation-circle', 'ring' => 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400'],
        ] as $stat)
            <flux:card class="p-4 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</p>
                        <p class="mt-1.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $stat['value'] }}</p>
                    </div>
                    <div class="shrink-0 rounded-xl p-2.5 {{ $stat['ring'] }}">
                        <flux:icon name="{{ $stat['icon'] }}" class="size-5" />
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>

    {{-- Recent activity --}}
    <flux:card class="overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <flux:heading size="sm">Recent Activity</flux:heading>
            <flux:button size="sm" variant="ghost" href="#" class="text-xs">View all</flux:button>
        </div>
        <div class="flex flex-col items-center justify-center px-5 py-16 text-center">
            <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="clock" class="size-7 text-zinc-400" />
            </div>
            <flux:heading size="sm" class="mt-4 text-zinc-700 dark:text-zinc-300">No activity yet</flux:heading>
            <flux:text class="mt-1 max-w-xs text-sm text-zinc-500">
                Customer and billing activity will appear here once data is available.
            </flux:text>
        </div>
    </flux:card>

</div>
