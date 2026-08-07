<div class="mx-auto max-w-2xl space-y-5">
    <flux:heading size="lg">Billing Settings</flux:heading>

    @if ($savedMessage)
        <div class="flex items-start gap-2 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400">
            <flux:icon name="check-circle" class="mt-0.5 size-4 shrink-0" />
            <span>{{ $savedMessage }}</span>
        </div>
    @endif

    <flux:card class="p-5">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:checkbox
                    wire:model="mandatoryRechargeCustomer"
                    label="Mandatory Recharge Customer"
                />
                <p class="mt-1 pl-7 text-xs text-zinc-500">
                    Keep Recharge Customer checked on money receipts and force recharge during save.
                </p>
            </div>

            <div class="flex justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="check"
                    class="min-h-[44px]"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    Save Settings
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
