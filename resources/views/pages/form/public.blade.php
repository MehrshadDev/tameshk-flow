@php
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
@endphp
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <x-layouts.head :title="$title . ' | ' . $siteName" :vite="['resources/css/app.css']" :livewire="false"/>
    <style>
        .public-form-dots {
            background-image: radial-gradient(color-mix(in oklab, var(--color-content-muted) 22%, transparent) 1.25px, transparent 1.25px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(ellipse 85% 75% at 50% 35%, #000 25%, transparent 100%);
            mask-image: radial-gradient(ellipse 85% 75% at 50% 35%, #000 25%, transparent 100%);
        }
    </style>
</head>
<body class="relative flex min-h-dvh flex-col justify-between overflow-x-hidden bg-surface-base font-vazirmatn-rd text-content-main antialiased selection:bg-primary/20 selection:text-primary">

<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
    <div class="public-form-dots absolute inset-0 opacity-75"></div>
    <div class="absolute -top-32 left-1/2 size-128 -translate-x-1/2 rounded-full bg-primary/10 blur-3xl"></div>
    <div class="absolute -bottom-32 right-1/4 size-96 rounded-full bg-emerald-500/8 blur-3xl"></div>
</div>

<header class="mx-auto flex w-full max-w-xl items-center justify-end px-4 pt-5 sm:px-6 sm:pt-8">
    <button type="button"
            id="theme-toggle"
            aria-label="تغییر تم"
            class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-card/90 text-content-muted shadow-2xs backdrop-blur-md transition hover:border-content-muted/40 hover:text-content-main cursor-pointer">
        <span class="flex items-center justify-center dark:hidden">
            <x-ui.icon name="moon" class="size-4.5"/>
        </span>
        <span class="hidden items-center justify-center dark:flex">
            <x-ui.icon name="sun" class="size-4.5"/>
        </span>
    </button>
</header>

<main class="mx-auto flex w-full max-w-xl flex-1 flex-col justify-center px-4 py-6 sm:px-6 sm:py-10">
    <div class="relative overflow-hidden rounded-3xl border border-line-border bg-surface-raised/95 p-6 shadow-2xl shadow-black/8 backdrop-blur-xl sm:p-9 dark:bg-surface-card/95 dark:shadow-black/50">
        <div class="pointer-events-none absolute inset-x-12 top-0 h-px bg-linear-to-r from-transparent via-primary/65 to-transparent"></div>

        <div id="form-container">
            <div class="mb-7 border-b border-line-border/70 pb-6">
                <div class="mb-4 flex size-12 items-center justify-center rounded-2xl border border-primary/25 bg-primary/10 text-primary shadow-inner">
                    <x-ui.icon name="clipboard-list" class="size-6"/>
                </div>

                <h1 dir="auto" class="text-start text-xl font-extrabold tracking-tight text-content-main sm:text-2xl">
                    {{ $title }}
                </h1>

                @if ($description !== '')
                    <p dir="auto" class="mt-2.5 text-start text-xs leading-7 text-content-muted sm:text-sm sm:leading-7 whitespace-pre-line">{{ $description }}</p>
                @endif
            </div>

            <div id="global-error"
                 class="mb-5 hidden items-start gap-2.5 rounded-2xl border border-primary/30 bg-primary/10 p-3.5 text-xs font-semibold leading-6 text-primary">
                <x-ui.icon name="alert-triangle" class="mt-0.5 size-4.5 shrink-0"/>
                <span id="global-error-text" class="flex-1"></span>
            </div>

            <form id="public-form" novalidate class="space-y-5">
                @foreach ($fields as $field)
                    @php
                        $fieldId = 'field_' . $field['name'];
                        $isLtrType = in_array($field['type'], ['email', 'tel', 'number'], true);
                    @endphp

                    <div class="space-y-1.5" data-field-wrapper="{{ $field['name'] }}">
                        <label for="{{ $fieldId }}" class="flex items-center gap-1 text-xs font-extrabold text-content-main sm:text-sm">
                            <span>{{ $field['label'] }}</span>
                            @if ($field['required'])
                                <span class="text-primary">*</span>
                            @endif
                        </label>

                        @if ($field['type'] === 'textarea')
                            <textarea id="{{ $fieldId }}"
                                      name="{{ $field['name'] }}"
                                      rows="4"
                                      maxlength="5000"
                                      dir="rtl"
                                      @if ($field['required']) required @endif
                                      placeholder="{{ $field['placeholder'] }}"
                                      class="custom-scrollbar w-full resize-y rounded-2xl border border-field-border bg-field px-4 py-3 text-right text-sm leading-6 text-content-main shadow-2xs transition placeholder:text-right placeholder:text-content-muted/55 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
                        @elseif ($field['type'] === 'select')
                            <div class="relative">
                                <select id="{{ $fieldId }}"
                                        name="{{ $field['name'] }}"
                                        @if ($field['required']) required @endif
                                        class="h-11.5 w-full appearance-none rounded-2xl border border-field-border bg-field pe-10 ps-4 text-sm font-medium text-content-main shadow-2xs transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                    <option value="">{{ $field['placeholder'] !== '' ? $field['placeholder'] : 'انتخاب کنید...' }}</option>
                                    @foreach ($field['options_list'] as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                                <span class="pointer-events-none absolute inset-e-3.5 top-1/2 -translate-y-1/2 text-content-muted">
                                    <x-ui.icon name="chevron-down" class="size-4"/>
                                </span>
                            </div>
                        @else
                            <input id="{{ $fieldId }}"
                                   name="{{ $field['name'] }}"
                                   type="{{ $field['type'] === 'number' ? 'text' : $field['type'] }}"
                                   @if ($field['type'] === 'number') inputmode="decimal" @endif
                                   @if ($field['type'] === 'tel') inputmode="tel" @endif
                                   @if ($field['type'] === 'email') inputmode="email" @endif
                                   dir="{{ $isLtrType ? 'ltr' : 'rtl' }}"
                                   maxlength="1000"
                                   @if ($field['required']) required @endif
                                   placeholder="{{ $field['placeholder'] }}"
                                   class="h-11.5 w-full rounded-2xl border border-field-border bg-field px-4 text-sm text-content-main shadow-2xs transition placeholder:text-content-muted/55 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 {{ $isLtrType ? 'text-left font-mono' : 'text-right placeholder:text-right' }}"/>
                        @endif

                        <p data-field-error="{{ $field['name'] }}" class="hidden text-xs font-medium text-primary"></p>
                    </div>
                @endforeach

                <div class="pt-2">
                    <button id="submit-btn"
                            type="submit"
                            class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-primary px-6 text-sm font-extrabold text-white shadow-lg shadow-primary/25 transition hover:bg-primary/90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer">
                        <span id="submit-label">{{ $submitLabel }}</span>
                        <span id="submit-icon" class="inline-flex">
                            <x-ui.icon name="send" class="size-4 -scale-x-100"/>
                        </span>
                        <span id="submit-spinner" class="hidden">
                            <x-ui.icon name="loader" class="size-4.5 animate-spin"/>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <div id="success-container" class="hidden py-8 text-center">
            <div class="mx-auto flex size-16 items-center justify-center rounded-3xl border border-emerald-500/30 bg-emerald-500/15 text-emerald-500 shadow-lg shadow-emerald-500/10">
                <x-ui.icon name="check" class="size-8"/>
            </div>

            <h2 class="mt-5 text-lg font-extrabold text-content-main sm:text-xl">
                با موفقیت ثبت شد!
            </h2>

            <p id="success-message"
               dir="auto"
               class="mx-auto mt-2.5 max-w-md text-xs leading-7 text-content-muted sm:text-sm sm:leading-7 whitespace-pre-line">{{ $responseMessage }}</p>
        </div>
    </div>
</main>

<footer class="mx-auto w-full max-w-xl px-4 pb-6 text-center sm:px-6">
    <a href="{{ route('home') }}"
       target="_blank"
       rel="noopener"
       class="inline-flex items-center gap-2 rounded-2xl border border-line-border/70 bg-surface-card/80 px-3.5 py-1.5 text-[11px] font-semibold text-content-muted shadow-2xs backdrop-blur-md transition hover:border-primary/40 hover:text-content-main">
        <img src="{{ asset('images/logo/favicon.png') }}" alt="{{ $siteName }}" class="size-4 rounded object-contain">
        <span>ساخته‌شده با <strong class="font-extrabold text-content-main">{{ $siteName }}</strong></span>
    </a>
</footer>

<script>
    (() => {
        const endpoint = @json($endpoint);
        const defaultSubmitLabel = @json($submitLabel);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const themeBtn = document.getElementById('theme-toggle');
        themeBtn?.addEventListener('click', () => {
            const isDark = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });

        const form = document.getElementById('public-form');
        const formContainer = document.getElementById('form-container');
        const successContainer = document.getElementById('success-container');
        const successMessage = document.getElementById('success-message');
        const submitBtn = document.getElementById('submit-btn');
        const submitLabel = document.getElementById('submit-label');
        const submitIcon = document.getElementById('submit-icon');
        const submitSpinner = document.getElementById('submit-spinner');
        const globalError = document.getElementById('global-error');
        const globalErrorText = document.getElementById('global-error-text');

        const clearErrors = () => {
            globalError.classList.add('hidden');
            globalError.classList.remove('flex');
            globalErrorText.textContent = '';

            document.querySelectorAll('[data-field-error]').forEach((el) => {
                el.textContent = '';
                el.classList.add('hidden');
            });

            document.querySelectorAll('[data-field-wrapper] input, [data-field-wrapper] textarea, [data-field-wrapper] select').forEach((el) => {
                el.classList.remove('border-primary');
            });
        };

        const setBusy = (busy) => {
            submitBtn.disabled = busy;
            submitLabel.textContent = busy ? 'در حال ارسال...' : defaultSubmitLabel;
            submitIcon.classList.toggle('hidden', busy);
            submitSpinner.classList.toggle('hidden', !busy);
        };

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors();

            const formData = new FormData(form);
            const fields = {};
            formData.forEach((value, key) => {
                fields[key] = value;
            });

            setBusy(true);

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ fields }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        Object.entries(data.errors).forEach(([fieldKey, message]) => {
                            const errEl = document.querySelector(`[data-field-error="${fieldKey}"]`);
                            const inputEl = document.querySelector(`[data-field-wrapper="${fieldKey}"] input, [data-field-wrapper="${fieldKey}"] textarea, [data-field-wrapper="${fieldKey}"] select`);
                            if (errEl) {
                                errEl.textContent = Array.isArray(message) ? message[0] : String(message);
                                errEl.classList.remove('hidden');
                            }
                            if (inputEl) {
                                inputEl.classList.add('border-primary');
                            }
                        });
                    } else {
                        globalErrorText.textContent = data.message || 'خطایی در ثبت فرم رخ داد. لطفاً دوباره تلاش کنید.';
                        globalError.classList.remove('hidden');
                        globalError.classList.add('flex');
                    }
                    return;
                }

                if (data.message) {
                    successMessage.textContent = data.message;
                }

                formContainer.classList.add('hidden');
                successContainer.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch {
                globalErrorText.textContent = 'خطا در برقراری ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.';
                globalError.classList.remove('hidden');
                globalError.classList.add('flex');
            } finally {
                setBusy(false);
            }
        });
    })();
</script>
</body>
</html>
