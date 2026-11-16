<?php

use App\Admin\AdminAnalyticsService;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(as: 'pq', except: '')]
    public string $paymentSearch = '';

    #[Url(as: 'pstatus', except: 'paid')]
    public string $paymentStatus = 'paid';

    #[Url(as: 'uq', except: '')]
    public string $userSearch = '';

    #[Url(as: 'ufilter', except: 'all')]
    public string $userFilter = 'all';

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function mount(): void
    {
        $this->paymentStatus = in_array($this->paymentStatus, ['all', 'paid', 'pending', 'failed'], true) ? $this->paymentStatus : 'paid';
        $this->userFilter = in_array($this->userFilter, ['all', 'subscribed', 'google', 'today'], true) ? $this->userFilter : 'all';
    }

    public function updatedPaymentSearch(): void
    {
        $this->paymentSearch = mb_substr(trim($this->paymentSearch), 0, 100);
        $this->resetPage('paymentsPage');
    }

    public function setPaymentStatus(string $status): void
    {
        if (! in_array($status, ['all', 'paid', 'pending', 'failed'], true)) {
            return;
        }

        $this->paymentStatus = $status;
        $this->resetPage('paymentsPage');
    }

    public function updatedUserSearch(): void
    {
        $this->userSearch = mb_substr(trim($this->userSearch), 0, 100);
        $this->resetPage('usersPage');
    }

    public function setUserFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'subscribed', 'google', 'today'], true)) {
            return;
        }

        $this->userFilter = $filter;
        $this->resetPage('usersPage');
    }

    public function refreshStats(): void
    {
        unset(
            $this->overview,
            $this->allRevenueSeries,
            $this->allUsersSeries,
            $this->insights,
            $this->payments,
            $this->users
        );

        $this->dispatch('notify', message: 'آمار و گزارش‌ها بروزرسانی شد.', type: 'success');
    }

    #[Computed]
    public function overview(): array
    {
        return app(AdminAnalyticsService::class)->overviewMetrics();
    }

    #[Computed]
    public function allRevenueSeries(): array
    {
        $service = app(AdminAnalyticsService::class);

        return [
            '7d' => $service->revenueTimeSeries('7d'),
            '30d' => $service->revenueTimeSeries('30d'),
            '12m' => $service->revenueTimeSeries('12m'),
        ];
    }

    #[Computed]
    public function allUsersSeries(): array
    {
        $service = app(AdminAnalyticsService::class);

        return [
            '7d' => $service->userRegistrationsTimeSeries('7d'),
            '30d' => $service->userRegistrationsTimeSeries('30d'),
            '12m' => $service->userRegistrationsTimeSeries('12m'),
        ];
    }

    #[Computed]
    public function insights(): array
    {
        return app(AdminAnalyticsService::class)->breakdownInsights();
    }

    #[Computed]
    public function payments()
    {
        $term = trim($this->paymentSearch);

        return Payment::query()
            ->with(['user:id,name,email', 'plan:id,name,slug', 'discountCode:id,code,title'])
            ->when($this->paymentStatus !== 'all', fn (Builder $q) => $q->where('status', $this->paymentStatus))
            ->when($term !== '', function (Builder $q) use ($term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(function (Builder $sub) use ($like) {
                    $sub->where('ref_id', 'like', $like)
                        ->orWhere('public_id', 'like', $like)
                        ->orWhere('card_pan', 'like', $like)
                        ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like));
                });
            })
            ->latest('id')
            ->paginate(10, ['*'], 'paymentsPage');
    }

    #[Computed]
    public function users()
    {
        $term = trim($this->userSearch);
        $todayStart = Carbon::now('Asia/Tehran')->startOfDay()->utc();

        return User::query()
            ->with(['activeSubscription.plan'])
            ->withCount([
                'workflows',
                'workflows as active_workflows_count' => fn (Builder $q) => $q->whereNotNull('published_at')->where('is_active', true),
            ])
            ->when($this->userFilter === 'subscribed', fn (Builder $q) => $q->whereHas('activeSubscription'))
            ->when($this->userFilter === 'google', fn (Builder $q) => $q->whereNotNull('google_id'))
            ->when($this->userFilter === 'today', fn (Builder $q) => $q->where('created_at', '>=', $todayStart))
            ->when($term !== '', function (Builder $q) use ($term) {
                $cleanId = ltrim($term, '#');
                $like = '%' . addcslashes($term, '%_\\') . '%';

                $q->where(function (Builder $sub) use ($like, $cleanId) {
                    if ($cleanId !== '' && ctype_digit($cleanId)) {
                        $sub->orWhere('id', (int) $cleanId);
                    }
                    $sub->orWhere('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->latest('id')
            ->paginate(10, ['*'], 'usersPage');
    }
};
?>

<div x-data="{
         activeTab: 'payments',
         chartRange: '30d'
     }"
     class="mx-auto max-w-6xl space-y-5 p-3.5 sm:space-y-6 sm:p-6">
    @php
        $overview = $this->overview;
    @endphp

    <div class="flex items-center justify-between gap-3 rounded-2xl border border-line-border bg-surface-card px-4 py-3 shadow-2xs">
        <div class="flex min-w-0 items-center gap-2.5">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <x-ui.icon name="chart-area-line" class="size-5"/>
            </span>
            <div class="min-w-0">
                <h2 class="truncate text-xs font-extrabold text-content-main sm:text-sm">مرکز آمار و گزارش‌های کلان تمشک فلو</h2>
                <p class="hidden truncate text-[11px] text-content-muted sm:block">
                    بررسی زنده درآمد ماهانه و سالانه، روند جذب کاربران و سلامت زیرساخت
                </p>
            </div>
        </div>

        <button type="button"
                wire:click="refreshStats"
                wire:loading.attr="disabled"
                wire:target="refreshStats"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-line-border bg-surface-base px-3 py-2 text-xs font-bold text-content-main transition hover:border-primary/40 hover:text-primary cursor-pointer disabled:opacity-50">
            <x-ui.icon name="refresh" wire:loading.class="animate-spin text-primary" wire:target="refreshStats" class="size-4"/>
            <span class="hidden sm:inline">بروزرسانی آمار</span>
        </button>
    </div>

    <x-admin.kpi-cards :metrics="$overview" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-admin.revenue-chart :all-series="$this->allRevenueSeries" />

        <x-admin.users-chart
            :all-series="$this->allUsersSeries"
            :users-metrics="$overview['users']"
        />
    </div>

    <x-admin.platform-insights
        :insights="$this->insights"
        :platform="$overview['platform']"
        :revenue="$overview['revenue']"
    />

    <div class="space-y-4 pt-2">
        <div class="flex items-center justify-between border-b border-line-border pb-3">
            <x-ui.segmented
                alpine="activeTab"
                variant="card"
                size="lg"
                cols="2"
                class="w-full sm:w-auto"
                :options="[
                    'payments' => [
                        'label' => 'تراکنش‌ها و خریدها',
                        'icon' => 'receipt',
                        'count' => $overview['revenue']['total_count'],
                    ],
                    'users' => [
                        'label' => 'لیست کاربران',
                        'icon' => 'users',
                        'count' => $overview['users']['total'],
                    ],
                ]"
            />
        </div>

        <div x-show="activeTab === 'payments'">
            <x-admin.payments-table
                :payments="$this->payments"
                :payment-search="$paymentSearch"
                :payment-status="$paymentStatus"
            />
        </div>

        <div x-show="activeTab === 'users'" x-cloak>
            <x-admin.users-table
                :users="$this->users"
                :user-search="$userSearch"
                :user-filter="$userFilter"
            />
        </div>
    </div>
</div>
