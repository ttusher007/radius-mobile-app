<div class="mx-auto max-w-lg space-y-5">

    <div>
        <flux:button
            :href="route('billing.bill-view')"
            wire:navigate
            size="sm"
            variant="ghost"
            icon="arrow-left"
        >
            Back to Bill View
        </flux:button>
    </div>

    <flux:card class="p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <flux:heading size="lg" class="truncate">{{ $customer->name ?: '—' }}</flux:heading>
                <p class="mt-0.5 text-sm text-zinc-500">
                    <span class="font-mono">{{ $customer->username }}</span>
                    <span class="text-zinc-300 dark:text-zinc-600">·</span>
                    ID {{ $customer->id }}
                </p>
                @if ($customer->contact)
                    <p class="mt-0.5 text-sm text-zinc-500">{{ $customer->contact }}</p>
                @endif
            </div>
            <div class="shrink-0 text-right">
                <p class="text-xs text-zinc-400">Current Due</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">৳{{ number_format($customer->due_amount) }}</p>
            </div>
        </div>
    </flux:card>

    <flux:card class="flex flex-col items-center justify-center px-5 py-16 text-center">
        <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
            <flux:icon name="wrench-screwdriver" class="size-7 text-zinc-400" />
        </div>
        <flux:heading size="sm" class="mt-4 text-zinc-700 dark:text-zinc-300">Money Receipt Entry</flux:heading>
        <flux:text class="mt-1 max-w-xs text-sm text-zinc-500">
            Payment entry for this customer will be built in the next step.
        </flux:text>
    </flux:card>

</div>
