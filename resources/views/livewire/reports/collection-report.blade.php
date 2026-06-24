<div class="space-y-5 sm:space-y-6" x-data="{ filtersOpen: true }">

    {{-- Heading --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <flux:heading size="lg" class="truncate">Collection Report</flux:heading>
            <flux:text class="mt-0.5 text-sm text-zinc-500">Customer payments by date</flux:text>
        </div>
        <flux:button size="sm" variant="ghost" icon="adjustments-horizontal" class="shrink-0 lg:hidden"
            x-on:click="filtersOpen = !filtersOpen">
            Filters
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="p-4 sm:p-5" x-show="filtersOpen" x-collapse>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:input type="date" wire:model.live="from" label="From" />
            <flux:input type="date" wire:model.live="to" label="To" />

            <x-searchable-select model="managerId" label="Manager" :options="$this->managerOptions" />

            <x-searchable-select model="popId" label="POP" :options="$this->popOptions" />

            <x-searchable-select model="ledgerId" label="Ledger" :options="$this->ledgerOptions" />

            @if ($this->canViewAllEntries)
                <x-searchable-select model="entryById" label="Entry By" :options="$this->entryUserOptions" />
            @endif
        </div>
    </flux:card>

    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4">
        <flux:card class="p-4 sm:p-5">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Receipts</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ number_format($this->summary->count) }}</p>
        </flux:card>
        <flux:card class="p-4 sm:p-5">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Collected</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">৳{{ number_format($this->summary->total) }}</p>
        </flux:card>
    </div>

    {{-- Per-ledger breakdown --}}
    @if (count($this->ledgerBreakdown) > 0)
        <div class="flex flex-wrap gap-2">
            @foreach ($this->ledgerBreakdown as $row)
                <div class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs dark:bg-zinc-800">
                    <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $row->name }}</span>
                    <span class="text-zinc-400">·</span>
                    <span class="text-zinc-600 dark:text-zinc-300">৳{{ number_format($row->total) }}</span>
                    <span class="text-zinc-400">({{ $row->count }})</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Results --}}
    <div wire:loading.class="opacity-50" class="transition-opacity">
        @forelse ($payments as $payment)
            <flux:card class="mb-3 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $payment->customer ?: '—' }}</p>
                        <p class="mt-0.5 text-sm text-zinc-500">
                            <span class="font-mono">{{ $payment->username }}</span>
                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                            ID {{ $payment->cid }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">৳{{ number_format($payment->amount) }}</p>
                        <p class="text-xs text-zinc-400">{{ \Illuminate\Support\Carbon::parse($payment->date)->format('d M Y') }}</p>
                    </div>
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div><dt class="text-xs text-zinc-400">POP</dt><dd class="text-zinc-700 dark:text-zinc-300">{{ $payment->pop ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-400">Ledger</dt><dd class="text-zinc-700 dark:text-zinc-300">{{ $payment->ledger }}</dd></div>
                    <div><dt class="text-xs text-zinc-400">Receipt #</dt><dd class="truncate font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $payment->mrn ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-400">Collected by</dt><dd class="text-zinc-700 dark:text-zinc-300">{{ $payment->col_by ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-400">Expiry Date</dt><dd><x-expiry-date :date="$payment->expiry_date" :label="$payment->expiry_label" /></dd></div>
                </dl>
            </flux:card>
        @empty
            <flux:card class="flex flex-col items-center justify-center px-5 py-16 text-center">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="inbox" class="size-7 text-zinc-400" />
                </div>
                <flux:heading size="sm" class="mt-4 text-zinc-700 dark:text-zinc-300">No collections</flux:heading>
                <flux:text class="mt-1 max-w-xs text-sm text-zinc-500">No payments match the selected dates and filters.</flux:text>
            </flux:card>
        @endforelse
    </div>

    @if ($payments->hasPages())
        <div class="pt-1">{{ $payments->links() }}</div>
    @endif

</div>
