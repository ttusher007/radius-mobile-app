<div class="space-y-5 sm:space-y-6">

    {{-- ── Assigned ledger balances ─────────────────────────────────────── --}}
    <div>
        <div class="mb-2 flex items-center gap-2">
            <flux:icon name="wallet" class="size-4 text-zinc-400" />
            <flux:heading size="sm">My Ledgers</flux:heading>
        </div>

        @if (count($this->ledgers) > 0)
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($this->ledgers as $ledger)
                    <flux:card class="p-4">
                        <p class="truncate text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $ledger->name }}</p>
                        <p @class([
                            'mt-1 text-xl font-bold tracking-tight',
                            'text-zinc-900 dark:text-white' => $ledger->balance >= 0,
                            'text-red-600 dark:text-red-400' => $ledger->balance < 0,
                        ])>
                            ৳{{ number_format($ledger->balance) }}
                        </p>
                    </flux:card>
                @endforeach
            </div>
        @else
            <flux:card class="p-4">
                <flux:text class="text-sm text-zinc-500">No ledgers are assigned to your account.</flux:text>
            </flux:card>
        @endif
    </div>

    {{-- ── Key metrics (clickable → details) ────────────────────────────── --}}
    <div>
        <div class="mb-2 flex items-center gap-2">
            <flux:icon name="chart-bar" class="size-4 text-zinc-400" />
            <flux:heading size="sm">My Customers &amp; Collection</flux:heading>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">

            {{-- Due customer count --}}
            <a href="{{ route('billing.bill-view') }}" wire:navigate
               class="group rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                <flux:card class="h-full p-4 transition group-hover:shadow-md sm:p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Due Customers</p>
                            <p class="mt-1.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ number_format($this->due->count) }}</p>
                        </div>
                        <div class="shrink-0 rounded-xl bg-blue-50 p-2.5 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <flux:icon name="users" class="size-5" />
                        </div>
                    </div>
                    <p class="mt-2 flex items-center gap-0.5 text-xs text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                        View list <flux:icon name="chevron-right" class="size-3" />
                    </p>
                </flux:card>
            </a>

            {{-- Total due amount --}}
            <a href="{{ route('billing.bill-view') }}" wire:navigate
               class="group rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                <flux:card class="h-full p-4 transition group-hover:shadow-md sm:p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Due</p>
                            <p class="mt-1.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">৳{{ number_format($this->due->total) }}</p>
                        </div>
                        <div class="shrink-0 rounded-xl bg-amber-50 p-2.5 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <flux:icon name="banknotes" class="size-5" />
                        </div>
                    </div>
                    <p class="mt-2 flex items-center gap-0.5 text-xs text-zinc-400 group-hover:text-amber-600 dark:group-hover:text-amber-400">
                        View list <flux:icon name="chevron-right" class="size-3" />
                    </p>
                </flux:card>
            </a>

            {{-- Today's collection count --}}
            <a href="{{ route('reports.collection', ['from' => $today, 'to' => $today]) }}" wire:navigate
               class="group rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <flux:card class="h-full p-4 transition group-hover:shadow-md sm:p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Today's Receipts</p>
                            <p class="mt-1.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ number_format($this->todayCollection->count) }}</p>
                        </div>
                        <div class="shrink-0 rounded-xl bg-emerald-50 p-2.5 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <flux:icon name="document-text" class="size-5" />
                        </div>
                    </div>
                    <p class="mt-2 flex items-center gap-0.5 text-xs text-zinc-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">
                        View details <flux:icon name="chevron-right" class="size-3" />
                    </p>
                </flux:card>
            </a>

            {{-- Today's collection amount --}}
            <a href="{{ route('reports.collection', ['from' => $today, 'to' => $today]) }}" wire:navigate
               class="group rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <flux:card class="h-full p-4 transition group-hover:shadow-md sm:p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Today's Collection</p>
                            <p class="mt-1.5 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">৳{{ number_format($this->todayCollection->total) }}</p>
                        </div>
                        <div class="shrink-0 rounded-xl bg-emerald-50 p-2.5 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <flux:icon name="arrow-trending-up" class="size-5" />
                        </div>
                    </div>
                    <p class="mt-2 flex items-center gap-0.5 text-xs text-zinc-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">
                        View details <flux:icon name="chevron-right" class="size-3" />
                    </p>
                </flux:card>
            </a>
        </div>
    </div>

    {{-- ── Quick actions ────────────────────────────────────────────────── --}}
    @php
        $canMoneyReceipt = \App\Support\AccessHelper::any(['money-receipt-entry', 'money-receipt-entry-admin', 'super-admin', 'perm_all_manager']);
        $canCollection = \App\Support\AccessHelper::any(['report_mac-payment', 'super-admin', 'perm_all_manager']);
    @endphp
    @if ($canMoneyReceipt || $canCollection)
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @if ($canMoneyReceipt)
        <a href="{{ route('billing.money-receipt') }}" wire:navigate class="group">
            <flux:card class="flex items-center gap-4 p-4 transition group-hover:shadow-md">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                    <flux:icon name="banknotes" class="size-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-zinc-900 dark:text-white">Money Receipt</p>
                    <p class="text-xs text-zinc-500">Collect a customer payment</p>
                </div>
                <flux:icon name="chevron-right" class="size-4 shrink-0 text-zinc-300 group-hover:text-zinc-500" />
            </flux:card>
        </a>
        @endif

        @if ($canCollection)
        <a href="{{ route('reports.collection') }}" wire:navigate class="group">
            <flux:card class="flex items-center gap-4 p-4 transition group-hover:shadow-md">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                    <flux:icon name="chart-bar" class="size-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-zinc-900 dark:text-white">Collection Report</p>
                    <p class="text-xs text-zinc-500">Customer payments by date</p>
                </div>
                <flux:icon name="chevron-right" class="size-4 shrink-0 text-zinc-300 group-hover:text-zinc-500" />
            </flux:card>
        </a>
        @endif
    </div>
    @endif

</div>
