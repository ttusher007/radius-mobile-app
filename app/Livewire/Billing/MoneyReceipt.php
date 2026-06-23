<?php

namespace App\Livewire\Billing;

use App\Services\DcmClient;
use App\Support\ResellerPermissionHelper;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Money Receipt')]
class MoneyReceipt extends Component
{
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

    /** Wizard step: form | confirm | done. */
    public string $step = 'form';

    public bool $processing = false;

    public ?array $result = null;

    public function mount(?int $customer = null): void
    {
        if ($customer !== null) {
            $this->customerId = (string) $customer;
            $this->loadCustomer();
        }
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
            'expiry_date' => $row->expiredate,
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
        $this->reset('customer', 'amount', 'searched', 'lookupError', 'result');
        $this->step = 'form';
        $this->customerId = '';
    }

    public function review(): void
    {
        $this->validate([
            'amount' => 'required|numeric|min:1',
            'ledgerId' => 'required',
        ], [
            'amount.required' => 'Enter the amount received.',
            'amount.min' => 'Amount must be at least 1.',
            'ledgerId.required' => 'Select a ledger.',
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

        $this->step = 'confirm';
    }

    public function back(): void
    {
        $this->step = 'form';
    }

    public function submit(): void
    {
        if ($this->processing || ! $this->customer) {
            return;
        }

        $this->processing = true;

        $response = app(DcmClient::class)->moneyReceipt([
            'customer_id' => (int) $this->customer['id'],
            'amount' => (float) $this->amount,
            'ledger_id' => (int) $this->ledgerId,
            'user_id' => (int) auth()->id(),
            'recharge' => $this->recharge,
        ]);

        $body = $response['body'];
        $this->result = [
            'ok' => $response['ok'] && ($body['status'] ?? false),
            'message' => $body['message'] ?? ($response['ok'] ? 'Completed.' : 'The request could not be completed.'),
            'data' => $body,
        ];

        $this->step = 'done';
        $this->processing = false;
    }

    public function newEntry(): void
    {
        $this->reset('customer', 'customerId', 'amount', 'searched', 'lookupError', 'result');
        $this->step = 'form';
    }

    public function render()
    {
        return view('livewire.billing.money-receipt');
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
