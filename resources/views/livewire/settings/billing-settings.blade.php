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

            <flux:separator />

            <div>
                <flux:heading size="sm">Tax Invoice (MUSHAK-6.3) Header</flux:heading>
                <p class="mt-1 text-xs text-zinc-500">
                    Printed at the top of every money receipt, above the memo.
                </p>
            </div>

            <flux:input
                wire:model="registeredPersonName"
                label="Registered Person Name"
                placeholder="e.g. Example Networks Ltd."
            />

            <flux:input
                wire:model="bin"
                label="BIN Number"
                placeholder="e.g. 000000000-0000"
                inputmode="numeric"
            />

            <flux:textarea
                wire:model="invoiceIssuingAddress"
                label="Invoice Issuing Address"
                rows="3"
                placeholder="House, road, area, city"
            />

            {{-- Logo is server-specific and git-ignored, so it is uploaded by
                 hand rather than through the app. --}}
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                <flux:heading size="sm">Company Logo</flux:heading>
                @if ($logoUrl)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $logoUrl }}" alt="Company logo" class="h-12 w-auto bg-white p-1" />
                        <flux:badge size="sm" color="green">Uploaded</flux:badge>
                    </div>
                @else
                    <flux:badge size="sm" color="zinc" class="mt-3">Not uploaded</flux:badge>
                @endif
                <p class="mt-3 text-xs break-all text-zinc-500">
                    Upload a monochrome PNG to <code class="font-mono">{{ $logoPath }}</code> on this server.
                </p>
            </div>

            <div class="flex justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="check"
                    class="min-h-[44px] w-full sm:w-auto"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    Save Settings
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
