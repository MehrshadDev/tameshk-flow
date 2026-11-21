@props([
    'template',
])

<a href="{{ route('templates.show', $template->slug) }}"
    {{ $attributes->class(['group flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-5 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-lg']) }}>
    <div>
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center -space-x-1.5 space-x-reverse">
                @foreach ($template->requiredApps as $app)
                    <span title="{{ $app['name'] }}"
                          class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-base text-content-main shadow-2xs transition-transform group-hover:scale-105">
                        <x-ui.icon :name="$app['icon']" class="size-4.5"/>
                    </span>
                @endforeach
            </div>

            <span class="inline-flex items-center gap-1 rounded-lg border border-line-border/80 bg-surface-base px-2.5 py-1 text-[11px] font-bold text-content-muted">
                <x-ui.icon :name="$template->category->icon()" class="size-3.5 text-primary"/>
                <span>{{ $template->category->label() }}</span>
            </span>
        </div>

        <h3 class="mt-4 text-base font-extrabold leading-7 text-content-main transition-colors group-hover:text-primary">
            {{ $template->title }}
        </h3>

        <p class="mt-2 line-clamp-2 text-xs leading-6 text-content-muted">
            {{ $template->subtitle }}
        </p>
    </div>

    <div class="mt-5 flex items-center justify-between border-t border-line-border/60 pt-3.5 text-[11px]">
        <div class="flex items-center gap-3 text-content-muted">
            <span class="inline-flex items-center gap-1">
                <x-ui.icon name="clock" class="size-3.5 text-primary"/>
                <span>{{ $template->setupMinutes }} دقیقه</span>
            </span>
            <span class="inline-flex items-center gap-1">
                <x-ui.icon name="hierarchy" class="size-3.5 text-emerald-500"/>
                <span>{{ $template->nodeCount() }} نود</span>
            </span>
        </div>

        <span class="inline-flex items-center gap-1 font-extrabold text-primary">
            <span>مشاهده</span>
            <x-ui.icon name="chevron-left" class="size-3.5 transition-transform group-hover:-translate-x-0.5"/>
        </span>
    </div>
</a>
