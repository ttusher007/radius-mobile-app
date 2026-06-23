<?php

namespace App\Livewire\Reports;

use App\Models\Pop;
use App\Models\Reseller;
use App\Support\AccessHelper;
use App\Support\BillingScope;
use App\Support\ResellerPermissionHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Mobile-friendly customer payment (collection) report — the PWA counterpart
 * of DCM's report/mac-payment, using the same RBAC scope.
 */
#[Layout('components.layouts.app')]
#[Title('Collection Report')]
class CollectionReport extends Component
{
    use WithPagination;

    /** Permissions that may open this report. */
    private const PERMS = ['report_mac-payment', 'super-admin', 'perm_all_manager'];

    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    #[Url(as: 'manager')]
    public string $managerId = 'all';

    #[Url(as: 'pop')]
    public string $popId = 'all';

    public int $perPage = 30;

    public function mount(): void
    {
        abort_unless(AccessHelper::any(self::PERMS) && BillingScope::hasAnyScope(), 403,
            'You do not have access to the collection report.');

        $today = now()->toDateString();
        if ($this->from === '' || ! $this->isValidDate($this->from)) {
            $this->from = $today;
        }
        if ($this->to === '' || ! $this->isValidDate($this->to)) {
            $this->to = $today;
        }
        $this->normaliseDates();
    }

    public function updatedFrom(): void
    {
        $this->normaliseDates();
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->normaliseDates();
        $this->resetPage();
    }

    public function updatedManagerId(): void
    {
        $this->reset('popId');
        $this->resetPage();
    }

    public function updatedPopId(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<int, object{id:int, name:string}>
     */
    #[Computed]
    public function managers(): array
    {
        $scope = ResellerPermissionHelper::getResellerIds();

        return Reseller::query()
            ->active()
            ->when($scope !== true, fn ($q) => $q->whereIn('id', is_array($scope) && $scope !== [] ? $scope : [-1]))
            ->orderBy('resellername')
            ->get(['id', 'resellername as name'])
            ->all();
    }

    /**
     * @return array<int, object{id:int, name:string}>
     */
    #[Computed]
    public function pops(): array
    {
        $scope = ResellerPermissionHelper::getPopIds();

        return Pop::query()
            ->active()
            ->when($scope !== true, fn ($q) => $q->whereIn('id', is_array($scope) && $scope !== [] ? $scope : [-1]))
            ->when($this->managerId !== 'all', fn ($q) => $q->where('allowresellerid', (int) $this->managerId))
            ->orderBy('popname')
            ->get(['id', 'popname as name'])
            ->all();
    }

    #[Computed]
    public function summary(): object
    {
        $row = $this->baseQuery()
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(p.amt), 0) as total')
            ->first();

        return (object) [
            'count' => (int) ($row->cnt ?? 0),
            'total' => (float) ($row->total ?? 0),
        ];
    }

    /**
     * Per-ledger totals for the current filter (collectors often use several).
     *
     * @return array<int, object{name:string, total:float, count:int}>
     */
    #[Computed]
    public function ledgerBreakdown(): array
    {
        return $this->baseQuery()
            ->groupBy('l.Ledger_Name')
            ->orderByDesc(DB::raw('SUM(p.amt)'))
            ->get([
                DB::raw('COALESCE(l.Ledger_Name, "—") as name'),
                DB::raw('COALESCE(SUM(p.amt), 0) as total'),
                DB::raw('COUNT(*) as cnt'),
            ])
            ->map(fn ($r) => (object) [
                'name' => $r->name,
                'total' => (float) $r->total,
                'count' => (int) $r->cnt,
            ])
            ->all();
    }

    public function payments()
    {
        return $this->baseQuery()
            ->select([
                'p.id', 'p.cid', 'p.col_date', 'p.mrn', 'p.col_by', 'p.amt',
                'c.username', 'c.clientname',
                'pl.popname', 'r.resellername', 'l.Ledger_Name as ledger_name',
            ])
            ->orderByDesc('p.col_date')
            ->orderByDesc('p.id')
            ->paginate($this->perPage)
            ->through(fn ($row) => (object) [
                'id' => (int) $row->id,
                'cid' => (int) $row->cid,
                'date' => $row->col_date,
                'customer' => $row->clientname ?: $row->username,
                'username' => $row->username,
                'manager' => $row->resellername,
                'pop' => $row->popname,
                'mrn' => $row->mrn,
                'col_by' => $row->col_by,
                'ledger' => $row->ledger_name ?: '—',
                'amount' => (float) $row->amt,
            ]);
    }

    public function render()
    {
        return view('livewire.reports.collection-report', [
            'payments' => $this->payments(),
        ]);
    }

    /**
     * Base collection query: payments in range, scoped to the user, plus the
     * selected manager / POP filters (each validated against scope).
     */
    private function baseQuery(): Builder
    {
        $query = DB::table('tblpayment as p')
            ->join('radcheck as c', 'c.id', '=', 'p.cid')
            ->leftJoin('uz_poplist as pl', 'pl.id', '=', 'c.allowpopid')
            ->leftJoin('uz_resellers as r', 'r.id', '=', 'c.resellerid')
            ->leftJoin('ledgers as l', 'l.Ledger_Id', '=', 'p.ledger_id')
            ->whereBetween('p.col_date', [$this->from, $this->to]);

        BillingScope::applyCustomerScope($query, 'c');

        if ($this->managerId !== 'all'
            && ResellerPermissionHelper::hasResellerPermission((int) $this->managerId)) {
            $query->where('c.resellerid', (int) $this->managerId);
        }

        if ($this->popId !== 'all'
            && ResellerPermissionHelper::hasPopPermission((int) $this->popId)) {
            $query->where('c.allowpopid', (int) $this->popId);
        }

        return $query;
    }

    private function normaliseDates(): void
    {
        if (! $this->isValidDate($this->from)) {
            $this->from = now()->toDateString();
        }
        if (! $this->isValidDate($this->to)) {
            $this->to = now()->toDateString();
        }
        if ($this->from > $this->to) {
            $this->to = $this->from;
        }
    }

    private function isValidDate(string $date): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        try {
            Carbon::parse($date);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
