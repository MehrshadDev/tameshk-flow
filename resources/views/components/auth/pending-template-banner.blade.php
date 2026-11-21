@php
    use App\Templates\PendingTemplate;
    use App\Templates\TemplateRepository;

    $pendingSlug = app(PendingTemplate::class)->currentSlug();
    $pendingTemplate = $pendingSlug ? TemplateRepository::find($pendingSlug) : null;
@endphp

@if ($pendingTemplate)
    <div class="mb-5 flex items-center gap-3 rounded-2xl border border-primary/25 bg-primary/8 p-3.5 text-start">
        <div class="flex shrink-0 items-center -space-x-1.5 space-x-reverse">
            @foreach ($pendingTemplate->requiredApps as $app)
                <span class="flex size-8 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-main shadow-2xs">
                    <x-ui.icon :name="$app['icon']" class="size-4"/>
                </span>
            @endforeach
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-xs font-extrabold text-content-main">
                {{ $pendingTemplate->title }}
            </p>
            <p class="mt-0.5 text-[11px] leading-5 text-content-muted">
                بلافاصله پس از ورود، این ورکفلو خودکار در ویرایشگر شما ساخته می‌شود.
            </p>
        </div>
    </div>
@endif
