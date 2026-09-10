<?php

namespace App\Livewire\Billing;

use App\Models\TblPayment;
use App\Services\DcmClient;
use App\Support\AppSettings;
use App\Support\ExpiryDateHelper;
use App\Support\ResellerPermissionHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Money Receipt')]
class MoneyReceipt extends Component
{
    /** Permission that unlocks the receipt date picker. */
    private const DATE_SELECT_PERMISSION = 'perm_entry-mr-allow-date-select';

    /** Search box value. */
    public string $customerId = '';

    /** Resolved customer (null until a successful search). */
    public ?array $customer = null;

    /** Search outcome flags. */
    public bool $searched = false;

    public ?string $lookupError = null;

    /** Entry fields. */
    public string $amount = '';

    public string $ledgerId = '';

    public bool $recharge = true;

    public bool $mandatoryRechargeCustomer = false;

    /**
     * Receipt date (Y-m-d). Editable only by users holding
     * `perm_entry-mr-allow-date-select`; everyone else is pinned to today.
     */
    public string $receiptDate = '';

    /**
     * Print a POS receipt after saving. The choice is persisted in the
     * browser's localStorage (see the view) so it survives across entries.
     */
    public bool $printReceipt = true;

    /** Wizard step: form | confirm | done. */
    public string $step = 'form';

    public bool $processing = false;

    public ?array $result = null;

    /**
     * Client-generated idempotency key (becomes tblpayment.mrn, which is
     * UNIQUE). Stable for a given confirmed entry so a retry / double-submit
     * cannot create a second payment. Regenerated whenever the entry is
     * re-reviewed or a new entry is started.
     */
    public string $mrn = '';

    public function mount(): void
    {
        $this->syncRechargePolicy();
        $this->receiptDate = $this->resolveReceiptDate();

        if (trim($this->customerId) !== '') {
            $this->loadCustomer();
        }
    }

    /**
     * May this user back-date / forward-date a receipt?
     */
    #[Computed]
    public function canSelectDate(): bool
    {
        return Gate::allows(self::DATE_SELECT_PERMISSION);
    }

    /**
     * Ledgers (cash/bank) assigned to the current user.
     *
     * @return array<int, object{id:int, name:string}>
     */
    #[Computed]
    public function ledgers(): array
    {
        return DB::table('ledger_users as lu')
            ->join('ledgers as l', 'l.Ledger_Id', '=', 'lu.ledger_id')
            ->where('lu.user_id', auth()->id())
            ->orderBy('l.Ledger_Name')
            ->get(['l.Ledger_Id as id', 'l.Ledger_Name as name'])
            ->all();
    }

    public function searchCustomer(): void
    {
        $this->loadCustomer();
    }

    private function loadCustomer(): void
    {
        $this->reset('customer', 'lookupError', 'result');
        $this->searched = true;
        $this->step = 'form';

        $id = (int) trim($this->customerId);
        if ($id < 1) {
            $this->lookupError = 'Enter a valid customer ID.';

            return;
        }

        $row = DB::table('radcheck as c')
            ->leftJoin('tblaccounts as a', 'a.id', '=', 'c.id')
            ->leftJoin('uz_package as p', 'p.id', '=', 'c.packageid')
            ->leftJoin('uz_poplist as pl', 'pl.id', '=', 'c.allowpopid')
            ->leftJoin('uz_resellers as r', 'r.id', '=', 'c.resellerid')
            ->where('c.id', $id)
            ->first([
                'c.id', 'c.username', 'c.clientname', 'c.clintcontactno',
                'c.enableuser', 'c.tmpdel', 'c.expiredate', 'c.allowpopid',
                'c.flat_level', 'c.building_num', 'c.building_name',
                'c.road_num', 'c.road_name', 'c.block_sector', 'c.area',
                'c.discount', 'c.ip_bill', 'c.extra_bill',
                'a.balance', 'p.packagename', 'p.packagerate',
                'pl.popname', 'r.resellername',
            ]);

        if (! $row) {
            $this->lookupError = "No customer found with ID {$id}.";

            return;
        }

        if (! ResellerPermissionHelper::hasPopPermission((int) $row->allowpopid)) {
            $this->lookupError = 'This customer is outside your assigned scope.';

            return;
        }

        $billAmount = max(0, (int) $row->packagerate - (int) $row->discount
            + (int) ($row->ip_bill ?? 0) + (int) ($row->extra_bill ?? 0));
        $due = (float) ($row->balance ?? 0);

        $this->customer = [
            'id' => (int) $row->id,
            'username' => $row->username,
            'name' => $row->clientname,
            'contact' => $row->clintcontactno,
            'status' => (int) $row->tmpdel === 1 ? 'Closed' : ((int) $row->enableuser === 1 ? 'Enabled' : 'Disabled'),
            'due' => $due,
            'package' => $row->packagename,
            'bill_amount' => $billAmount,
            'discount' => (int) ($row->discount ?? 0),
            'ip_bill' => (int) ($row->ip_bill ?? 0),
            'extra_bill' => (int) ($row->extra_bill ?? 0),
            'expiry_date' => $row->expiredate,
            'expiry_label' => ExpiryDateHelper::format($row->expiredate),
            'pop' => $row->popname,
            'manager' => $row->resellername,
            'address' => $this->formatAddress($row),
        ];

        // Convenience: prefill with the outstanding due (staff can override).
        $this->amount = $due > 0 ? (string) (int) round($due) : '';

        $this->dispatch('mr-focus-amount');
    }

    public function changeCustomer(): void
    {
        $this->reset('customer', 'amount', 'searched', 'lookupError', 'result', 'mrn');
        $this->step = 'form';
        $this->customerId = '';
    }

    public function review(): void
    {
        $this->syncRechargePolicy();

        // Users without the permission can never move the date off today,
        // whatever the browser sent.
        $this->receiptDate = $this->resolveReceiptDate();

        $this->validate([
            'amount' => 'required|numeric|min:1',
            'ledgerId' => 'required',
            'receiptDate' => 'required|date_format:Y-m-d',
        ], [
            'amount.required' => 'Enter the amount received.',
            'amount.min' => 'Amount must be at least 1.',
            'ledgerId.required' => 'Select a ledger.',
            'receiptDate.required' => 'Select a receipt date.',
            'receiptDate.date_format' => 'Select a valid receipt date.',
        ]);

        if (! $this->customer) {
            $this->lookupError = 'Search and confirm a customer first.';

            return;
        }

        // Re-validate the ledger belongs to the user (defence in depth).
        $allowed = collect($this->ledgers())->contains(fn ($l) => (string) $l->id === (string) $this->ledgerId);
        if (! $allowed) {
            $this->addError('ledgerId', 'Select a valid ledger.');

            return;
        }

        // Fresh idempotency key for this confirmed entry (ties the amount/
        // customer/ledger about to be submitted to a single tblpayment row).
        $this->mrn = $this->generateMrn();

        $this->step = 'confirm';
    }

    public function back(): void
    {
        $this->syncRechargePolicy();
        $this->receiptDate = $this->resolveReceiptDate();

        $this->step = 'form';
    }

    public function submit(): void
    {
        if ($this->processing || ! $this->customer) {
            return;
        }

        $this->processing = true;
        $recharge = $this->effectiveRecharge();
        $this->recharge = $recharge;
        $this->receiptDate = $this->resolveReceiptDate();

        $response = app(DcmClient::class)->moneyReceipt([
            'customer_id' => (int) $this->customer['id'],
            'amount' => (float) $this->amount,
            'ledger_id' => (int) $this->ledgerId,
            'user_id' => (int) auth()->id(),
            'recharge' => $recharge,
            'mrn' => $this->mrn,
            'date' => $this->receiptDateTime()->format('Y-m-d H:i:s'),
        ]);

        $body = $response['body'];

        // A duplicate for OUR own mrn means our submit already went through
        // (double-submit / retry): treat it as recorded, never as a failure.
        $isDuplicate = ! empty($body['duplicate']);

        $this->result = [
            'ok' => $isDuplicate || ($response['ok'] && ($body['status'] ?? false)),
            'duplicate' => $isDuplicate,
            'message' => $isDuplicate
                ? 'This receipt was already recorded — no duplicate was created.'
                : ($body['message'] ?? ($response['ok'] ? 'Completed.' : 'The request could not be completed.')),
            'data' => $body,
        ];

        if ($this->result['ok']) {
            $this->result['receipt'] = $this->buildReceipt($body);

            if ($this->printReceipt) {
                $this->dispatch('mr-print-receipt', receipt: $this->result['receipt'], manual: false);
            }
        }

        $this->step = 'done';
        $this->processing = false;
    }

    /**
     * Data for the printable POS receipt (consumed by receipt-printer.js).
     *
     * @return array<string, mixed>
     */
    private function buildReceipt(array $body): array
    {
        $previousDue = (float) $this->customer['due'];
        $amount = isset($body['amount']) ? (float) $body['amount'] : (float) $this->amount;

        return [
            // MUSHAK-6.3 tax invoice header (see Billing Settings).
            ...$this->receiptHeader(),
            'mrn' => (string) ($body['mrn'] ?? $this->mrn),
            'date' => $this->receiptDateTime()->format('d M Y, h:i A'),
            'customer_id' => (int) $this->customer['id'],
            'customer_name' => (string) ($this->customer['name'] ?? ''),
            'username' => (string) ($this->customer['username'] ?? ''),
            'contact' => (string) ($this->customer['contact'] ?? ''),
            'address' => (string) ($this->customer['address'] ?? ''),
            'package' => (string) ($this->customer['package'] ?? ''),
            'previous_due' => $previousDue,
            'amount' => $amount,
            'new_due' => array_key_exists('balance', $body) ? (float) $body['balance'] : $previousDue - $amount,
            'ledger' => (string) (collect($this->ledgers())->firstWhere('id', (int) $this->ledgerId)?->name ?? ''),
            'received_by' => (string) (auth()->user()->name ?? ''),
            'recharge_months' => ! empty($body['recharged']) ? (int) ($body['recharge_months'] ?? 1) : 0,
        ];
    }

    public function newEntry(): void
    {
        $this->reset('customer', 'customerId', 'amount', 'searched', 'lookupError', 'result', 'mrn');
        $this->syncRechargePolicy();
        $this->receiptDate = $this->resolveReceiptDate();
        $this->step = 'form';
    }

    public function updatedRecharge(): void
    {
        $this->syncRechargePolicy();
    }

    public function render()
    {
        return view('livewire.billing.money-receipt', [
            'receiptHeader' => $this->receiptHeader(),
        ]);
    }

    /**
     * MUSHAK-6.3 tax invoice header, shared by the printed receipt and the
     * "Test print" button.
     *
     * @return array<string, mixed>
     */
    private function receiptHeader(): array
    {
        return [
            'company' => (string) config('pwa.manifest.name', config('app.name')),
            'logo_url' => AppSettings::logoUrl(),
            'registered_person_name' => AppSettings::registeredPersonName(),
            'bin' => AppSettings::bin(),
            'invoice_issuing_address' => AppSettings::invoiceIssuingAddress(),
        ];
    }

    /**
     * The effective receipt date: the picked date for permitted users,
     * today for everyone else.
     */
    private function resolveReceiptDate(): string
    {
        $today = Carbon::today()->toDateString();

        if (! $this->canSelectDate()) {
            return $today;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($this->receiptDate))->toDateString();
        } catch (\Throwable) {
            return $today;
        }
    }

    /**
     * Receipt date carrying the current wall-clock time, so a back-dated
     * entry still records a sensible timestamp.
     */
    private function receiptDateTime(): Carbon
    {
        $now = Carbon::now();
        $date = Carbon::parse($this->resolveReceiptDate());

        return $date->isSameDay($now)
            ? $now
            : $date->setTimeFrom($now);
    }

    /**
     * MRN format: YmdHi + 4-digit suffix (YearMonthDateHourMin + millisec).
     * Example: 2026062323010001
     */
    private function generateMrn(): string
    {
        $now = Carbon::now();
        $base = $now->format('YmdHi');
        $suffix = (int) floor((int) $now->format('u') / 100);

        do {
            $mrn = $base.sprintf('%04d', $suffix);
            $suffix++;
            if ($suffix > 9999) {
                usleep(1000);
                $now = Carbon::now();
                $base = $now->format('YmdHi');
                $suffix = (int) floor((int) $now->format('u') / 100);
            }
        } while (TblPayment::where('mrn', $mrn)->exists());

        return $mrn;
    }

    private function syncRechargePolicy(): void
    {
        $this->mandatoryRechargeCustomer = AppSettings::mandatoryRechargeCustomer();

        if ($this->mandatoryRechargeCustomer) {
            $this->recharge = true;
        }
    }

    private function effectiveRecharge(): bool
    {
        if (AppSettings::mandatoryRechargeCustomer()) {
            $this->mandatoryRechargeCustomer = true;
            $this->recharge = true;

            return true;
        }

        $this->mandatoryRechargeCustomer = false;

        return $this->recharge;
    }

    private function formatAddress(object $row): string
    {
        $parts = array_filter([
            $row->flat_level ? "Flat/Level {$row->flat_level}" : null,
            $row->building_num ? "Building {$row->building_num}" : null,
            $row->building_name ?: null,
            $row->road_num ? "Road {$row->road_num}" : null,
            $row->road_name ?: null,
            $row->block_sector ? "Block {$row->block_sector}" : null,
            $row->area ?: null,
        ], fn ($part) => $part !== null && $part !== '');

        return implode(', ', $parts);
    }
}
