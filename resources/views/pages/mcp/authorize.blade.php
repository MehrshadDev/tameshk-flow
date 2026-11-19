<x-layouts.auth :title="'اتصال هوشمند به ' . config('app.name')">
    @php
        $avatarUrl = $user->avatarUrl();
        $scopeLabels = [
            'mcp:read' => [
                'title' => 'مشاهده ورکفلوها، نودها و تاریخچه اجراها',
                'desc' => 'بررسی لیست ورکفلوها، شناخت تمام نودهای موجود و مشاهده نام اعتبارنامه‌های متصل (بدون دسترسی به رمزها و کلیدهای خام)',
                'icon' => 'eye',
            ],
            'mcp:write' => [
                'title' => 'ساخت، ویرایش، چیدمان و اجرای ورکفلوها',
                'desc' => 'طراحی خودکار ورکفلو، چیدمان مرتب نودها، اجرای تستی و مدیریت وضعیت انتشار در حساب شما',
                'icon' => 'sparkles',
            ],
        ];
    @endphp

    <div class="space-y-5">
        <div class="text-center">
            <div class="mx-auto mb-3 flex size-13 items-center justify-center rounded-2xl border border-primary/25 bg-primary/10 text-primary shadow-sm">
                <x-ui.icon name="brand:ai-agent" class="size-7"/>
            </div>

            <h1 class="text-lg font-extrabold text-content-main">درخواست اتصال به تمشک فلو</h1>
            <p class="mt-1.5 text-xs leading-6 text-content-muted">
                اپلیکیشن
                <strong dir="ltr" class="inline-block rounded-md border border-line-border bg-surface-card px-1.5 py-0.5 font-mono text-[11px] font-bold text-content-main">{{ $client->name }}</strong>
                درخواست دسترسی به حساب شما از طریق پروتکل MCP را دارد.
            </p>
        </div>

        <div class="flex items-center justify-between gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-2xs">
            <div class="flex min-w-0 items-center gap-2.5">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="size-9 shrink-0 rounded-xl object-cover ring-1 ring-line-border">
                @else
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/15 font-mono text-xs font-bold text-primary">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                @endif

                <div class="min-w-0 text-start">
                    <p dir="auto" class="truncate text-xs font-bold text-content-main">{{ $user->name }}</p>
                    <p dir="ltr" class="truncate text-left font-mono text-[11px] text-content-muted">{{ $user->email }}</p>
                </div>
            </div>

            @if ($redirectHost !== '')
                <span dir="ltr" class="shrink-0 rounded-lg border border-line-border bg-surface-base px-2 py-1 font-mono text-[10px] font-semibold text-content-muted">
                    {{ $redirectHost }}
                </span>
            @endif
        </div>

        <div class="space-y-2.5 rounded-2xl border border-line-border bg-surface-card/70 p-4">
            <p class="text-xs font-extrabold text-content-main">مجوزهای درخواستی:</p>

            <div class="space-y-2.5">
                @foreach ($scopes as $scope)
                    @if (isset($scopeLabels[$scope]))
                        <div class="flex items-start gap-2.5">
                            <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-500">
                                <x-ui.icon :name="$scopeLabels[$scope]['icon']" class="size-3.5"/>
                            </span>
                            <div class="min-w-0 flex-1 text-start">
                                <p class="text-xs font-bold text-content-main">{{ $scopeLabels[$scope]['title'] }}</p>
                                <p class="mt-0.5 text-[11px] leading-5 text-content-muted">{{ $scopeLabels[$scope]['desc'] }}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-emerald-500/25 bg-emerald-500/8 p-3 text-start text-[11px] leading-5 text-emerald-600 dark:text-emerald-400">
            <x-ui.icon name="lock" class="mt-0.5 size-4 shrink-0 text-emerald-500"/>
            <span>کلیدهای API، توکن‌های ربات تلگرام و رمزهای اعتبارنامه‌های شما به‌صورت رمزنگاری‌شده باقی می‌مانند و هرگز برای هوش مصنوعی ارسال نمی‌شوند.</span>
        </div>

        <form method="POST" action="{{ route('mcp.oauth.approve') }}" class="space-y-2.5 pt-1">
            @csrf
            <input type="hidden" name="payload" value="{{ $payload }}">

            <div class="grid grid-cols-2 gap-2.5">
                <button type="submit"
                        name="decision"
                        value="deny"
                        class="flex h-10.5 items-center justify-center gap-1.5 rounded-xl border border-line-border bg-surface-card text-xs font-bold text-content-main transition hover:bg-line-border/50 cursor-pointer">
                    <x-ui.icon name="x" class="size-4"/>
                    <span>انصراف</span>
                </button>

                <button type="submit"
                        name="decision"
                        value="approve"
                        class="flex h-10.5 items-center justify-center gap-1.5 rounded-xl bg-primary text-xs font-bold text-white shadow-md shadow-primary/25 transition hover:bg-primary/90 cursor-pointer">
                    <x-ui.icon name="check" class="size-4"/>
                    <span>تایید و اتصال</span>
                </button>
            </div>
        </form>
    </div>
</x-layouts.auth>
