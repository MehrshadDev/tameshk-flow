<?php

namespace App\Admin;

use App\Credentials\Registry\CredentialRegistry;
use App\Models\Credential;
use App\Models\DiscountCode;
use App\Models\Node;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Tickets\Enums\TicketStatus;
use App\Workflow\Registry\NodeRegistry;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Morilog\Jalali\Jalalian;

final class AdminAnalyticsService
{
    private const TZ = 'Asia/Tehran';

    public function overviewMetrics(): array
    {
        $tz = new DateTimeZone(self::TZ);
        $nowTehran = Carbon::now($tz);
        $jNow = Jalalian::fromCarbon($nowTehran);

        $todayStart = $nowTehran->copy()->startOfDay()->utc();
        $yesterdayStart = $nowTehran->copy()->subDay()->startOfDay()->utc();
        $yesterdayEnd = $nowTehran->copy()->subDay()->endOfDay()->utc();

        $monthStart = $this->firstDayOfJalaliMonth($jNow, $tz)->toCarbon()->utc();
        $prevJMonth = $this->subtractJalaliMonths($jNow, 1);
        $prevMonthStart = $this->firstDayOfJalaliMonth($prevJMonth, $tz)->toCarbon()->utc();

        $yearStart = (new Jalalian($jNow->getYear(), 1, 1, 0, 0, 0, $tz))->toCarbon()->utc();

        $paidBase = Payment::query()
            ->where('status', 'paid')
            ->whereRaw('COALESCE(paid_at, created_at) IS NOT NULL');

        $todayRevenue = (int) (clone $paidBase)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$todayStart])
            ->sum('amount');

        $yesterdayRevenue = (int) (clone $paidBase)
            ->whereRaw('COALESCE(paid_at, created_at) >= ? AND COALESCE(paid_at, created_at) <= ?', [$yesterdayStart, $yesterdayEnd])
            ->sum('amount');

        $monthRevenue = (int) (clone $paidBase)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$monthStart])
            ->sum('amount');

        $prevMonthRevenue = (int) (clone $paidBase)
            ->whereRaw('COALESCE(paid_at, created_at) >= ? AND COALESCE(paid_at, created_at) < ?', [$prevMonthStart, $monthStart])
            ->sum('amount');

        $yearRevenue = (int) (clone $paidBase)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$yearStart])
            ->sum('amount');

        $totalRevenue = (int) (clone $paidBase)->sum('amount');
        $totalDiscountGiven = (int) (clone $paidBase)->sum('discount_amount');

        $todaySalesCount = (clone $paidBase)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$todayStart])
            ->count();

        $monthSalesCount = (clone $paidBase)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$monthStart])
            ->count();

        $yearSalesCount = (clone $paidBase)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$yearStart])
            ->count();

        $totalSalesCount = (clone $paidBase)->count();
        $paidOrdersCount = (clone $paidBase)->where('amount', '>', 0)->count();
        $averageOrderValue = $paidOrdersCount > 0 ? (int) round($totalRevenue / $paidOrdersCount) : 0;

        $totalUsers = User::query()->count();
        $usersToday = User::query()->where('created_at', '>=', $todayStart)->count();
        $usersYesterday = User::query()->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])->count();
        $usersThisMonth = User::query()->where('created_at', '>=', $monthStart)->count();
        $usersPrevMonth = User::query()->where('created_at', '>=', $prevMonthStart)->where('created_at', '<', $monthStart)->count();
        $usersThisYear = User::query()->where('created_at', '>=', $yearStart)->count();
        $googleUsersCount = User::query()->whereNotNull('google_id')->count();
        $verifiedUsersCount = User::query()->whereNotNull('email_verified_at')->count();

        $activeSubscriptionsCount = Subscription::query()->valid()->count();
        $payingUsersCount = Subscription::query()->valid()->distinct('user_id')->count('user_id');
        $conversionRate = $totalUsers > 0 ? round(($payingUsersCount / $totalUsers) * 100, 1) : 0.0;

        $totalWorkflows = Workflow::query()->count();
        $activeWorkflows = Workflow::query()->whereNotNull('published_at')->where('is_active', true)->count();
        $totalNodes = Node::query()->where('type', '!=', 'sticky_note')->count();

        $totalRuns = WorkflowRun::query()->count();
        $runsToday = WorkflowRun::query()->where('created_at', '>=', $todayStart)->count();
        $runsThisMonth = WorkflowRun::query()->where('created_at', '>=', $monthStart)->count();
        $succeededRuns = WorkflowRun::query()->where('status', 'success')->count();
        $failedRuns = WorkflowRun::query()->where('status', 'failed')->count();
        $finishedRuns = $succeededRuns + $failedRuns;
        $runSuccessRate = $finishedRuns > 0 ? round(($succeededRuns / $finishedRuns) * 100, 1) : 100.0;

        $openTicketsCount = Ticket::query()
            ->whereIn('status', [TicketStatus::Open->value, TicketStatus::CustomerReply->value])
            ->count();

        return [
            'current_jalali_month_label' => $jNow->format('%B %Y'),
            'current_jalali_year_label' => $jNow->format('%Y'),

            'revenue' => [
                'today' => $todayRevenue,
                'yesterday' => $yesterdayRevenue,
                'month' => $monthRevenue,
                'prev_month' => $prevMonthRevenue,
                'month_growth' => $this->growthPercent($monthRevenue, $prevMonthRevenue),
                'year' => $yearRevenue,
                'total' => $totalRevenue,
                'total_discount' => $totalDiscountGiven,
                'aov' => $averageOrderValue,
                'today_count' => $todaySalesCount,
                'month_count' => $monthSalesCount,
                'year_count' => $yearSalesCount,
                'total_count' => $totalSalesCount,
            ],

            'users' => [
                'total' => $totalUsers,
                'today' => $usersToday,
                'yesterday' => $usersYesterday,
                'month' => $usersThisMonth,
                'prev_month' => $usersPrevMonth,
                'month_growth' => $this->growthPercent($usersThisMonth, $usersPrevMonth),
                'year' => $usersThisYear,
                'google_count' => $googleUsersCount,
                'email_count' => max(0, $totalUsers - $googleUsersCount),
                'verified_count' => $verifiedUsersCount,
                'active_subscriptions' => $activeSubscriptionsCount,
                'paying_users' => $payingUsersCount,
                'conversion_rate' => $conversionRate,
            ],

            'platform' => [
                'total_workflows' => $totalWorkflows,
                'active_workflows' => $activeWorkflows,
                'total_nodes' => $totalNodes,
                'total_runs' => $totalRuns,
                'runs_today' => $runsToday,
                'runs_month' => $runsThisMonth,
                'succeeded_runs' => $succeededRuns,
                'failed_runs' => $failedRuns,
                'run_success_rate' => $runSuccessRate,
                'open_tickets' => $openTicketsCount,
                'total_tickets' => Ticket::query()->count(),
                'total_credentials' => Credential::query()->count(),
                'active_discounts' => DiscountCode::query()->where('is_active', true)->count(),
                'pending_jobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
                'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            ],
        ];
    }

    public function revenueTimeSeries(string $range = '30d'): array
    {
        return match ($range) {
            '7d' => $this->dailyRevenueSeries(7),
            '12m' => $this->monthlyJalaliRevenueSeries(12),
            default => $this->dailyRevenueSeries(30),
        };
    }

    public function userRegistrationsTimeSeries(string $range = '30d'): array
    {
        return match ($range) {
            '7d' => $this->dailyUsersSeries(7),
            '12m' => $this->monthlyJalaliUsersSeries(12),
            default => $this->dailyUsersSeries(30),
        };
    }

    public function breakdownInsights(): array
    {
        $paidByPlan = Payment::query()
            ->select('plan_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(amount) as total_amount'))
            ->where('status', 'paid')
            ->with('plan:id,name,slug')
            ->groupBy('plan_id')
            ->get();

        $totalRevenue = max(1, (int) $paidByPlan->sum('total_amount'));

        $planBreakdown = $paidByPlan->map(function ($row) use ($totalRevenue) {
            $amount = (int) $row->total_amount;

            return [
                'name' => $row->plan?->name ?? 'نامشخص',
                'slug' => $row->plan?->slug ?? 'unknown',
                'orders_count' => (int) $row->orders_count,
                'amount' => $amount,
                'percent' => (int) round(($amount / $totalRevenue) * 100),
            ];
        })->sortByDesc('amount')->values()->all();

        $cycleRows = Payment::query()
            ->select('billing_cycle', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(amount) as total_amount'))
            ->where('status', 'paid')
            ->groupBy('billing_cycle')
            ->get()
            ->keyBy('billing_cycle');

        $monthlyAmount = (int) ($cycleRows->get('monthly')?->total_amount ?? 0);
        $yearlyAmount = (int) ($cycleRows->get('yearly')?->total_amount ?? 0);
        $monthlyCount = (int) ($cycleRows->get('monthly')?->orders_count ?? 0);
        $yearlyCount = (int) ($cycleRows->get('yearly')?->orders_count ?? 0);
        $totalCycleCount = max(1, $monthlyCount + $yearlyCount);

        $nodeDefinitions = app(NodeRegistry::class)->definitions();
        $topNodesRaw = Node::query()
            ->select('type', DB::raw('COUNT(*) as usage_count'))
            ->where('type', '!=', 'sticky_note')
            ->groupBy('type')
            ->orderByDesc('usage_count')
            ->limit(6)
            ->get();

        $maxNodeUsage = max(1, (int) ($topNodesRaw->first()?->usage_count ?? 1));

        $topNodes = $topNodesRaw->map(function ($row) use ($nodeDefinitions, $maxNodeUsage) {
            $def = $nodeDefinitions[$row->type] ?? null;

            return [
                'type' => $row->type,
                'name' => $def?->name ?? $row->type,
                'icon' => $def?->icon ?? 'box',
                'count' => (int) $row->usage_count,
                'percent' => (int) round(((int) $row->usage_count / $maxNodeUsage) * 100),
            ];
        })->all();

        $credDefinitions = collect(app(CredentialRegistry::class)->definitionsAsArray())->keyBy('key');
        $topCredentialsRaw = Credential::query()
            ->select('type', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('type')
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get();

        $topCredentials = $topCredentialsRaw->map(function ($row) use ($credDefinitions) {
            $def = $credDefinitions->get($row->type);

            return [
                'type' => $row->type,
                'name' => $def['name'] ?? $row->type,
                'icon' => $def['icon'] ?? 'key',
                'count' => (int) $row->usage_count,
            ];
        })->all();

        return [
            'plans' => $planBreakdown,
            'cycles' => [
                'monthly' => [
                    'count' => $monthlyCount,
                    'amount' => $monthlyAmount,
                    'percent' => (int) round(($monthlyCount / $totalCycleCount) * 100),
                ],
                'yearly' => [
                    'count' => $yearlyCount,
                    'amount' => $yearlyAmount,
                    'percent' => (int) round(($yearlyCount / $totalCycleCount) * 100),
                ],
            ],
            'top_nodes' => $topNodes,
            'top_credentials' => $topCredentials,
        ];
    }

    private function dailyRevenueSeries(int $days): array
    {
        $tz = new DateTimeZone(self::TZ);
        $nowTehran = Carbon::now($tz);
        $startTehran = $nowTehran->copy()->subDays($days - 1)->startOfDay();

        $payments = Payment::query()
            ->where('status', 'paid')
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$startTehran->copy()->utc()])
            ->get(['amount', 'paid_at', 'created_at']);

        $buckets = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $startTehran->copy()->addDays($i);
            $key = $day->format('Y-m-d');
            $jDay = Jalalian::fromCarbon($day);

            $buckets[$key] = [
                'key' => $key,
                'label' => $jDay->format('Y/m/d'),
                'short_label' => $jDay->format('m/d'),
                'amount' => 0,
                'count' => 0,
            ];
        }

        foreach ($payments as $payment) {
            $dt = ($payment->paid_at ?? $payment->created_at)?->copy()->setTimezone($tz);
            if (! $dt) {
                continue;
            }

            $key = $dt->format('Y-m-d');
            if (isset($buckets[$key])) {
                $buckets[$key]['amount'] += (int) $payment->amount;
                $buckets[$key]['count']++;
            }
        }

        $points = array_values($buckets);
        $maxAmount = max(1, ...array_column($points, 'amount'));

        return [
            'points' => $points,
            'total_amount' => array_sum(array_column($points, 'amount')),
            'total_count' => array_sum(array_column($points, 'count')),
            'max_amount' => $maxAmount,
        ];
    }

    private function monthlyJalaliRevenueSeries(int $months): array
    {
        $tz = new DateTimeZone(self::TZ);
        $jNow = Jalalian::fromCarbon(Carbon::now($tz));

        $buckets = [];
        $earliestUtc = null;

        for ($i = $months - 1; $i >= 0; $i--) {
            $jMonth = $this->subtractJalaliMonths($jNow, $i);
            $firstDay = $this->firstDayOfJalaliMonth($jMonth, $tz);

            if ($earliestUtc === null) {
                $earliestUtc = $firstDay->toCarbon()->utc();
            }

            $key = sprintf('%04d-%02d', $jMonth->getYear(), $jMonth->getMonth());
            $buckets[$key] = [
                'key' => $key,
                'label' => $firstDay->format('%B %Y'),
                'short_label' => $firstDay->format('%B'),
                'amount' => 0,
                'count' => 0,
            ];
        }

        $payments = Payment::query()
            ->where('status', 'paid')
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$earliestUtc])
            ->get(['amount', 'paid_at', 'created_at']);

        foreach ($payments as $payment) {
            $dt = ($payment->paid_at ?? $payment->created_at)?->copy()->setTimezone($tz);
            if (! $dt) {
                continue;
            }

            $jDt = Jalalian::fromCarbon($dt);
            $key = sprintf('%04d-%02d', $jDt->getYear(), $jDt->getMonth());

            if (isset($buckets[$key])) {
                $buckets[$key]['amount'] += (int) $payment->amount;
                $buckets[$key]['count']++;
            }
        }

        $points = array_values($buckets);
        $maxAmount = max(1, ...array_column($points, 'amount'));

        return [
            'points' => $points,
            'total_amount' => array_sum(array_column($points, 'amount')),
            'total_count' => array_sum(array_column($points, 'count')),
            'max_amount' => $maxAmount,
        ];
    }

    private function dailyUsersSeries(int $days): array
    {
        $tz = new DateTimeZone(self::TZ);
        $nowTehran = Carbon::now($tz);
        $startTehran = $nowTehran->copy()->subDays($days - 1)->startOfDay();

        $users = User::query()
            ->where('created_at', '>=', $startTehran->copy()->utc())
            ->get(['created_at', 'google_id']);

        $buckets = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $startTehran->copy()->addDays($i);
            $key = $day->format('Y-m-d');
            $jDay = Jalalian::fromCarbon($day);

            $buckets[$key] = [
                'key' => $key,
                'label' => $jDay->format('Y/m/d'),
                'short_label' => $jDay->format('m/d'),
                'count' => 0,
                'google_count' => 0,
            ];
        }

        foreach ($users as $user) {
            $dt = $user->created_at?->copy()->setTimezone($tz);
            if (! $dt) {
                continue;
            }

            $key = $dt->format('Y-m-d');
            if (isset($buckets[$key])) {
                $buckets[$key]['count']++;
                if ($user->google_id !== null) {
                    $buckets[$key]['google_count']++;
                }
            }
        }

        $points = array_values($buckets);
        $maxCount = max(1, ...array_column($points, 'count'));

        return [
            'points' => $points,
            'total_count' => array_sum(array_column($points, 'count')),
            'max_count' => $maxCount,
        ];
    }

    private function monthlyJalaliUsersSeries(int $months): array
    {
        $tz = new DateTimeZone(self::TZ);
        $jNow = Jalalian::fromCarbon(Carbon::now($tz));

        $buckets = [];
        $earliestUtc = null;

        for ($i = $months - 1; $i >= 0; $i--) {
            $jMonth = $this->subtractJalaliMonths($jNow, $i);
            $firstDay = $this->firstDayOfJalaliMonth($jMonth, $tz);

            if ($earliestUtc === null) {
                $earliestUtc = $firstDay->toCarbon()->utc();
            }

            $key = sprintf('%04d-%02d', $jMonth->getYear(), $jMonth->getMonth());
            $buckets[$key] = [
                'key' => $key,
                'label' => $firstDay->format('%B %Y'),
                'short_label' => $firstDay->format('%B'),
                'count' => 0,
                'google_count' => 0,
            ];
        }

        $users = User::query()
            ->where('created_at', '>=', $earliestUtc)
            ->get(['created_at', 'google_id']);

        foreach ($users as $user) {
            $dt = $user->created_at?->copy()->setTimezone($tz);
            if (! $dt) {
                continue;
            }

            $jDt = Jalalian::fromCarbon($dt);
            $key = sprintf('%04d-%02d', $jDt->getYear(), $jDt->getMonth());

            if (isset($buckets[$key])) {
                $buckets[$key]['count']++;
                if ($user->google_id !== null) {
                    $buckets[$key]['google_count']++;
                }
            }
        }

        $points = array_values($buckets);
        $maxCount = max(1, ...array_column($points, 'count'));

        return [
            'points' => $points,
            'total_count' => array_sum(array_column($points, 'count')),
            'max_count' => $maxCount,
        ];
    }

    private function subtractJalaliMonths(Jalalian $date, int $months): Jalalian
    {
        return $months > 0 ? $date->subMonths($months) : $date;
    }

    private function firstDayOfJalaliMonth(Jalalian $date, DateTimeZone $tz): Jalalian
    {
        return new Jalalian($date->getYear(), $date->getMonth(), 1, 0, 0, 0, $tz);
    }

    private function growthPercent(int|float $current, int|float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
