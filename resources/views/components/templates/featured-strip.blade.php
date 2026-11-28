@props([
    'heading' => 'ورکفلوهای آماده برای شروع',
    'subheading' => 'بدون نیاز به طراحی از صفر؛ یکی از قالب‌های آماده را با یک کلیک وارد حساب خود کنید.',
    'slugs' => [],
])

@php
    use App\Templates\TemplateRepository;

    $templates = $slugs !== []
        ? array_values(array_filter(array_map(fn (string $s) => TemplateRepository::find($s), $slugs)))
        : array_slice(TemplateRepository::all(), 0, 3);
@endphp

@if ($templates !== [])
    <section class="w-full border-t border-line-border bg-surface-base py-16 font-vazirmatn-rd sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-balance text-xl font-extrabold tracking-tight text-content-main sm:text-3xl">
                        {{ $heading }}
                    </h2>
                    <p class="mt-2 text-xs leading-6 text-content-muted sm:text-sm">
                        {{ $subheading }}
                    </p>
                </div>

                <a href="{{ route('templates.index') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 self-start text-xs font-extrabold text-primary hover:underline sm:self-auto sm:text-sm">
                    <span>مشاهده همه قالب‌ها</span>
                    <x-ui.icon name="chevron-left" class="size-4"/>
                </a>
            </div>

            <div class="mt-7 grid grid-cols-1 gap-5 md:grid-cols-3">
                @foreach ($templates as $template)
                    <x-templates.card :template="$template"/>
                @endforeach
            </div>
        </div>
    </section>
@endif
