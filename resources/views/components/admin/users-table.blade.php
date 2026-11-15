@props([
    'users',
    'userSearch' => '',
    'userFilter' => 'all',
])

@php
    use App\Workflow\Support\DateTimeHelper;
@endphp

<div x-data="{ localFilter: @entangle('userFilter') }" class="space-y-4">
    <div class="flex flex-col gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex-1">
            <x-ui.icon name="search"
                       class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
            <input type="text"
                   maxlength="100"
                   wire:model.live.debounce.300ms="userSearch"
                   placeholder="جستجو در نام، ایمیل یا شناسه کاربر..."
                   class="h-9.5 w-full rounded-xl border border-line-border bg-surface-base pe-9 ps-10 text-xs font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"/>
            @if ($userSearch !== '')
                <button type="button"
                        wire:click="$set('userSearch', '')"
                        class="absolute inset-e-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                    <x-ui.icon name="x" class="size-3.5"/>
                </button>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-1 rounded-xl border border-line-border bg-surface-base p-1 sm:flex sm:shrink-0">
            @foreach (['all' => 'همه کاربران', 'subscribed' => 'دارای اشتراک', 'google' => 'ورود با گوگل', 'today' => 'ثبت‌نام امروز'] as $key => $label)
                <button type="button"
                        @click="if (localFilter !== '{{ $key }}') { localFilter = '{{ $key }}'; $wire.setUserFilter('{{ $key }}'); }"
                        :class="localFilter === '{{ $key }}'
                            ? 'cursor-default bg-primary text-white font-bold shadow-2xs'
                            : 'cursor-pointer font-medium text-content-muted hover:text-content-main'"
                        class="rounded-lg px-2.5 py-1.5 text-center text-xs whitespace-nowrap transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="relative min-h-40">
        <div wire:loading.flex
             wire:target="setUserFilter, userSearch, previousPage, nextPage, gotoPage"
             class="absolute inset-0 z-20 hidden items-center justify-center rounded-2xl bg-surface-base/65 backdrop-blur-[2px] transition-opacity">
            <div class="inline-flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-raised px-4 py-2.5 text-xs font-bold text-content-main shadow-xl">
                <x-ui.icon name="loader" class="size-4 animate-spin text-primary"/>
                <span>در حال بارگذاری کاربران...</span>
            </div>
        </div>

        @if ($users->isEmpty())
            <x-ui.card :padding="false">
                <x-ui.empty-state
                    icon="users"
                    title="کاربری با این مشخصات یافت نشد"
                    description="هیچ کاربری متناسب با عبارت جستجو یا فیلتر انتخابی شما پیدا نشد."
                />
            </x-ui.card>
        @else
            <div class="overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xs">
                <div class="divide-y divide-line-border/60 md:hidden">
                    @foreach ($users as $u)
                        @php
                            $avatarUrl = $u->avatarUrl();
                            $activeSub = $u->activeSubscription;
                            $planName = $activeSub?->plan?->name ?? 'رایگان';
                            $planSlug = $activeSub?->plan?->slug ?? 'free';
                            $regDate = $u->created_at?->copy()->setTimezone('Asia/Tehran');
                            $persianReg = $regDate ? DateTimeHelper::toPersianDate($regDate, 'yyyy/MM/dd HH:mm') : '—';
                        @endphp
                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    @if ($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="{{ $u->name }}" class="size-9 shrink-0 rounded-xl object-cover ring-1 ring-line-border">
                                    @else
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/15 font-mono text-xs font-bold text-primary">
                                            {{ mb_substr($u->name, 0, 1) }}
                                        </span>
                                    @endif

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span dir="auto" class="truncate text-xs font-extrabold text-content-main">{{ $u->name }}</span>
                                            <span dir="ltr" class="inline-block font-mono text-[10px] text-content-muted">#{{ $u->id }}</span>
                                        </div>
                                        <div class="mt-0.5">
                                            <span dir="ltr" class="inline-block truncate font-mono text-[11px] text-content-muted select-all">{{ $u->email }}</span>
                                        </div>
                                    </div>
                                </div>

                                @if ($planSlug === 'business')
                                    <x-ui.badge variant="warning" dot class="shrink-0 whitespace-nowrap">پلن {{ $planName }}</x-ui.badge>
                                @elseif ($planSlug === 'pro')
                                    <x-ui.badge variant="danger" dot class="shrink-0 whitespace-nowrap">پلن {{ $planName }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" class="shrink-0 whitespace-nowrap">رایگان</x-ui.badge>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2 rounded-xl border border-line-border/60 bg-surface-base/60 p-2.5 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-content-muted">ورکفلوها:</span>
                                    <span dir="ltr" class="inline-block font-mono font-bold">
                                        <span class="text-emerald-500">{{ $u->active_workflows_count }}</span>
                                        <span class="text-content-muted">/</span>
                                        <span class="text-content-main">{{ $u->workflows_count }}</span>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between border-r border-line-border/60 pr-2">
                                    <span class="text-content-muted">اجراهای دوره:</span>
                                    <span class="font-mono font-bold text-content-main">{{ number_format((int) $u->runs_used) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-content-muted">
                                @if ($u->google_id)
                                    <span class="inline-flex items-center gap-1 font-semibold text-content-main">
                                        <x-ui.icon name="brand:google" class="size-3.5"/>
                                        <span>متصل به گوگل</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1">
                                        <x-ui.icon name="key" class="size-3.5"/>
                                        <span>ثبت‌نام با ایمیل</span>
                                    </span>
                                @endif

                                <span dir="ltr" class="inline-block font-mono">{{ $persianReg }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-start text-xs">
                        <thead>
                        <tr class="border-b border-line-border bg-surface-raised/60 text-content-muted">
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">کاربر</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">پلن فعلی</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">ورکفلوها (فعال / کل)</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">اجراهای این دوره</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">نوع حساب</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">تاریخ ثبت‌نام</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-line-border/60">
                        @foreach ($users as $u)
                            @php
                                $avatarUrl = $u->avatarUrl();
                                $activeSub = $u->activeSubscription;
                                $planName = $activeSub?->plan?->name ?? 'رایگان';
                                $planSlug = $activeSub?->plan?->slug ?? 'free';
                                $regDate = $u->created_at?->copy()->setTimezone('Asia/Tehran');
                                $persianReg = $regDate ? DateTimeHelper::toPersianDate($regDate, 'yyyy/MM/dd HH:mm') : '—';
                            @endphp
                            <tr class="transition hover:bg-surface-raised/40">
                                <td class="px-5 py-3.5 text-start">
                                    <div class="flex items-center gap-2.5">
                                        @if ($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $u->name }}" class="size-9 shrink-0 rounded-xl object-cover ring-1 ring-line-border">
                                        @else
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/15 font-mono text-xs font-bold text-primary">
                                                {{ mb_substr($u->name, 0, 1) }}
                                            </span>
                                        @endif

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span dir="auto" class="truncate font-bold text-content-main">{{ $u->name }}</span>
                                                <span dir="ltr" class="inline-block font-mono text-[10px] text-content-muted">#{{ $u->id }}</span>
                                            </div>
                                            <div class="mt-0.5">
                                                <span dir="ltr" class="inline-block truncate font-mono text-[11px] text-content-muted select-all">{{ $u->email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    @if ($planSlug === 'business')
                                        <x-ui.badge variant="warning" dot class="whitespace-nowrap">پلن {{ $planName }}</x-ui.badge>
                                    @elseif ($planSlug === 'pro')
                                        <x-ui.badge variant="danger" dot class="whitespace-nowrap">پلن {{ $planName }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral" class="whitespace-nowrap">رایگان</x-ui.badge>
                                    @endif
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    <span dir="ltr" class="inline-block font-mono font-bold">
                                        <span class="text-emerald-500">{{ $u->active_workflows_count }}</span>
                                        <span class="text-content-muted">/</span>
                                        <span class="text-content-main">{{ $u->workflows_count }}</span>
                                    </span>
                                    <span class="ms-1 text-[11px] text-content-muted">ورکفلو</span>
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    <span class="font-mono font-bold tabular-nums text-content-main">{{ number_format((int) $u->runs_used) }}</span>
                                    <span class="text-[11px] text-content-muted">اجرا</span>
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    @if ($u->google_id)
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-line-border bg-surface-base px-2 py-1 text-[11px] font-semibold text-content-main">
                                            <x-ui.icon name="brand:google" class="size-3.5"/>
                                            <span>گوگل</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-line-border bg-surface-base px-2 py-1 text-[11px] font-medium text-content-muted">
                                            <x-ui.icon name="key" class="size-3.5"/>
                                            <span>ایمیل</span>
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    <div>
                                        <span dir="ltr" class="inline-block font-mono text-xs text-content-main">{{ $persianReg }}</span>
                                    </div>
                                    <div class="mt-0.5 text-[10px] text-content-muted">{{ $u->created_at?->diffForHumans() }}</div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <x-ui.pagination :paginator="$users" />
        @endif
    </div>
</div>
