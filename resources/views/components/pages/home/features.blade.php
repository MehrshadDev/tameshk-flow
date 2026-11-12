@php
    $models = [
        ['name' => 'Gemini', 'icon' => 'brand:gemini'],
        ['name' => 'ChatGPT', 'icon' => 'brand:chatgpt'],
        ['name' => 'Claude', 'icon' => 'brand:claude'],
        ['name' => 'DeepSeek', 'icon' => 'brand:deepseek'],
        ['name' => 'Groq', 'icon' => 'brand:groq'],
    ];

    $vars = [
        ['key' => 'chat_id', 'val' => '984120531', 'expr' => '{{ input.chat_id }}'],
        ['key' => 'first_name', 'val' => '"مهرشاد"', 'expr' => '{{ input.first_name }}'],
        ['key' => 'phone', 'val' => '"09123456789"', 'expr' => '{{ input.phone }}'],
    ];
@endphp

<section id="features"
         x-data="{
             selectedModel: 0,
             models: @js($models),
             selectedVar: 0,
             vars: @js($vars),
             activeBranch: 'true',
             init() {
                 setInterval(() => {
                     this.activeBranch = this.activeBranch === 'true' ? 'false' : 'true';
                 }, 3200);
                 setInterval(() => {
                     this.selectedVar = (this.selectedVar + 1) % this.vars.length;
                 }, 2800);
             }
         }"
         class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-28">

    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-3xl font-extrabold leading-[1.35] tracking-tight text-content-main sm:text-6xl sm:leading-tight">
                <span class="block">اتوماسیون فارسی،</span>
                <span class="relative mt-2 inline-block">
                    <span class="bg-linear-to-l from-primary via-purple-500 to-amber-500 bg-clip-text py-1 text-transparent">بدون پیچیدگی</span>
                    <svg viewBox="0 0 300 14" preserveAspectRatio="none" class="absolute inset-x-0 -bottom-3 h-3 w-full text-primary" aria-hidden="true">
                        <path d="M3 9 C 45 2, 85 13, 130 7 S 215 3, 297 9" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                    </svg>
                </span>
            </h2>
            <p class="mx-auto mt-8 max-w-xl text-base leading-8 text-content-muted sm:text-lg">
                هر چیزی که برای ساخت ورک‌فلو لازم دارید، از همان اول آماده است.
            </p>
        </div>

        <div class="mt-14 grid grid-cols-1 gap-5 sm:mt-16 lg:grid-cols-12">

            <article class="flex flex-col rounded-3xl border border-line-border bg-surface-card p-3 lg:col-span-7">
                <div class="features-dot-grid relative flex min-h-80 flex-1 flex-col items-center justify-center gap-5 overflow-hidden rounded-2xl border border-line-border/60 bg-surface-base/80 p-4 sm:p-6">
                    <div class="pointer-events-none absolute left-1/2 top-0 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-purple-500/20 blur-3xl" aria-hidden="true"></div>

                    <svg viewBox="0 0 380 245" style="direction: ltr" class="relative block w-full max-w-sm overflow-visible" role="img" aria-label="AI Agent Node">
                        <path d="M 18 58 H 85" class="stroke-line-border" stroke-width="2"/>
                        <path d="M 295 58 H 362" class="stroke-line-border" stroke-width="2"/>

                        <path id="agent-model-line" d="M 130 100 C 130 132, 105 128, 105 156" class="fill-none stroke-purple-500/50" stroke-width="2" stroke-dasharray="4 4"/>
                        <path id="agent-mem-line" d="M 190 100 V 156" class="fill-none stroke-purple-500/50" stroke-width="2" stroke-dasharray="4 4"/>
                        <path id="agent-tool-line" d="M 250 100 C 250 132, 275 128, 275 156" class="fill-none stroke-purple-500/50" stroke-width="2" stroke-dasharray="4 4"/>

                        <circle r="3" class="fill-purple-400">
                            <animateMotion dur="2.2s" repeatCount="indefinite" path="M 105 156 C 105 128, 130 132, 130 100"/>
                        </circle>
                        <circle r="3" class="fill-purple-400">
                            <animateMotion dur="2.6s" begin="0.7s" repeatCount="indefinite" path="M 275 156 C 275 128, 250 132, 250 100"/>
                        </circle>

                        <rect x="85" y="16" width="210" height="84" rx="20" class="fill-surface-card stroke-purple-500" stroke-width="2"/>

                        <circle cx="85" cy="58" r="5" class="fill-content-muted stroke-surface-card" stroke-width="2"/>
                        <circle cx="295" cy="58" r="5" class="fill-purple-500 stroke-surface-card" stroke-width="2"/>

                        <circle cx="292" cy="19" r="9" class="fill-emerald-500 stroke-surface-card" stroke-width="2"/>
                        <path d="M 288.5 19 L 291 21.5 L 296 16.5" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

                        <rect x="125" y="95" width="10" height="10" rx="2" transform="rotate(45 130 100)" class="fill-purple-500 stroke-surface-card" stroke-width="2"/>
                        <rect x="185" y="95" width="10" height="10" rx="2" transform="rotate(45 190 100)" class="fill-purple-500 stroke-surface-card" stroke-width="2"/>
                        <rect x="245" y="95" width="10" height="10" rx="2" transform="rotate(45 250 100)" class="fill-purple-500 stroke-surface-card" stroke-width="2"/>

                        <foreignObject x="103" y="34" width="48" height="48">
                            <div class="flex size-12 items-center justify-center rounded-2xl bg-purple-500/15 text-purple-500">
                                <x-ui.icon name="brand:ai-agent" class="size-7"/>
                            </div>
                        </foreignObject>
                        <text x="163" y="52" text-anchor="start" class="fill-content-main text-[15px] font-extrabold">AI Agent</text>
                        <text x="163" y="75" text-anchor="start" class="fill-emerald-500 text-[12px] font-bold" x-text="models[selectedModel].name"></text>

                        <circle cx="105" cy="184" r="28" class="fill-surface-card stroke-purple-500/70" stroke-width="1.75"/>
                        <rect x="101" y="152" width="8" height="8" rx="1.5" transform="rotate(45 105 156)" class="fill-purple-500 stroke-surface-card" stroke-width="1.5"/>
                        <foreignObject x="89" y="168" width="32" height="32">
                            <div class="flex size-8 items-center justify-center">
                                @foreach ($models as $mIdx => $m)
                                    <span x-show="selectedModel === {{ $mIdx }}" @if ($mIdx > 0) x-cloak @endif class="flex">
                                        <x-ui.icon :name="$m['icon']" class="size-6"/>
                                    </span>
                                @endforeach
                            </div>
                        </foreignObject>
                        <text x="105" y="232" text-anchor="middle" class="fill-content-main text-[11px] font-bold" x-text="models[selectedModel].name"></text>

                        <circle cx="190" cy="184" r="28" class="fill-surface-card stroke-line-border" stroke-width="1.75"/>
                        <rect x="186" y="152" width="8" height="8" rx="1.5" transform="rotate(45 190 156)" class="fill-purple-500 stroke-surface-card" stroke-width="1.5"/>
                        <foreignObject x="174" y="168" width="32" height="32">
                            <div class="flex size-8 items-center justify-center text-cyan-500">
                                <x-ui.icon name="history" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="190" y="232" text-anchor="middle" class="fill-content-muted text-[11px] font-semibold">Memory</text>

                        <circle cx="275" cy="184" r="28" class="fill-surface-card stroke-line-border" stroke-width="1.75"/>
                        <rect x="271" y="152" width="8" height="8" rx="1.5" transform="rotate(45 275 156)" class="fill-purple-500 stroke-surface-card" stroke-width="1.5"/>
                        <foreignObject x="259" y="168" width="32" height="32">
                            <div class="flex size-8 items-center justify-center">
                                <x-ui.icon name="brand:google-sheets" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="275" y="232" text-anchor="middle" class="fill-content-muted text-[11px] font-semibold">Sheets Tool</text>
                    </svg>

                    <div dir="ltr" class="relative flex items-center gap-1.5 rounded-2xl border border-line-border bg-surface-card p-1.5 shadow-xs">
                        @foreach ($models as $mIdx => $m)
                            <button type="button"
                                    @click="selectedModel = {{ $mIdx }}"
                                    :aria-pressed="selectedModel === {{ $mIdx }}"
                                    aria-label="{{ $m['name'] }}"
                                    :class="selectedModel === {{ $mIdx }} ? 'bg-purple-500/15 ring-1 ring-purple-500/50' : 'hover:bg-surface-base'"
                                    class="flex size-10 cursor-pointer items-center justify-center rounded-xl transition">
                                <x-ui.icon :name="$m['icon']" class="size-5"/>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="px-4 pb-4 pt-6 sm:px-6">
                    <h3 class="text-xl font-extrabold text-content-main sm:text-2xl">ایجنتی که خودش تصمیم می‌گیرد</h3>
                    <p class="mt-2 max-w-md text-sm leading-7 text-content-muted sm:text-base sm:leading-8">
                        مدل، حافظه و ابزارها را وصل کنید؛ ایجنت در هر پیام خودش ابزار مناسب را انتخاب می‌کند.
                    </p>
                </div>
            </article>

            <article class="flex flex-col rounded-3xl border border-line-border bg-surface-card p-3 lg:col-span-5">
                <div class="features-dot-grid relative flex min-h-80 flex-1 flex-col items-center justify-center gap-5 overflow-hidden rounded-2xl border border-line-border/60 bg-surface-base/80 p-4 sm:p-6">
                    <div class="pointer-events-none absolute left-1/2 top-0 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/20 blur-3xl" aria-hidden="true"></div>

                    <svg viewBox="0 0 320 96" style="direction: ltr" class="relative block w-full max-w-xs overflow-visible" aria-hidden="true">
                        <path d="M 108 42 H 212" class="stroke-emerald-500/50" stroke-width="2.5" stroke-dasharray="5 5"/>
                        <circle r="4" class="fill-emerald-500">
                            <animateMotion dur="2s" repeatCount="indefinite" path="M 108 42 H 212"/>
                        </circle>

                        <rect x="44" y="10" width="64" height="64" rx="18" class="fill-surface-card stroke-line-border" stroke-width="2"/>
                        <circle cx="108" cy="42" r="5" class="fill-emerald-500 stroke-surface-card" stroke-width="2"/>
                        <circle cx="104" cy="14" r="8" class="fill-emerald-500 stroke-surface-card" stroke-width="2"/>
                        <path d="M 101 14 L 103 16 L 107.5 11.5" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <foreignObject x="56" y="22" width="40" height="40">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-sky-500/15 text-sky-500">
                                <x-ui.icon name="brand:telegram" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="76" y="90" text-anchor="middle" class="fill-content-muted text-[11px] font-bold">Telegram</text>

                        <rect x="212" y="10" width="64" height="64" rx="18" class="fill-surface-card stroke-primary" stroke-width="2"/>
                        <circle cx="212" cy="42" r="5" class="fill-primary stroke-surface-card" stroke-width="2"/>
                        <foreignObject x="224" y="22" width="40" height="40">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-primary/15 text-primary">
                                <x-ui.icon name="pencil" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="244" y="90" text-anchor="middle" class="fill-content-main text-[11px] font-bold">Edit Fields</text>
                    </svg>

                    <div dir="ltr" class="relative w-full max-w-xs space-y-3 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-sm">
                        <div class="flex items-center justify-between gap-1.5">
                            @foreach ($vars as $vIdx => $v)
                                <button type="button"
                                        @click="selectedVar = {{ $vIdx }}"
                                        :class="selectedVar === {{ $vIdx }}
                                            ? 'border-primary bg-primary/10 text-primary'
                                            : 'border-line-border bg-surface-base text-content-muted hover:text-content-main'"
                                        class="flex-1 cursor-pointer truncate rounded-xl border py-1.5 text-center font-mono text-xs font-bold transition">
                                    {{ $v['key'] }}
                                </button>
                            @endforeach
                        </div>

                        <div class="flex h-12 items-center justify-between rounded-xl border border-emerald-500/50 bg-emerald-500/10 px-3 font-mono text-xs">
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-primary px-1.5 py-0.5 text-[10px] font-bold text-white">fx</span>
                                <span class="font-bold text-emerald-500" x-text="vars[selectedVar].expr"></span>
                            </div>
                            <span class="font-bold text-content-main" x-text="vars[selectedVar].val"></span>
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-4 pt-6 sm:px-6">
                    <h3 class="text-xl font-extrabold text-content-main sm:text-2xl">داده را بکشید و رها کنید</h3>
                    <p class="mt-2 text-sm leading-7 text-content-muted sm:text-base sm:leading-8">
                        خروجی هر نود را روی فیلد بعدی بیندازید. درستی متغیر همان لحظه بررسی می‌شود.
                    </p>
                </div>
            </article>

            <article class="flex flex-col rounded-3xl border border-line-border bg-surface-card p-3 lg:col-span-5">
                <div class="features-dot-grid relative flex min-h-80 flex-1 flex-col items-center justify-center gap-4 overflow-hidden rounded-2xl border border-line-border/60 bg-surface-base/80 p-4 sm:p-6">
                    <div class="pointer-events-none absolute left-1/2 top-0 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true"></div>

                    <div :class="activeBranch === 'true'
                            ? 'border-emerald-500/35 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                            : 'border-rose-500/35 bg-rose-500/10 text-rose-500'"
                         class="relative inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-bold transition-colors duration-300">
                        <span class="size-2 rounded-full" :class="activeBranch === 'true' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        <span x-text="activeBranch === 'true' ? 'شرط برقرار است ← ثبت در گوگل شیت' : 'شرط برقرار نیست ← ارسال پیام در جیمیل'"></span>
                    </div>

                    <svg viewBox="0 0 360 200" style="direction: ltr" class="relative block w-full max-w-sm overflow-visible" role="img" aria-label="Branching Workflow">
                        <path d="M 80 100 H 140" class="stroke-emerald-500" stroke-width="2.5"/>

                        <path d="M 208 84 C 244 84, 244 48, 284 48"
                              :class="activeBranch === 'true' ? 'stroke-emerald-500' : 'stroke-line-border'"
                              class="fill-none transition-colors duration-300"
                              stroke-width="2.5"/>

                        <path d="M 208 116 C 244 116, 244 152, 284 152"
                              :class="activeBranch === 'false' ? 'stroke-rose-500' : 'stroke-line-border'"
                              class="fill-none transition-colors duration-300"
                              stroke-width="2.5"/>

                        <circle x-show="activeBranch === 'true'" r="4" class="fill-emerald-500">
                            <animateMotion dur="1.6s" repeatCount="indefinite" path="M 208 84 C 244 84, 244 48, 284 48"/>
                        </circle>

                        <circle x-show="activeBranch === 'false'" r="4" class="fill-rose-500">
                            <animateMotion dur="1.6s" repeatCount="indefinite" path="M 208 116 C 244 116, 244 152, 284 152"/>
                        </circle>

                        <rect x="228" y="48" width="32" height="18" rx="6" class="fill-surface-card stroke-emerald-500/40" stroke-width="1"/>
                        <text x="244" y="60" text-anchor="middle" class="fill-emerald-500 text-[10px] font-bold">بله</text>

                        <rect x="228" y="134" width="32" height="18" rx="6" class="fill-surface-card stroke-rose-500/40" stroke-width="1"/>
                        <text x="244" y="146" text-anchor="middle" class="fill-rose-500 text-[10px] font-bold">خیر</text>

                        <rect x="16" y="68" width="64" height="64" rx="18" class="fill-surface-card stroke-line-border" stroke-width="2"/>
                        <circle cx="80" cy="100" r="5" class="fill-emerald-500 stroke-surface-card" stroke-width="2"/>
                        <circle cx="76" cy="72" r="8" class="fill-emerald-500 stroke-surface-card" stroke-width="2"/>
                        <path d="M 73 72 L 75 74 L 79.5 69.5" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <foreignObject x="28" y="80" width="40" height="40">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-sky-500/15 text-sky-500">
                                <x-ui.icon name="brand:telegram" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="48" y="150" text-anchor="middle" class="fill-content-muted text-[11px] font-bold">Telegram</text>

                        <rect x="140" y="66" width="68" height="68" rx="18" class="fill-surface-card stroke-amber-500" stroke-width="2"/>
                        <circle cx="140" cy="100" r="5" class="fill-amber-500 stroke-surface-card" stroke-width="2"/>
                        <circle cx="208" cy="84" r="5" class="fill-emerald-500 stroke-surface-card" stroke-width="2"/>
                        <circle cx="208" cy="116" r="5" class="fill-rose-500 stroke-surface-card" stroke-width="2"/>
                        <circle cx="204" cy="70" r="8" class="fill-emerald-500 stroke-surface-card" stroke-width="2"/>
                        <path d="M 201 70 L 203 72 L 207.5 67.5" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <foreignObject x="154" y="80" width="40" height="40">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                                <x-ui.icon name="arrows-split-2" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="174" y="152" text-anchor="middle" class="fill-content-main text-[11px] font-bold">If Condition</text>

                        <rect x="284" y="18" width="60" height="60" rx="16"
                              :class="activeBranch === 'true' ? 'stroke-emerald-500' : 'stroke-line-border'"
                              class="fill-surface-card transition-colors duration-300"
                              stroke-width="2"/>
                        <circle cx="284" cy="48" r="5" class="fill-emerald-500 stroke-surface-card" stroke-width="2"/>
                        <foreignObject x="294" y="28" width="40" height="40">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/15">
                                <x-ui.icon name="brand:google-sheets" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="314" y="94" text-anchor="middle" class="fill-content-muted text-[10px] font-bold">Sheets</text>

                        <rect x="284" y="122" width="60" height="60" rx="16"
                              :class="activeBranch === 'false' ? 'stroke-rose-500' : 'stroke-line-border'"
                              class="fill-surface-card transition-colors duration-300"
                              stroke-width="2"/>
                        <circle cx="284" cy="152" r="5" class="fill-rose-500 stroke-surface-card" stroke-width="2"/>
                        <foreignObject x="294" y="132" width="40" height="40">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-rose-500/15">
                                <x-ui.icon name="brand:gmail" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="314" y="196" text-anchor="middle" class="fill-content-muted text-[10px] font-bold">Gmail</text>
                    </svg>
                </div>

                <div class="px-4 pb-4 pt-6 sm:px-6">
                    <h3 class="text-xl font-extrabold text-content-main sm:text-2xl">شرط بگذارید و مسیرها را جدا کنید</h3>
                    <p class="mt-2 text-sm leading-7 text-content-muted sm:text-base sm:leading-8">
                        با نودهای شرطی If و Switch، پیام‌ها و داده‌ها را بررسی کنید و هرکدام را خودکار به مسیر درست بفرستید.
                    </p>
                </div>
            </article>

            <article class="flex flex-col rounded-3xl border border-line-border bg-surface-card p-3 lg:col-span-7">
                <div x-data="{
                         date: '',
                         day: '',
                         time: '',
                         digits: '۰۱۲۳۴۵۶۷۸۹',
                         otp: Array.from({ length: 6 }, () => Math.floor(Math.random() * 10)),
                         regenerateOtp() {
                             this.otp = Array.from({ length: 6 }, () => Math.floor(Math.random() * 10));
                         },
                         fmt(options) {
                             return new Intl.DateTimeFormat('fa-IR-u-ca-persian', { timeZone: 'Asia/Tehran', ...options });
                         },
                         tick() {
                             const now = new Date();
                             const p = Object.fromEntries(this.fmt({ year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(now).map((x) => [x.type, x.value]));
                             this.date = p.year + '/' + p.month + '/' + p.day;
                             this.day = this.fmt({ weekday: 'long' }).format(now).replace(/^(یک|پنج)شنبه$/, '$1\u200cشنبه') + '، ' + this.fmt({ day: 'numeric', month: 'long' }).format(now);
                             this.time = this.fmt({ hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }).format(now);
                         },
                         init() {
                             this.tick();
                             setInterval(() => this.tick(), 1000);
                         }
                     }"
                     class="features-dot-grid relative flex min-h-80 flex-1 flex-col items-center justify-center gap-6 overflow-hidden rounded-2xl border border-line-border/60 bg-surface-base/80 p-4 sm:p-6">
                    <div class="pointer-events-none absolute left-1/2 top-0 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-500/20 blur-3xl" aria-hidden="true"></div>

                    <div class="relative flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                        <div class="flex items-center gap-2.5 rounded-2xl border border-pink-500/40 bg-surface-card px-3.5 py-2 shadow-sm">
                            <span class="flex size-8 items-center justify-center rounded-xl bg-pink-500/15 text-pink-500">
                                <x-ui.icon name="calendar" class="size-4"/>
                            </span>
                            <span class="text-xs font-extrabold text-content-main">نود تاریخ شمسی</span>
                            <svg viewBox="0 0 20 20" class="size-5 shrink-0" fill="none" aria-hidden="true">
                                <circle cx="10" cy="10" r="9" class="fill-emerald-500"/>
                                <path d="M 6.5 10.2 L 8.8 12.5 L 13.5 7.5" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>

                        <span class="hidden h-0.5 w-5 rounded-full bg-line-border sm:block"></span>

                        <div class="flex items-center gap-2.5 rounded-2xl border border-amber-500/40 bg-surface-card px-3.5 py-2 shadow-sm">
                            <span class="flex size-8 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                                <x-ui.icon name="dice-3" class="size-4"/>
                            </span>
                            <span class="text-xs font-extrabold text-content-main">نود کد تایید (OTP)</span>
                            <svg viewBox="0 0 20 20" class="size-5 shrink-0" fill="none" aria-hidden="true">
                                <circle cx="10" cy="10" r="9" class="fill-emerald-500"/>
                                <path d="M 6.5 10.2 L 8.8 12.5 L 13.5 7.5" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>

                    <div class="relative flex flex-col items-center">
                        <p dir="ltr" x-text="date" class="h-[1.2em] text-4xl font-extrabold tracking-tight text-content-main sm:text-6xl"></p>
                        <div class="mt-3 flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-content-muted sm:text-sm">
                            <span x-text="day"></span>
                            <span class="h-4 w-px bg-line-border"></span>
                            <span class="flex items-center gap-1.5 text-amber-500">
                                <x-ui.icon name="clock" class="size-4"/>
                                <span dir="ltr" x-text="time" class="min-w-16 text-start"></span>
                            </span>
                        </div>
                    </div>

                    <div class="relative flex flex-col items-center gap-2">
                        <div dir="ltr"
                             @click="regenerateOtp()"
                             title="کلیک برای تولید کد جدید"
                             class="flex cursor-pointer items-center gap-1.5 sm:gap-2">
                            <template x-for="(d, i) in otp" :key="i">
                                <span x-text="digits[d]" class="flex size-10 items-center justify-center rounded-xl border border-amber-500/40 bg-surface-card text-lg font-extrabold text-amber-500 shadow-sm transition hover:border-amber-500 sm:size-12 sm:text-xl"></span>
                            </template>
                        </div>
                        <button type="button"
                                @click="regenerateOtp()"
                                class="cursor-pointer text-[11px] font-semibold text-content-muted transition hover:text-amber-500">
                            کد یک‌بارمصرف تصادفی (کلیک برای تولید مجدد)
                        </button>
                    </div>
                </div>

                <div class="px-4 pb-4 pt-6 sm:px-6">
                    <h3 class="text-xl font-extrabold text-content-main sm:text-2xl">ساخته‌شده برای ایران</h3>
                    <p class="mt-2 max-w-lg text-sm leading-7 text-content-muted sm:text-base sm:leading-8">
                        تقویم جلالی، اعداد فارسی، کد تایید، ذخیره‌ساز داده و کلیدهای API رمزنگاری‌شده.
                    </p>
                </div>
            </article>

        </div>
    </div>
</section>

@once
    <style>
        .features-dot-grid {
            background-image: radial-gradient(color-mix(in oklab, var(--color-content-muted) 25%, transparent) 1.25px, transparent 1.25px);
            background-size: 20px 20px;
        }
    </style>
@endonce
