@props([
    'active' => 'index',
])

<header class="sticky top-0 z-40 border-b border-line-border bg-surface-raised/90 font-vazirmatn-rd backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
        <div class="flex items-center gap-3">
            <x-layouts.logo :href="route('home')" logoClasses="h-8.5 w-auto sm:h-9"/>

            <span class="hidden h-5 w-px bg-line-border sm:block"></span>

            <nav aria-label="مسیرهای اصلی" class="hidden items-center gap-1 sm:flex">
                <a href="{{ route('templates.index') }}"
                    @class([
                        'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs transition',
                        'bg-primary/10 font-extrabold text-primary' => $active === 'index' || $active === 'show',
                        'font-semibold text-content-muted hover:bg-surface-card hover:text-content-main' => $active !== 'index' && $active !== 'show',
                    ])>
                    <x-ui.icon name="sparkles" class="size-3.5"/>
                    <span>ورکفلوهای آماده</span>
                </a>

                <a href="{{ route('docs.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold text-content-muted transition hover:bg-surface-card hover:text-content-main">
                    <x-ui.icon name="file-text" class="size-3.5"/>
                    <span>مستندات</span>
                </a>
            </nav>
        </div>

        <div class="flex items-center gap-2 sm:gap-2.5">
            <button type="button"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                    aria-label="تغییر تم"
                    class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted transition hover:border-content-muted/40 hover:text-content-main cursor-pointer">
                <x-ui.icon name="moon" x-show="!dark" class="size-4.5"/>
                <x-ui.icon name="sun" x-show="dark" x-cloak class="size-4.5"/>
            </button>

            @auth
                <x-ui.button :href="route('app.workflows.index')" size="md" class="text-xs sm:text-sm">
                    <span>پنل کاربری</span>
                    <x-ui.icon name="chevron-left" class="size-4"/>
                </x-ui.button>
            @else
                <x-ui.button :href="route('login')" variant="secondary" size="md" class="hidden sm:inline-flex">
                    ورود
                </x-ui.button>
                <x-ui.button :href="route('register')" size="md" class="text-xs shadow-sm shadow-primary/25 sm:text-sm">
                    شروع رایگان
                </x-ui.button>
            @endauth
        </div>
    </div>
</header>
