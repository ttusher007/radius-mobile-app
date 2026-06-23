<?php

namespace App\Livewire;

use App\Support\BillingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    /**
     * Cash/bank ledgers assigned to the user with their current balance
     * (journal Debit − Credit), mirroring DCM's Journal::netDueForLedger.
     *
     * @return array<int, object{id:int, name:string, balance:float}>
     */
    #[Computed]
    public function ledgers(): array
    {
        try {
            return DB::table('ledger_users as lu')
                ->join('ledgers as l', 'l.Ledger_Id', '=', 'lu.ledger_id')
                ->leftJoin('journal as j', 'j.ledger_id', '=', 'l.Ledger_Id')
                ->where('lu.user_id', auth()->id())
                ->groupBy('l.Ledger_Id', 'l.Ledger_Name')
                ->orderBy('l.Ledger_Name')
                ->selectRaw('l.Ledger_Id as id, l.Ledger_Name as name,
                    COALESCE(SUM(CASE WHEN j.posting = "D" THEN j.amount
                                      WHEN j.posting = "C" THEN -j.amount
                                      ELSE 0 END), 0) as balance')
                ->get()
                ->map(fn ($r) => (object) [
                    'id' => (int) $r->id,
                    'name' => $r->name,
                    'balance' => (float) $r->balance,
                ])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Due customers within the user's scope: count + total outstanding.
     */
    #[Computed]
    public function due(): object
    {
        try {
            $query = DB::table('radcheck as c')
                ->join('tblaccounts as a', 'a.id', '=', 'c.id')
                ->where('a.balance', '>', 0)
                ->where('c.tmpdel', 0);

            BillingScope::applyCustomerScope($query, 'c');

            $row = $query->selectRaw('COUNT(*) as cnt, COALESCE(SUM(a.balance), 0) as total')->first();

            return (object) [
                'count' => (int) ($row->cnt ?? 0),
                'total' => (float) ($row->total ?? 0),
            ];
        } catch (Throwable $e) {
            return (object) ['count' => 0, 'total' => 0.0];
        }
    }

    /**
     * Today's collection (money receipts) within the user's scope.
     */
    #[Computed]
    public function todayCollection(): object
    {
        try {
            $query = DB::table('tblpayment as p')
                ->join('radcheck as c', 'c.id', '=', 'p.cid')
                ->whereDate('p.col_date', now()->toDateString());

            BillingScope::applyCustomerScope($query, 'c');

            $row = $query->selectRaw('COUNT(*) as cnt, COALESCE(SUM(p.amt), 0) as total')->first();

            return (object) [
                'count' => (int) ($row->cnt ?? 0),
                'total' => (float) ($row->total ?? 0),
            ];
        } catch (Throwable $e) {
            return (object) ['count' => 0, 'total' => 0.0];
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard', [
            'today' => now()->toDateString(),
        ]);
    }
}
