<?php

namespace App\Livewire\Billing;

use App\Models\Radcheck;
use App\Support\ResellerPermissionHelper;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Money Receipt')]
class MoneyReceipt extends Component
{
    public int $customerId;

    public ?object $customer = null;

    public function mount(int $customer): void
    {
        $this->customerId = $customer;

        $record = Radcheck::query()
            ->where('radcheck.id', $customer)
            ->first([
                'id', 'username', 'clientname', 'clintcontactno',
                'allowpopid', 'resellerid', 'expiredate',
            ]);

        abort_unless($record, 404);
        abort_unless(
            ResellerPermissionHelper::hasPopPermission((int) $record->allowpopid),
            403,
            'You do not have access to this customer.'
        );

        $balance = (int) (DB::table('tblaccounts')->where('id', $customer)->value('balance') ?? 0);

        $this->customer = (object) [
            'id' => (int) $record->id,
            'username' => $record->username,
            'name' => $record->clientname,
            'contact' => $record->clintcontactno,
            'due_amount' => $balance,
        ];
    }

    public function render()
    {
        return view('livewire.billing.money-receipt');
    }
}
