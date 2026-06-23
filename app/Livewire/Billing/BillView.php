<?php

namespace App\Livewire\Billing;

use App\Models\Pop;
use App\Models\Reseller;
use App\Support\ResellerPermissionHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Bill View')]
class BillView extends Component
{
    use WithPagination;

    /** Selected manager (reseller) id, or 'all'. */
    #[Url(as: 'manager')]
    public string $managerId = 'all';

    /** Selected POP id, or 'all'. */
    #[Url(as: 'pop')]
    public string $popId = 'all';

    /** Selected area (radcheck.area free-text), or 'all'. */
    #[Url(as: 'area')]
    public string $area = 'all';

    /** Connection status filter: all | enable | disable. */
    #[Url(as: 'status')]
    public string $status = 'all';

    /** Free-text search across id / username / name / contact. */
    #[Url(as: 'q')]
    public string $search = '';

    public int $perPage = 25;

    private bool|array|null $resellerScopeMemo = null;

    private bool|array|null $popScopeMemo = null;

    public function mount(): void
    {
        $reseller = $this->resellerScope();
        $pop = $this->popScope();

        $hasScope = $reseller === true
            || (is_array($reseller) && $reseller !== [])
            || (is_array($pop) && $pop !== []);

        abort_unless($hasScope, 403, 'You do not have access to any managers or POPs.');
    }

    // ── Filter change handlers (cascade resets) ──────────────────────────

    public function updatedManagerId(): void
    {
        $this->reset('popId', 'area');
        $this->resetPage();
    }

    public function updatedPopId(): void
    {
        $this->reset('area');
        $this->resetPage();
    }

    public function updatedArea(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('managerId', 'popId', 'area', 'status', 'search');
        $this->resetPage();
    }

    // ── Dropdown option sources ──────────────────────────────────────────

    /**
     * @return array<int, object{id:int, name:string}>
     */
    #[Computed]
    public function managers(): array
    {
        $scope = $this->effectiveResellerScope();

        return Reseller::query()
            ->active()
            ->when($scope !== true, fn ($q) => $q->whereIn('id', $scope ?: [-1]))
            ->orderBy('resellername')
            ->get(['id', 'resellername as name'])
            ->all();
    }

    /**
     * POPs for the selected manager, limited to the user's POP scope.
     *
     * @return array<int, object{id:int, name:string}>
     */
    #[Computed]
    public function pops(): array
    {
        $scope = $this->popScope();

        return Pop::query()
            ->active()
            ->when($scope !== true, fn ($q) => $q->whereIn('id', $scope ?: [-1]))
            ->when($this->managerId !== 'all', fn ($q) => $q->where('allowresellerid', (int) $this->managerId))
            ->orderBy('popname')
            ->get(['id', 'popname as name'])
            ->all();
    }

    /**
     * Distinct, non-empty areas within the current manager / POP scope.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function areas(): array
    {
        return $this->scopedCustomerQuery(applyArea: false, applyStatus: false, applySearch: false)
            ->whereNotNull('c.area')
            ->where('c.area', '<>', '')
            ->distinct()
            ->orderBy('c.area')
            ->pluck('c.area')
            ->all();
    }

    // ── Result set ───────────────────────────────────────────────────────

    #[Computed]
    public function summary(): object
    {
        $row = $this->scopedCustomerQuery()
            ->selectRaw('COUNT(*) as customer_count, COALESCE(SUM(a.balance), 0) as due_total')
            ->first();

        return (object) [
            'customer_count' => (int) ($row->customer_count ?? 0),
            'due_total' => (float) ($row->due_total ?? 0),
        ];
    }

    public function customers()
    {
        return $this->scopedCustomerQuery()
            ->select([
                'c.id',
                'c.username',
                'c.clientname',
                'c.clintcontactno',
                'c.expiredate',
                'c.enableuser',
                'c.area',
                'c.flat_level',
                'c.building_num',
                'c.building_name',
                'c.road_num',
                'c.road_name',
                'c.block_sector',
                'c.discount',
                'c.ip_bill',
                'c.extra_bill',
                'a.balance',
                'p.packagename',
                'p.packagerate',
            ])
            ->orderBy('c.id')
            ->paginate($this->perPage)
            ->through(fn ($row) => $this->presentCustomer($row));
    }

    public function render()
    {
        return view('livewire.billing.bill-view', [
            'customers' => $this->customers(),
        ]);
    }

    // ── Query building ───────────────────────────────────────────────────

    /**
     * Base query: customers with a positive balance (due), restricted to the
     * user's permission scope plus the active manager / POP / area / status /
     * search filters.
     */
    private function scopedCustomerQuery(
        bool $applyArea = true,
        bool $applyStatus = true,
        bool $applySearch = true,
    ): Builder {
        $query = DB::table('radcheck as c')
            ->join('tblaccounts as a', 'a.id', '=', 'c.id')
            ->leftJoin('uz_package as p', 'p.id', '=', 'c.packageid')
            ->where('a.balance', '>', 0)
            ->where('c.tmpdel', 0);

        // Permission scope. Apply whichever (reseller / pop) restriction is
        // present; a non-empty array narrows results, an absent one is skipped.
        $reseller = $this->resellerScope();
        if (is_array($reseller) && $reseller !== []) {
            $query->whereIn('c.resellerid', $reseller);
        }
        $pop = $this->popScope();
        if (is_array($pop) && $pop !== []) {
            $query->whereIn('c.allowpopid', $pop);
        }

        // Selected manager — only honoured if within the user's scope.
        if ($this->managerId !== 'all'
            && ResellerPermissionHelper::hasResellerPermission((int) $this->managerId)) {
            $query->where('c.resellerid', (int) $this->managerId);
        }

        // Selected POP — only honoured if within the user's scope.
        if ($this->popId !== 'all'
            && ResellerPermissionHelper::hasPopPermission((int) $this->popId)) {
            $query->where('c.allowpopid', (int) $this->popId);
        }

        if ($applyArea && $this->area !== 'all') {
            $query->where('c.area', $this->area);
        }

        if ($applyStatus && $this->status !== 'all') {
            $query->where('c.enableuser', $this->status === 'enable' ? 1 : 0);
        }

        if ($applySearch && trim($this->search) !== '') {
            $term = trim($this->search);
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('c.username', 'like', "%{$term}%")
                    ->orWhere('c.clientname', 'like', "%{$term}%")
                    ->orWhere('c.clintcontactno', 'like', "%{$term}%")
                    ->orWhere('c.id', $term);
            });
        }

        return $query;
    }

    private function presentCustomer(object $row): object
    {
        $billAmount = (int) $row->packagerate
            - (int) $row->discount
            + (int) ($row->ip_bill ?? 0)
            + (int) ($row->extra_bill ?? 0);

        return (object) [
            'id' => (int) $row->id,
            'username' => $row->username,
            'name' => $row->clientname,
            'contact' => $row->clintcontactno,
            'address' => $this->formatAddress($row),
            'area' => $row->area,
            'package' => $row->packagename,
            'expiry_date' => $row->expiredate,
            'bill_amount' => max(0, $billAmount),
            'due_amount' => (float) $row->balance,
            'enabled' => (int) $row->enableuser === 1,
        ];
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

    // ── Permission scope (memoised per request) ──────────────────────────

    private function resellerScope(): bool|array
    {
        return $this->resellerScopeMemo ??= ResellerPermissionHelper::getResellerIds();
    }

    private function popScope(): bool|array
    {
        return $this->popScopeMemo ??= ResellerPermissionHelper::getPopIds();
    }

    /**
     * Reseller scope for the manager dropdown. When the user is POP-scoped
     * only (asst. manager with no reseller assignment), derive the owning
     * resellers from their permitted POPs so the dropdown stays meaningful.
     */
    private function effectiveResellerScope(): bool|array
    {
        $reseller = $this->resellerScope();
        if ($reseller === true) {
            return true;
        }
        if ($reseller !== []) {
            return $reseller;
        }

        $pop = $this->popScope();
        if ($pop === true) {
            return true;
        }
        if ($pop === []) {
            return [];
        }

        return Pop::whereIn('id', $pop)
            ->pluck('allowresellerid')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
