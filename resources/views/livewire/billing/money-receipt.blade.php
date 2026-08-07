<div
    class="mx-auto max-w-lg space-y-5"
    x-data="{
        printStatus: null,
        init() {
            const l = localStorage.getItem('mr_ledger_id');
            if (l && ! $wire.ledgerId) $wire.set('ledgerId', l, false);
            if (@js($mandatoryRechargeCustomer)) {
                localStorage.setItem('mr_recharge', '1');
                $wire.set('recharge', true, false);
            } else {
                const r = localStorage.getItem('mr_recharge');
                if (r !== null) $wire.set('recharge', r === '1', false);
            }
            const p = localStorage.getItem('mr_print_receipt');
            if (p !== null) $wire.set('printReceipt', p === '1', false);
        },
        showStatus(detail) {
            this.printStatus = detail;
            if (detail.type === 'success') {
                setTimeout(() => { if (this.printStatus === detail) this.printStatus = null; }, 6000);
            }
        }
    }"
    x-on:mr-focus-amount.window="$nextTick(() => { const el = document.getElementById('mr-amount'); if (el) { el.focus(); el.select(); } })"
    x-on:mr-print-status.window="showStatus($event.detail)"
>

    {{-- Printer feedback — Web Bluetooth fails in ways only the user can fix. --}}
    <div
        x-show="printStatus"
        style="display: none"
        class="flex items-start gap-2 rounded-lg p-3 text-sm"
        :class="{
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400': printStatus?.type === 'success',
            'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400': printStatus?.type === 'error',
            'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300': printStatus?.type === 'info',
        }"
    >
        <flux:icon name="printer" class="mt-0.5 size-4 shrink-0" />
        <span class="flex-1" x-text="printStatus?.message"></span>
        <button type="button" class="shrink-0 opacity-60" x-on:click="printStatus = null" aria-label="Dismiss">
            <flux:icon name="x-mark" class="size-4" />
        </button>
    </div>

    <div>
        <flux:button :href="route('billing.bill-view')" wire:navigate size="sm" variant="ghost" icon="arrow-left">
            Back to Bill View
        </flux:button>
    </div>

    <flux:heading size="lg">Money Receipt</flux:heading>

    {{-- ── Search panel ─────────────────────────────────────────────────── --}}
    @if (! $customer)
        <flux:card class="p-5">
            <form wire:submit="searchCustomer" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <flux:input
                        wire:model="customerId"
                        label="Customer ID"
                        placeholder="Enter customer ID"
                        inputmode="numeric"
                        autofocus
                        clearable
                    />
                </div>
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="magnifying-glass"
                    class="min-h-[44px]"
                    wire:loading.attr="disabled"
                    wire:target="searchCustomer"
                >
                    Search
                </flux:button>
            </form>

            @if ($lookupError)
                <div class="mt-4 flex items-start gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                    <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0" />
                    <span>{{ $lookupError }}</span>
                </div>
            @endif
        </flux:card>
    @endif

    {{-- ── Customer found: confirm + entry ──────────────────────────────── --}}
    @if ($customer)
        {{-- Customer confirmation --}}
        <flux:card class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <flux:heading size="lg" class="truncate">{{ $customer['name'] ?: '—' }}</flux:heading>
                        @if ($customer['status'] === 'Enabled')
                            <flux:badge size="sm" color="green" inset="top bottom">Enabled</flux:badge>
                        @elseif ($customer['status'] === 'Disabled')
                            <flux:badge size="sm" color="red" inset="top bottom">Disabled</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc" inset="top bottom">Closed</flux:badge>
                        @endif
                    </div>
                    <p class="mt-0.5 text-sm text-zinc-500">
                        <span class="font-mono">{{ $customer['username'] }}</span>
                        <span class="text-zinc-300 dark:text-zinc-600">·</span>
                        ID {{ $customer['id'] }}
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-xs text-zinc-400">Current Due</p>
                    <p class="text-lg font-bold text-amber-600 dark:text-amber-400">৳{{ number_format($customer['due']) }}</p>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div><dt class="text-xs text-zinc-400">Package</dt><dd class="text-zinc-700 dark:text-zinc-300">{{ $customer['package'] ?: '—' }}</dd></div>
                <div><dt class="text-xs text-zinc-400">Monthly Bill</dt><dd class="text-zinc-700 dark:text-zinc-300">৳{{ number_format($customer['bill_amount']) }}</dd></div>
                <div><dt class="text-xs text-zinc-400">Current Expiry Date</dt><dd><x-expiry-date :date="$customer['expiry_date']" :label="$customer['expiry_label']" /></dd></div>
                <div><dt class="text-xs text-zinc-400">Discount</dt><dd class="text-zinc-700 dark:text-zinc-300">৳{{ number_format($customer['discount']) }}</dd></div>
                <div><dt class="text-xs text-zinc-400">IP Bill</dt><dd class="text-zinc-700 dark:text-zinc-300">৳{{ number_format($customer['ip_bill']) }}</dd></div>
                <div><dt class="text-xs text-zinc-400">Extra Bill</dt><dd class="text-zinc-700 dark:text-zinc-300">৳{{ number_format($customer['extra_bill']) }}</dd></div>
                <div><dt class="text-xs text-zinc-400">POP</dt><dd class="text-zinc-700 dark:text-zinc-300">{{ $customer['pop'] ?: '—' }}</dd></div>
                <div><dt class="text-xs text-zinc-400">Contact</dt><dd class="text-zinc-700 dark:text-zinc-300">{{ $customer['contact'] ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-xs text-zinc-400">Address</dt><dd class="text-zinc-700 dark:text-zinc-300">{{ $customer['address'] ?: '—' }}</dd></div>
            </dl>

            <div class="mt-4">
                <flux:button wire:click="changeCustomer" size="sm" variant="subtle" icon="arrow-path">
                    Change customer
                </flux:button>
            </div>
        </flux:card>

        {{-- ── Step: entry form ─────────────────────────────────────────── --}}
        @if ($step === 'form')
            <flux:card class="p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input label="Date" value="{{ now()->format('d M Y') }}" readonly />

                    <div x-on:change="localStorage.setItem('mr_ledger_id', $event.target.value)">
                        <flux:select wire:model="ledgerId" label="Ledger">
                            <flux:select.option value="">Select ledger…</flux:select.option>
                            @foreach ($this->ledgers as $ledger)
                                <flux:select.option :value="(string) $ledger->id">{{ $ledger->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                @if (count($this->ledgers) === 0)
                    <div class="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                        <flux:icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0" />
                        <span>No ledgers are assigned to your account. Contact an administrator.</span>
                    </div>
                @endif

                <div class="mt-4">
                    <flux:input
                        id="mr-amount"
                        wire:model="amount"
                        label="Amount (BDT)"
                        type="number"
                        inputmode="decimal"
                        min="1"
                        step="1"
                        placeholder="0"
                    />
                    <flux:error name="amount" />
                </div>

                <div class="mt-4" x-on:change="if (! @js($mandatoryRechargeCustomer)) localStorage.setItem('mr_recharge', $event.target.checked ? '1' : '0')">
                    @if ($mandatoryRechargeCustomer)
                        <flux:checkbox wire:model="recharge" label="Recharge Customer" disabled />
                    @else
                        <flux:checkbox wire:model="recharge" label="Recharge Customer" />
                    @endif
                    <p class="mt-1 pl-7 text-xs text-zinc-500">
                        @if ($mandatoryRechargeCustomer)
                            Required by admin setting. Extend validity, enable if disabled, top up manager/POP, generate bill &amp; notify.
                        @else
                            Extend validity, enable if disabled, top up manager/POP, generate bill &amp; notify.
                        @endif
                    </p>
                </div>

                <div class="mt-4" x-data="{ method: localStorage.getItem('mr_print_method') || 'auto', showSetup: false }">
                    <div x-on:change="localStorage.setItem('mr_print_receipt', $event.target.checked ? '1' : '0')">
                        <flux:checkbox wire:model="printReceipt" label="Print Receipt" />
                        <p class="mt-1 pl-7 text-xs text-zinc-500">
                            Print a POS receipt after saving — Bluetooth printer on mobile, system POS printer on desktop.
                        </p>
                    </div>

                    <div x-show="$wire.printReceipt" style="display: none" class="mt-2 pl-7">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                            x-on:click="showSetup = ! showSetup"
                        >
                            <flux:icon name="cog-6-tooth" class="size-3.5" />
                            <span x-text="showSetup ? 'Hide printer setup' : 'Printer setup'"></span>
                        </button>
                    </div>

                    <div
                        x-show="$wire.printReceipt && showSetup"
                        style="display: none"
                        class="mt-3 space-y-3 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/50"
                    >
                        <flux:select
                            x-model="method"
                            x-on:change="localStorage.setItem('mr_print_method', $event.target.value)"
                            label="Printer"
                            size="sm"
                            description="RawBT is for Bluetooth Classic printers that do not appear in the pairing list."
                        >
                            <flux:select.option value="auto">Automatic (Bluetooth on mobile, dialog on desktop)</flux:select.option>
                            <flux:select.option value="bluetooth">Bluetooth POS printer</flux:select.option>
                            <flux:select.option value="rawbt">RawBT app (Android)</flux:select.option>
                            <flux:select.option value="dialog">System print dialog</flux:select.option>
                        </flux:select>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <flux:button
                                x-show="method === 'auto' || method === 'bluetooth'"
                                type="button"
                                size="sm"
                                variant="outline"
                                icon="link"
                                class="min-h-[44px] w-full sm:w-auto"
                                x-on:click="window.dispatchEvent(new CustomEvent('mr-pair-printer'))"
                            >
                                Pair printer
                            </flux:button>
                            <flux:button
                                type="button"
                                size="sm"
                                variant="outline"
                                icon="printer"
                                class="min-h-[44px] w-full sm:w-auto"
                                x-on:click="window.dispatchEvent(new CustomEvent('mr-test-print', { detail: { company: @js(config('pwa.manifest.name', config('app.name'))) } }))"
                            >
                                Test print
                            </flux:button>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <flux:button
                        wire:click="review"
                        variant="primary"
                        icon="arrow-right"
                        class="min-h-[44px] w-full"
                    >
                        Review
                    </flux:button>
                </div>
            </flux:card>
        @endif

        {{-- ── Step: confirm preview ────────────────────────────────────── --}}
        @if ($step === 'confirm')
            <flux:card class="p-5">
                <flux:heading size="sm" class="mb-3">Confirm this entry</flux:heading>

                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-sm text-zinc-500">Customer</dt>
                        <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $customer['name'] }} (#{{ $customer['id'] }})</dd>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-sm text-zinc-500">Amount</dt>
                        <dd class="text-base font-bold text-zinc-900 dark:text-white">৳{{ number_format((float) $amount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-sm text-zinc-500">Ledger</dt>
                        <dd class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ collect($this->ledgers)->firstWhere('id', (int) $ledgerId)?->name ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-sm text-zinc-500">Date</dt>
                        <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ now()->format('d M Y') }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-sm text-zinc-500">Recharge customer</dt>
                        <dd class="text-sm font-medium">
                            @if ($recharge)
                                <span class="text-emerald-600 dark:text-emerald-400">Yes</span>
                            @else
                                <span class="text-zinc-500">No</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row-reverse">
                    <flux:button
                        wire:click="submit"
                        variant="primary"
                        icon="check"
                        class="min-h-[44px] w-full sm:flex-1"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                    >
                        <span wire:loading.remove wire:target="submit">Confirm &amp; Save</span>
                        <span wire:loading wire:target="submit">Processing…</span>
                    </flux:button>
                    <flux:button
                        wire:click="back"
                        variant="ghost"
                        class="min-h-[44px] w-full sm:w-auto"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                    >
                        Back
                    </flux:button>
                </div>
            </flux:card>
        @endif

        {{-- ── Step: result ─────────────────────────────────────────────── --}}
        @if ($step === 'done' && $result)
            <flux:card class="p-5">
                @if ($result['ok'])
                    <div class="flex flex-col items-center text-center">
                        <div class="flex size-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <flux:icon name="check-circle" class="size-8" />
                        </div>
                        <flux:heading size="lg" class="mt-3">Payment Recorded</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ $result['message'] }}</flux:text>
                    </div>

                    <dl class="mt-5 space-y-1.5 rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-800/50">
                        @if (! empty($result['data']['mrn']))
                            <div class="flex justify-between"><dt class="text-zinc-500">Receipt #</dt><dd class="font-mono text-zinc-800 dark:text-zinc-200">{{ $result['data']['mrn'] }}</dd></div>
                        @endif
                        @isset($result['data']['amount'])
                            <div class="flex justify-between"><dt class="text-zinc-500">Amount</dt><dd class="font-medium text-zinc-800 dark:text-zinc-200">৳{{ number_format((float) $result['data']['amount']) }}</dd></div>
                        @endisset
                        @isset($result['data']['balance'])
                            <div class="flex justify-between"><dt class="text-zinc-500">New balance</dt><dd class="font-medium text-zinc-800 dark:text-zinc-200">৳{{ number_format((float) $result['data']['balance']) }}</dd></div>
                        @endisset
                        @if (! empty($result['data']['recharged']))
                            <div class="flex justify-between"><dt class="text-zinc-500">Recharged</dt><dd class="font-medium text-emerald-600 dark:text-emerald-400">{{ (int) $result['data']['recharge_months'] }} month(s)</dd></div>
                        @endif
                        @if (! empty($result['data']['enabled']))
                            <div class="flex justify-between"><dt class="text-zinc-500">Connection</dt><dd class="font-medium text-emerald-600 dark:text-emerald-400">Re-enabled</dd></div>
                        @endif
                        @if (! empty($result['data']['sms_sent']))
                            <div class="flex justify-between"><dt class="text-zinc-500">Notification</dt><dd class="font-medium text-zinc-800 dark:text-zinc-200">Sent</dd></div>
                        @endif
                    </dl>

                    @if (! empty($result['data']['messages']) && is_array($result['data']['messages']))
                        <ul class="mt-3 space-y-1 text-xs text-zinc-500">
                            @foreach ($result['data']['messages'] as $msg)
                                <li class="flex gap-1.5"><flux:icon name="information-circle" class="mt-0.5 size-3.5 shrink-0" />{{ $msg }}</li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <div class="flex flex-col items-center text-center">
                        <div class="flex size-14 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <flux:icon name="x-circle" class="size-8" />
                        </div>
                        <flux:heading size="lg" class="mt-3">Could Not Complete</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ $result['message'] }}</flux:text>
                    </div>
                @endif

                <div class="mt-6 flex flex-col gap-3 sm:flex-row-reverse">
                    <flux:button wire:click="newEntry" variant="primary" icon="plus" class="min-h-[44px] w-full sm:flex-1">
                        New Entry
                    </flux:button>
                    @if ($result['ok'] && ! empty($result['receipt']))
                        <flux:button
                            type="button"
                            variant="outline"
                            icon="printer"
                            class="min-h-[44px] w-full sm:w-auto"
                            x-on:click="window.dispatchEvent(new CustomEvent('mr-print-receipt', { detail: { receipt: {{ \Illuminate\Support\Js::from($result['receipt']) }}, manual: true } }))"
                        >
                            Print Receipt
                        </flux:button>
                    @endif
                    @if (! $result['ok'])
                        <flux:button wire:click="back" variant="ghost" class="min-h-[44px] w-full sm:w-auto">
                            Try Again
                        </flux:button>
                    @endif
                </div>
            </flux:card>
        @endif
    @endif

</div>
