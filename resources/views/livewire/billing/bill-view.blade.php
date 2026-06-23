<div class="space-y-5 sm:space-y-6" x-data="{ filtersOpen: true }">

    {{-- Page heading --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <flux:heading size="lg" class="truncate">Bill View</flux:heading>
            <flux:text class="mt-0.5 text-sm text-zinc-500">Customers with an outstanding due</flux:text>
        </div>
        <flux:button
            size="sm"
            variant="ghost"
            icon="adjustments-horizontal"
            class="shrink-0 lg:hidden"
            x-on:click="filtersOpen = !filtersOpen"
        >
            Filters
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="p-4 sm:p-5" x-show="filtersOpen" x-collapse>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <flux:select wire:model.live="managerId" label="Manager">
                <flux:select.option value="all">-- All --</flux:select.option>
                @foreach ($this->managers as $manager)
                    <flux:select.option :value="(string) $manager->id">{{ $manager->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="popId" label="POP">
                <flux:select.option value="all">-- All --</flux:select.option>
                @foreach ($this->pops as $pop)
                    <flux:select.option :value="(string) $pop->id">{{ $pop->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="area" label="Area">
                <flux:select.option value="all">-- All --</flux:select.option>
                @foreach ($this->areas as $areaOption)
                    <flux:select.option :value="$areaOption">{{ $areaOption }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" label="Status">
                <flux:select.option value="all">All</flux:select.option>
                <flux:select.option value="enable">Enabled</flux:select.option>
                <flux:select.option value="disable">Disabled</flux:select.option>
            </flux:select>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.400ms="search"
                    label="Search"
                    placeholder="ID, username, name or contact"
                    icon="magnifying-glass"
                    clearable
                />
            </div>
            <flux:button
                variant="subtle"
                icon="x-mark"
                class="min-h-[44px] sm:w-auto"
                wire:click="clearFilters"
            >
                Clear
            </flux:button>
        </div>
    </flux:card>

    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4">
        <flux:card class="p-4 sm:p-5">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Customers</p>
                    <p class="mt-1.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        {{ number_format($this->summary->customer_count) }}
                    </p>
                </div>
                <div class="shrink-0 rounded-xl bg-blue-50 p-2.5 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <flux:icon name="users" class="size-5" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 sm:p-5">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Due</p>
                    <p class="mt-1.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        ৳{{ number_format($this->summary->due_total) }}
                    </p>
                </div>
                <div class="shrink-0 rounded-xl bg-amber-50 p-2.5 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <flux:icon name="banknotes" class="size-5" />
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Results --}}
    <div wire:loading.class="opacity-50" class="transition-opacity">
        @forelse ($customers as $customer)
            <flux:card class="mb-3 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $customer->name ?: '—' }}</p>
                            @if ($customer->enabled)
                                <flux:badge size="sm" color="green" inset="top bottom">Enabled</flux:badge>
                            @else
                                <flux:badge size="sm" color="red" inset="top bottom">Disabled</flux:badge>
                            @endif
                        </div>
                        <p class="mt-0.5 text-sm text-zinc-500">
                            <span class="font-mono">{{ $customer->username }}</span>
                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                            ID {{ $customer->id }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs text-zinc-400">Due</p>
                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400">৳{{ number_format($customer->due_amount) }}</p>
                    </div>
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div class="col-span-2">
                        <dt class="text-xs text-zinc-400">Address</dt>
                        <dd class="text-zinc-700 dark:text-zinc-300">{{ $customer->address ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-400">Package</dt>
                        <dd class="text-zinc-700 dark:text-zinc-300">{{ $customer->package ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-400">Bill Amount</dt>
                        <dd class="text-zinc-700 dark:text-zinc-300">৳{{ number_format($customer->bill_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-400">Current Expiry Date</dt>
                        <dd class="text-zinc-700 dark:text-zinc-300">{{ $customer->expiry_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-400">Contact</dt>
                        <dd class="text-zinc-700 dark:text-zinc-300">{{ $customer->contact ?: '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-4">
                    <flux:button
                        :href="route('billing.money-receipt', $customer->id)"
                        wire:navigate
                        variant="primary"
                        icon="banknotes"
                        class="min-h-[44px] w-full"
                    >
                        Receive Payment
                    </flux:button>
                </div>
            </flux:card>
        @empty
            <flux:card class="flex flex-col items-center justify-center px-5 py-16 text-center">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="inbox" class="size-7 text-zinc-400" />
                </div>
                <flux:heading size="sm" class="mt-4 text-zinc-700 dark:text-zinc-300">No dues found</flux:heading>
                <flux:text class="mt-1 max-w-xs text-sm text-zinc-500">
                    No customers match the selected filters. Try widening your selection.
                </flux:text>
            </flux:card>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($customers->hasPages())
        <div class="pt-1">
            {{ $customers->links() }}
        </div>
    @endif

</div>
