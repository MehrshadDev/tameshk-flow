@php
    use Morilog\Jalali\CalendarUtils;
    use Morilog\Jalali\Jalalian;

    $saasEnabled = (bool) config('saas.enabled', false);
    $isLocal = app()->isLocal();

    $enamadId = trim((string) config('saas.enamad.id', ''));
    $enamadCode = trim((string) config('saas.enamad.code', ''));
    $hasEnamadCredentials = $enamadId !== '' && $enamadCode !== '';

    $configuredLocalLogo = ltrim((string) config('saas.enamad.local_logo', 'images/enamad.png'), '/');

    if (! is_file(public_path($configuredLocalLogo))) {
        $foundLogos = glob(public_path('{images/,images/*/,}*enamad*.{png,svg,webp,jpg,jpeg}'), GLOB_BRACE) ?: [];
        if (! empty($foundLogos[0])) {
            $configuredLocalLogo = ltrim(str_replace('\\', '/', substr($foundLogos[0], strlen(public_path()))), '/');
        }
    }

    $localEnamadLogo = asset($configuredLocalLogo);

    $enamadHref = $hasEnamadCredentials
        ? "https://trustseal.enamad.ir/?id=$enamadId&Code=$enamadCode"
        : 'https://trustseal.enamad.ir';

    $enamadRemoteSrc = $hasEnamadCredentials
        ? "https://trustseal.enamad.ir/logo.aspx?id=$enamadId&Code=$enamadCode"
        : $localEnamadLogo;

    $useRemoteEnamad = ! $isLocal && $hasEnamadCredentials;

    $zibalTrustDomain = trim((string) config('saas.zibal.trust_domain', 'tameshkflow.ir'));
    $zibalTrustHref = 'https://gateway.zibal.ir/trustMe/' . ltrim($zibalTrustDomain, '/');
    $zibalLightSrc = 'https://zibal.ir/trust/assets/1.png';
    $zibalDarkSrc = 'https://zibal.ir/trust/assets/2.png';
    $localZibalLogo = asset('icons/brands/zibal.svg');

    $links = array_values(array_filter([
        ['label' => 'قابلیت‌ها', 'href' => route('home') . '#features', 'external' => false],
        $saasEnabled ? ['label' => 'تعرفه‌ها', 'href' => route('home') . '#pricing', 'external' => false] : null,
        ['label' => 'مستندات', 'href' => route('docs.index'), 'external' => false],
        ['label' => 'قوانین و مقررات', 'href' => route('terms'), 'external' => false],
        ['label' => 'حریم خصوصی', 'href' => route('privacy'), 'external' => false],
        ['label' => 'گیت‌هاب', 'href' => 'https://github.com/MehrshadDev/tameshk-flow', 'external' => true],
    ]));

    $sealImgClass = 'size-full cursor-pointer object-contain opacity-90 transition duration-300 group-hover:scale-105 group-hover:opacity-100 dark:opacity-75 dark:brightness-0 dark:invert dark:group-hover:opacity-100';
    $sealBoxClass = 'group relative flex size-20 sm:size-24 shrink-0 items-center justify-center rounded-2xl border border-line-border/90 bg-surface-base/80 p-1.5 shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md';
    $linkClass = 'rounded-xl px-2.5 py-1.5 text-xs font-medium text-content-muted transition-colors hover:bg-surface-base hover:text-primary sm:px-3 sm:text-sm';
    $currentYear = CalendarUtils::convertNumbers(
        Jalalian::now()->format('Y')
    );
@endphp

<footer class="w-full bg-surface-base px-2 pb-3 pt-2 font-vazirmatn-rd sm:px-3 sm:pb-4">
    <div class="mx-auto max-w-7xl">
        <div
            class="relative overflow-hidden rounded-3xl border border-line-border bg-surface-raised/90 p-5 shadow-lg shadow-black/5 backdrop-blur-xl sm:p-8 lg:px-10 lg:py-9 dark:bg-surface-card/85 dark:shadow-black/40">
            <div
                class="pointer-events-none absolute inset-x-12 top-0 h-px bg-linear-to-r from-transparent via-primary/55 to-transparent"></div>
            <div
                class="pointer-events-none absolute -top-24 -right-24 size-64 rounded-full bg-primary/8 blur-3xl"></div>
            <div
                class="pointer-events-none absolute -bottom-24 -left-24 size-64 rounded-full bg-rose-500/6 blur-3xl"></div>

            @if ($saasEnabled)
                <div class="relative flex flex-col items-center justify-between gap-6 md:flex-row md:items-center md:gap-8">
                    <div class="flex w-full flex-col items-center text-center md:w-auto md:items-start md:text-start">
                        <x-layouts.logo logoClasses="h-8 sm:h-9 w-auto"/>

                        <p class="mt-2.5 max-w-md text-xs leading-6 text-content-muted sm:mt-3 sm:text-sm">
                            پلتفرم بصری اتوماسیون گردش‌کار و ساخت ایجنت‌های هوش مصنوعی، بدون نیاز به کدنویسی.
                        </p>

                        <nav aria-label="پیوندهای فوتر"
                             class="mt-4 flex flex-wrap items-center justify-center gap-1 sm:mt-5 md:-mr-3 md:justify-start">
                            @foreach ($links as $link)
                                <a href="{{ $link['href'] }}"
                                   @if ($link['external']) target="_blank" rel="noopener noreferrer" @endif
                                   class="{{ $linkClass }}">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    <div class="h-px w-full bg-linear-to-r from-transparent via-line-border to-transparent md:hidden"></div>

                    <div class="flex shrink-0 items-center justify-center gap-3 sm:gap-4">
                        <div
                            class="hidden h-20 w-px bg-linear-to-b from-transparent via-line-border to-transparent md:ml-2 md:block"></div>

                        <a href="{{ $zibalTrustHref }}"
                           target="_blank"
                           rel="noopener"
                           aria-label="پرداخت آنلاین زیبال"
                           x-data="{ lightFailed: false, darkFailed: false }"
                           class="group relative flex h-20 sm:h-24 shrink-0 items-center justify-center transition-transform duration-300 hover:-translate-y-0.5">
                            <img x-show="!lightFailed"
                                 x-on:error="lightFailed = true"
                                 src="{{ $zibalLightSrc }}"
                                 alt="پرداخت آنلاین زیبال"
                                 loading="lazy"
                                 decoding="async"
                                 fetchpriority="low"
                                 class="block dark:hidden h-full w-auto cursor-pointer object-contain transition duration-300 group-hover:scale-105">

                            <img x-show="!darkFailed"
                                 x-on:error="darkFailed = true"
                                 src="{{ $zibalDarkSrc }}"
                                 alt="پرداخت آنلاین زیبال"
                                 loading="lazy"
                                 decoding="async"
                                 fetchpriority="low"
                                 class="hidden dark:block h-full w-auto cursor-pointer object-contain transition duration-300 group-hover:scale-105">

                            <div x-show="lightFailed"
                                 x-cloak
                                 class="flex dark:hidden size-20 sm:size-24 items-center justify-center rounded-2xl border border-line-border/90 bg-surface-base/80 p-3 shadow-xs">
                                <img src="{{ $localZibalLogo }}"
                                     alt="پرداخت آنلاین زیبال"
                                     loading="lazy"
                                     decoding="async"
                                     class="size-10 object-contain">
                            </div>

                            <div x-show="darkFailed"
                                 x-cloak
                                 class="hidden dark:flex size-20 sm:size-24 items-center justify-center rounded-2xl border border-line-border/90 bg-surface-base/80 p-3 shadow-xs">
                                <img src="{{ $localZibalLogo }}"
                                     alt="پرداخت آنلاین زیبال"
                                     loading="lazy"
                                     decoding="async"
                                     class="size-10 object-contain">
                            </div>
                        </a>

                        @if ($useRemoteEnamad)
                            <div x-data="{
                                     loaded: false,
                                     failed: false,
                                     src: '',
                                     mountSeal() {
                                         const remote = this.$el.dataset.remoteSrc;
                                         const start = () => {
                                             const io = new IntersectionObserver((entries) => {
                                                 if (entries[0]?.isIntersecting) {
                                                     this.src = remote;
                                                     io.disconnect();
                                                 }
                                             }, { rootMargin: '250px' });
                                             io.observe(this.$el);
                                         };

                                         if (document.readyState === 'complete') {
                                             start();
                                         } else {
                                             window.addEventListener('load', () => setTimeout(start, 150), { once: true });
                                         }
                                     }
                                 }"
                                 x-init="mountSeal()"
                                 data-remote-src="{{ $enamadRemoteSrc }}"
                                 class="{{ $sealBoxClass }}">
                                <div
                                    class="flex size-full items-center justify-center overflow-hidden rounded-xl p-1.5">
                                    <div x-show="!loaded && !failed"
                                         class="flex size-full animate-pulse items-center justify-center rounded-lg bg-line-border/40 text-[10px] font-bold text-content-muted">
                                        <span>eNamad</span>
                                    </div>

                                    <a referrerpolicy="origin"
                                       target="_blank"
                                       rel="noopener"
                                       href="{{ $enamadHref }}"
                                       aria-label="نماد اعتماد الکترونیکی"
                                       class="flex size-full items-center justify-center">
                                        <img x-show="src && !failed"
                                             x-cloak
                                             referrerpolicy="origin"
                                             x-bind:src="src"
                                             x-on:load="loaded = true"
                                             x-on:error="failed = true"
                                             alt="نماد اعتماد الکترونیکی"
                                             loading="lazy"
                                             decoding="async"
                                             fetchpriority="low"
                                             code="{{ $enamadCode }}"
                                             class="{{ $sealImgClass }}">

                                        <img x-show="failed"
                                             x-cloak
                                             src="{{ $localEnamadLogo }}"
                                             alt="نماد اعتماد الکترونیکی"
                                             loading="lazy"
                                             decoding="async"
                                             class="{{ $sealImgClass }}">
                                    </a>

                                    <noscript>
                                        <a referrerpolicy="origin" target="_blank" href="{{ $enamadHref }}">
                                            <img referrerpolicy="origin" src="{{ $enamadRemoteSrc }}"
                                                 alt="نماد اعتماد الکترونیکی" style="cursor:pointer"
                                                 code="{{ $enamadCode }}">
                                        </a>
                                    </noscript>
                                </div>
                            </div>
                        @else
                            <a href="{{ $isLocal ? '#enamad-local-preview' : $enamadHref }}"
                               @if (! $isLocal) target="_blank" rel="noopener" referrerpolicy="origin" @endif
                               aria-label="نماد اعتماد الکترونیکی"
                               class="{{ $sealBoxClass }}">
                                <div
                                    class="flex size-full items-center justify-center overflow-hidden rounded-xl p-1.5">
                                    <img src="{{ $localEnamadLogo }}"
                                         alt="نماد اعتماد الکترونیکی"
                                         loading="lazy"
                                         decoding="async"
                                         fetchpriority="low"
                                         class="{{ $sealImgClass }}">
                                </div>
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="relative flex flex-col items-center justify-between gap-6 md:flex-row">
                    <div class="flex flex-col items-center gap-2.5 text-center md:items-start md:text-start">
                        <x-layouts.logo logoClasses="h-8 sm:h-9 w-auto"/>

                        <p class="text-xs leading-6 text-content-muted sm:text-sm">
                            پلتفرم بصری اتوماسیون گردش‌کار و ساخت ایجنت‌های هوش مصنوعی
                        </p>
                    </div>

                    <nav aria-label="پیوندهای فوتر"
                         class="flex flex-wrap items-center justify-center gap-1 md:justify-end">
                        @foreach ($links as $link)
                            <a href="{{ $link['href'] }}"
                               @if ($link['external']) target="_blank" rel="noopener noreferrer" @endif
                               class="{{ $linkClass }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endif
        </div>

        <p class="mt-4 text-center text-xs text-content-muted">
            © {{ $currentYear }} تمشک فلو — تمامی حقوق محفوظ است.
        </p>
    </div>
</footer>
