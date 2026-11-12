@php
    $detachedNodes = [
        [
            'id' => 'agent',
            'label' => 'AI Agent',
            'icon' => 'brand:ai-agent',
            'color' => '#a855f7',
            'badgeBg' => 'bg-purple-500/15 text-purple-500',
            'dx' => 0.0,   'dy' => -0.34,
            'mx' => 0.0,   'my' => -0.35,
            'cx' => -0.14, 'cy' => -0.22,
        ],
        [
            'id' => 'gemini',
            'label' => 'Google Gemini',
            'icon' => 'brand:gemini',
            'color' => '#3b82f6',
            'badgeBg' => 'bg-blue-500/15 text-blue-500',
            'dx' => -0.27, 'dy' => -0.23,
            'mx' => -0.34, 'my' => -0.21,
            'cx' => -0.10, 'cy' => -0.34,
        ],
        [
            'id' => 'telegram',
            'label' => 'Telegram',
            'icon' => 'brand:telegram',
            'color' => '#0ea5e9',
            'badgeBg' => 'bg-sky-500/15 text-sky-500',
            'dx' => -0.38, 'dy' => 0.0,
            'mx' => -0.38, 'my' => 0.0,
            'cx' => -0.24, 'cy' => -0.16,
        ],
        [
            'id' => 'gmail',
            'label' => 'Gmail',
            'icon' => 'brand:gmail',
            'color' => '#f43f5e',
            'badgeBg' => 'bg-rose-500/15 text-rose-500',
            'dx' => -0.27, 'dy' => 0.23,
            'mx' => -0.34, 'my' => 0.21,
            'cx' => -0.26, 'cy' => 0.08,
        ],
        [
            'id' => 'jalali',
            'label' => 'تاریخ شمسی',
            'icon' => 'calendar',
            'color' => '#ec4899',
            'badgeBg' => 'bg-pink-500/15 text-pink-500',
            'dx' => 0.0,  'dy' => 0.34,
            'mx' => 0.0,  'my' => 0.35,
            'cx' => 0.14, 'cy' => 0.22,
        ],
        [
            'id' => 'sheets',
            'label' => 'Google Sheets',
            'icon' => 'brand:google-sheets',
            'color' => '#10b981',
            'badgeBg' => 'bg-emerald-500/15 text-emerald-500',
            'dx' => 0.27, 'dy' => -0.23,
            'mx' => 0.34, 'my' => -0.21,
            'cx' => 0.10, 'cy' => -0.34,
        ],
        [
            'id' => 'otp',
            'label' => 'کد تایید (OTP)',
            'icon' => 'dice-3',
            'color' => '#f59e0b',
            'badgeBg' => 'bg-amber-500/15 text-amber-500',
            'dx' => 0.38, 'dy' => 0.0,
            'mx' => 0.38, 'my' => 0.0,
            'cx' => 0.24, 'cy' => -0.16,
        ],
        [
            'id' => 'http',
            'label' => 'HTTP Request',
            'icon' => 'world',
            'color' => '#E21C48',
            'badgeBg' => 'bg-primary/15 text-primary',
            'dx' => 0.27, 'dy' => 0.23,
            'mx' => 0.34, 'my' => 0.21,
            'cx' => 0.26, 'cy' => 0.08,
        ],
    ];

    $orbsLayout = [
        ['row' => 0, 'y' => -0.72, 'r' => 0.74, 'angle' => 0,   'nodeIndex' => 0],
        ['row' => 0, 'y' => -0.72, 'r' => 0.74, 'angle' => 72,  'nodeIndex' => 5],
        ['row' => 0, 'y' => -0.72, 'r' => 0.74, 'angle' => 144, 'nodeIndex' => null],
        ['row' => 0, 'y' => -0.72, 'r' => 0.74, 'angle' => 216, 'nodeIndex' => null],
        ['row' => 0, 'y' => -0.72, 'r' => 0.74, 'angle' => 288, 'nodeIndex' => 1],

        ['row' => 1, 'y' => -0.34, 'r' => 1.00, 'angle' => 25,  'nodeIndex' => 6],
        ['row' => 1, 'y' => -0.34, 'r' => 1.00, 'angle' => 85,  'nodeIndex' => null],
        ['row' => 1, 'y' => -0.34, 'r' => 1.00, 'angle' => 145, 'nodeIndex' => 2],
        ['row' => 1, 'y' => -0.34, 'r' => 1.00, 'angle' => 205, 'nodeIndex' => null],
        ['row' => 1, 'y' => -0.34, 'r' => 1.00, 'angle' => 265, 'nodeIndex' => null],
        ['row' => 1, 'y' => -0.34, 'r' => 1.00, 'angle' => 325, 'nodeIndex' => 7],

        ['row' => 2, 'y' => 0.08,  'r' => 0.82, 'angle' => 0,   'nodeIndex' => 4],
        ['row' => 2, 'y' => 0.08,  'r' => 0.82, 'angle' => 72,  'nodeIndex' => null],
        ['row' => 2, 'y' => 0.08,  'r' => 0.82, 'angle' => 144, 'nodeIndex' => 3],
        ['row' => 2, 'y' => 0.08,  'r' => 0.82, 'angle' => 216, 'nodeIndex' => null],
        ['row' => 2, 'y' => 0.08,  'r' => 0.82, 'angle' => 288, 'nodeIndex' => null],

        ['row' => 3, 'y' => 0.46,  'r' => 0.52, 'angle' => 50,  'nodeIndex' => null],
        ['row' => 3, 'y' => 0.46,  'r' => 0.52, 'angle' => 170, 'nodeIndex' => null],
        ['row' => 3, 'y' => 0.46,  'r' => 0.52, 'angle' => 290, 'nodeIndex' => null],

        ['row' => 4, 'y' => 0.80,  'r' => 0.12, 'angle' => 90,  'nodeIndex' => null],
    ];
@endphp

<section x-data="tameshkShowcase(@js($orbsLayout), @js($detachedNodes))"
         class="relative h-[280vh] w-full border-t border-line-border bg-surface-base font-vazirmatn-rd select-none">

    <div class="sticky top-0 flex h-dvh w-full items-center justify-center overflow-hidden">
        <div class="showcase-dot-grid pointer-events-none absolute inset-0 opacity-85" aria-hidden="true"></div>

        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div x-ref="ambientGlow"
                 class="absolute left-1/2 top-1/2 size-120 -translate-x-1/2 -translate-y-1/2 rounded-full opacity-40 blur-3xl will-change-transform"
                 style="background: radial-gradient(circle, rgba(226,28,72,0.28) 0%, rgba(168,85,247,0.22) 45%, rgba(245,158,11,0.12) 70%, transparent 100%);"></div>
        </div>

        <div x-ref="stage" class="relative flex size-full max-w-6xl items-center justify-center">
            <svg x-ref="networkSvg"
                 viewBox="-600 -340 1200 680"
                 class="pointer-events-none absolute inset-0 size-full overflow-visible"
                 fill="none"
                 aria-hidden="true">
                <defs>
                    <linearGradient id="showcase-edge-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#E21C48" stop-opacity="0.95"/>
                        <stop offset="55%" stop-color="#a855f7" stop-opacity="0.85"/>
                        <stop offset="100%" stop-color="#10b981" stop-opacity="0.95"/>
                    </linearGradient>
                    <linearGradient id="showcase-spark-grad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#f59e0b"/>
                        <stop offset="50%" stop-color="#E21C48"/>
                        <stop offset="100%" stop-color="#a855f7"/>
                    </linearGradient>
                </defs>

                <g x-ref="sparksGroup" style="opacity: 0">
                    <path d="M -38 -42 Q 0 -10 42 -36" stroke="url(#showcase-spark-grad)" stroke-width="2" stroke-dasharray="4 6" class="showcase-spark-line"/>
                    <path d="M -52 6 Q 0 -22 54 8" stroke="url(#showcase-spark-grad)" stroke-width="2.2" stroke-dasharray="3 5" class="showcase-spark-line"/>
                    <path d="M -28 44 Q 4 12 32 42" stroke="url(#showcase-spark-grad)" stroke-width="1.8" stroke-dasharray="4 6" class="showcase-spark-line"/>
                    <path d="M 0 -68 L -14 -8 L 16 14 L -4 66" stroke="#f59e0b" stroke-width="1.6" stroke-dasharray="3 5" class="showcase-spark-line"/>
                    <circle cx="-26" cy="-18" r="3" fill="#f59e0b"/>
                    <circle cx="28" cy="-12" r="2.5" fill="#a855f7"/>
                    <circle cx="0" cy="24" r="3" fill="#E21C48"/>
                </g>

                <g x-ref="edgesGroup" style="opacity: 0">
                    @foreach ($detachedNodes as $idx => $n)
                        <path data-edge-bg="{{ $idx }}"
                              d="M 0 0 L 0 0"
                              stroke="var(--color-line-border)"
                              stroke-width="2"
                              stroke-linecap="round"/>
                        <path data-edge-line="{{ $idx }}"
                              d="M 0 0 L 0 0"
                              pathLength="1"
                              stroke="url(#showcase-edge-grad)"
                              stroke-width="2.5"
                              stroke-linecap="round"
                              stroke-dasharray="6 6"
                              class="showcase-edge-flow"/>
                        <circle data-edge-dot="{{ $idx }}"
                                r="4"
                                fill="{{ $n['color'] }}"
                                style="opacity: 0"/>
                    @endforeach
                </g>
            </svg>

            <div x-ref="crown"
                 class="pointer-events-none absolute left-1/2 top-1/2 z-40 -translate-x-1/2 -translate-y-1/2 will-change-transform">
                <svg width="116" height="68" viewBox="0 0 116 68" fill="none" class="overflow-visible drop-shadow-[0_0_14px_rgba(16,185,129,0.55)]">
                    <defs>
                        <linearGradient id="crown-leaf-center" x1="58" y1="2" x2="58" y2="62" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#6ee7b7"/>
                            <stop offset="45%" stop-color="#10b981"/>
                            <stop offset="100%" stop-color="#047857" stop-opacity="0.85"/>
                        </linearGradient>
                        <linearGradient id="crown-leaf-side" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#34d399" stop-opacity="0.95"/>
                            <stop offset="60%" stop-color="#10b981" stop-opacity="0.80"/>
                            <stop offset="100%" stop-color="#065f46" stop-opacity="0.65"/>
                        </linearGradient>
                    </defs>

                    <path d="M 54 56 L 10 20 L 28 48 L 54 58 Z"
                          fill="url(#crown-leaf-side)"
                          stroke="#6ee7b7"
                          stroke-width="1.25"
                          stroke-linejoin="round"/>
                    <path d="M 10 20 L 38 34 L 54 56 Z"
                          fill="#10b981"
                          fill-opacity="0.35"
                          stroke="#a7f3d0"
                          stroke-width="0.9"
                          stroke-linejoin="round"/>

                    <path d="M 62 56 L 106 20 L 88 48 L 62 58 Z"
                          fill="url(#crown-leaf-side)"
                          stroke="#6ee7b7"
                          stroke-width="1.25"
                          stroke-linejoin="round"/>
                    <path d="M 106 20 L 78 34 L 62 56 Z"
                          fill="#10b981"
                          fill-opacity="0.35"
                          stroke="#a7f3d0"
                          stroke-width="0.9"
                          stroke-linejoin="round"/>

                    <path d="M 58 3 L 41 37 L 58 60 L 75 37 Z"
                          fill="url(#crown-leaf-center)"
                          stroke="#a7f3d0"
                          stroke-width="1.5"
                          stroke-linejoin="round"/>
                    <path d="M 58 3 L 58 60"
                          stroke="#d1fae5"
                          stroke-width="1.2"
                          stroke-opacity="0.85"/>
                </svg>
            </div>

            @foreach ($orbsLayout as $index => $orb)
                @php
                    $nodeData = $orb['nodeIndex'] !== null ? ($detachedNodes[$orb['nodeIndex']] ?? null) : null;
                @endphp

                <div data-orb-index="{{ $index }}"
                     class="showcase-orb-shell absolute left-1/2 top-1/2 flex size-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center will-change-transform"
                     style="border-radius: 32px;">

                    <div data-orb-skin="{{ $index }}"
                         class="showcase-orb-sphere pointer-events-none absolute inset-0 rounded-[inherit]"></div>

                    @if ($nodeData)
                        <div data-node-card="{{ $index }}"
                             class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-2xl border border-line-border bg-surface-card shadow-lg"
                             style="opacity: 0">
                            <span data-node-badge="{{ $index }}"
                                  class="absolute -top-1.5 -right-1.5 z-20 flex size-5 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm ring-2 ring-surface-card"
                                  style="opacity: 0; transform: scale(0.4)">
                                <x-ui.icon name="check" class="size-3"/>
                            </span>

                            <span class="pointer-events-none absolute -left-1.5 top-1/2 size-2.5 -translate-y-1/2 rounded-full border-2 border-surface-card bg-primary"></span>
                            <span class="pointer-events-none absolute -right-1.5 top-1/2 size-2.5 -translate-y-1/2 rounded-full border-2 border-surface-card bg-content-muted"></span>

                            <div class="flex size-10 items-center justify-center rounded-xl shadow-inner {{ $nodeData['badgeBg'] }}">
                                <x-ui.icon :name="$nodeData['icon']" class="size-5.5"/>
                            </div>

                            <div data-node-label="{{ $index }}"
                                 class="absolute top-full left-1/2 mt-2 w-28 -translate-x-1/2 text-center"
                                 style="opacity: 0">
                                <p class="truncate text-[11px] font-extrabold text-content-main">{{ $nodeData['label'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

@once
    <style>
        #main-content {
            overflow-x: clip !important;
            overflow-y: visible !important;
        }

        .showcase-dot-grid {
            background-image: radial-gradient(color-mix(in oklab, var(--color-content-muted) 26%, transparent) 1.35px, transparent 1.35px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(ellipse 80% 75% at 50% 50%, #000 35%, transparent 100%);
            mask-image: radial-gradient(ellipse 80% 75% at 50% 50%, #000 35%, transparent 100%);
        }

        .showcase-orb-sphere {
            background:
                radial-gradient(circle at 28% 24%, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0) 26%),
                radial-gradient(circle at 50% 52%, #0c0410 18%, #280926 52%, #a855f7 76%, #E21C48 91%, #f59e0b 100%);
            box-shadow:
                inset 0 0 10px rgba(245, 158, 11, 0.45),
                inset -3px -4px 12px rgba(168, 85, 247, 0.65),
                0 6px 18px -4px rgba(226, 28, 72, 0.38);
            border: 1px solid rgba(255, 255, 255, 0.24);
        }

        @keyframes showcase-dash-flow {
            to {
                stroke-dashoffset: -24;
            }
        }

        .showcase-edge-flow {
            animation: showcase-dash-flow 1.2s linear infinite;
        }

        .showcase-spark-line {
            animation: showcase-dash-flow 0.6s linear infinite;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tameshkShowcase', (orbsConfig, nodesConfig) => ({
                targetProgress: 0,
                currentProgress: 0,
                rafId: null,
                stageW: 1000,
                stageH: 640,
                isMobile: false,
                orbEls: [],
                edgeLines: [],
                edgeBgs: [],
                edgeDots: [],

                init() {
                    const section = this.$el;

                    this.orbEls = orbsConfig.map((cfg, idx) => {
                        const shell = section.querySelector(`[data-orb-index="${idx}"]`);
                        return {
                            cfg,
                            shell,
                            skin: section.querySelector(`[data-orb-skin="${idx}"]`),
                            card: section.querySelector(`[data-node-card="${idx}"]`),
                            badge: section.querySelector(`[data-node-badge="${idx}"]`),
                            label: section.querySelector(`[data-node-label="${idx}"]`),
                            nodeCfg: cfg.nodeIndex !== null ? nodesConfig[cfg.nodeIndex] : null,
                        };
                    });

                    this.edgeLines = nodesConfig.map((_, i) => section.querySelector(`[data-edge-line="${i}"]`));
                    this.edgeBgs = nodesConfig.map((_, i) => section.querySelector(`[data-edge-bg="${i}"]`));
                    this.edgeDots = nodesConfig.map((_, i) => section.querySelector(`[data-edge-dot="${i}"]`));

                    const updateBounds = () => {
                        if (!this.$refs.stage) return;
                        const rect = this.$refs.stage.getBoundingClientRect();
                        this.stageW = Math.max(320, rect.width);
                        this.stageH = Math.max(420, rect.height);
                        this.isMobile = window.innerWidth < 640;

                        if (this.$refs.networkSvg) {
                            this.$refs.networkSvg.setAttribute(
                                'viewBox',
                                `${-this.stageW / 2} ${-this.stageH / 2} ${this.stageW} ${this.stageH}`
                            );
                        }

                        this.renderFrame(this.currentProgress);
                    };

                    const onScroll = () => {
                        const rect = section.getBoundingClientRect();
                        const scrollable = Math.max(1, rect.height - window.innerHeight);
                        const raw = -rect.top / scrollable;
                        this.targetProgress = Math.max(0, Math.min(1, raw));
                        this.startLoop();
                    };

                    updateBounds();
                    onScroll();

                    window.addEventListener('scroll', onScroll, { passive: true });
                    window.addEventListener('resize', updateBounds, { passive: true });
                },

                startLoop() {
                    if (this.rafId !== null) return;

                    const tick = () => {
                        const diff = this.targetProgress - this.currentProgress;

                        if (Math.abs(diff) < 0.0006) {
                            this.currentProgress = this.targetProgress;
                            this.renderFrame(this.currentProgress);
                            this.rafId = null;
                            return;
                        }

                        this.currentProgress += diff * 0.14;
                        this.renderFrame(this.currentProgress);
                        this.rafId = requestAnimationFrame(tick);
                    };

                    this.rafId = requestAnimationFrame(tick);
                },

                clamp01(v) {
                    return Math.max(0, Math.min(1, v));
                },

                smoothstep(t) {
                    const c = this.clamp01(t);
                    return c * c * (3 - 2 * c);
                },

                computeClusterPos(cfg, rotAngle, expandFactor) {
                    const baseR = this.isMobile ? 48 : 62;
                    const baseH = this.isMobile ? 64 : 82;

                    const rad = (cfg.angle * Math.PI) / 180;
                    const x0 = Math.cos(rad);
                    const z0 = Math.sin(rad) * cfg.r;
                    const y0 = cfg.y;

                    const cosA = Math.cos(rotAngle);
                    const sinA = Math.sin(rotAngle);

                    const yRot = y0 * cosA - z0 * sinA;
                    const zRot = y0 * sinA + z0 * cosA;

                    const yNorm01 = this.clamp01((yRot + 0.9) / 1.8);
                    const coneEnvelope = 1.04 - 0.72 * Math.pow(yNorm01, 1.45);

                    const x = x0 * coneEnvelope * baseR * expandFactor;
                    const y = yRot * baseH * expandFactor;
                    const z = zRot;

                    return { x, y, z };
                },

                renderFrame(p) {
                    const p1 = this.clamp01(p / 0.30);
                    const p2 = this.smoothstep((p - 0.30) / 0.45);
                    const p3 = this.smoothstep((p - 0.72) / 0.24);

                    const rotAngle = p1 * Math.PI * 1.45 + p2 * Math.PI * 0.35;

                    const chargePulse = Math.sin(p1 * Math.PI);
                    const expandFactor = 1 + chargePulse * 0.25 - p2 * 0.24 - p3 * 0.10;

                    if (this.$refs.sparksGroup) {
                        const sparkOpacity = this.clamp01(Math.sin(this.clamp01(p / 0.42) * Math.PI) * 1.15);
                        this.$refs.sparksGroup.style.opacity = sparkOpacity.toFixed(3);
                        this.$refs.sparksGroup.style.transform = `scale(${(1 + chargePulse * 0.15).toFixed(3)})`;
                    }

                    if (this.$refs.ambientGlow) {
                        const glowScale = 0.9 + chargePulse * 0.35 + p3 * 0.45;
                        this.$refs.ambientGlow.style.transform = `translate3d(-50%, -50%, 0) scale(${glowScale.toFixed(3)})`;
                    }

                    if (this.$refs.crown) {
                        const crownY = -(this.isMobile ? 66 : 82) * expandFactor + p3 * 10;
                        const crownScale = (this.isMobile ? 0.84 : 1) * (1 + chargePulse * 0.08 - p2 * 0.14);
                        const crownTilt = Math.sin(p1 * Math.PI * 2) * 5;
                        this.$refs.crown.style.transform = `translate3d(0, ${crownY.toFixed(1)}px, 0) scale(${crownScale.toFixed(3)}) rotate(${crownTilt.toFixed(1)}deg)`;
                    }

                    for (let i = 0; i < this.orbEls.length; i++) {
                        const item = this.orbEls[i];
                        const clusterPos = this.computeClusterPos(item.cfg, rotAngle, expandFactor);

                        const depthNorm = this.clamp01((clusterPos.z + 1) / 2);
                        const baseScale = (this.isMobile ? 0.72 : 0.84) + depthNorm * 0.34;
                        const baseBrightness = 0.58 + depthNorm * 0.55;

                        if (!item.nodeCfg) {
                            const coreScale = baseScale * (1 - p2 * 0.16 + p3 * 0.08);
                            const zIdx = Math.round(10 + depthNorm * 20);
                            item.shell.style.zIndex = String(zIdx);
                            item.shell.style.transform = `translate3d(${clusterPos.x.toFixed(1)}px, ${clusterPos.y.toFixed(1)}px, 0) scale(${coreScale.toFixed(3)})`;
                            item.skin.style.filter = `brightness(${(baseBrightness + p3 * 0.30).toFixed(2)})`;
                            continue;
                        }

                        const nCfg = item.nodeCfg;
                        const targetX = (this.isMobile ? nCfg.mx : nCfg.dx) * this.stageW;
                        const targetY = (this.isMobile ? nCfg.my : nCfg.dy) * this.stageH;
                        const ctrlX = nCfg.cx * this.stageW;
                        const ctrlY = nCfg.cy * this.stageH;

                        const invT = 1 - p2;
                        const x = invT * invT * clusterPos.x + 2 * invT * p2 * ctrlX + p2 * p2 * targetX;
                        const y = invT * invT * clusterPos.y + 2 * invT * p2 * ctrlY + p2 * p2 * targetY;

                        const targetScale = this.isMobile ? 0.86 : 1.0;
                        const scale = baseScale * (1 - p2) + targetScale * p2;
                        const radiusPx = 32 - p2 * 16;

                        const zIdx = p2 > 0.15 ? 35 : Math.round(10 + depthNorm * 20);
                        item.shell.style.zIndex = String(zIdx);
                        item.shell.style.borderRadius = `${radiusPx.toFixed(1)}px`;
                        item.shell.style.transform = `translate3d(${x.toFixed(1)}px, ${y.toFixed(1)}px, 0) scale(${scale.toFixed(3)})`;

                        const cardOpacity = this.smoothstep((p2 - 0.18) / 0.65);
                        item.skin.style.opacity = (1 - cardOpacity).toFixed(3);
                        item.card.style.opacity = cardOpacity.toFixed(3);

                        if (item.label) {
                            const labelOpacity = this.smoothstep((p2 - 0.55) / 0.40);
                            item.label.style.opacity = labelOpacity.toFixed(3);
                        }

                        if (item.badge) {
                            item.badge.style.opacity = p3.toFixed(3);
                            item.badge.style.transform = `scale(${(0.4 + p3 * 0.6).toFixed(3)})`;
                        }

                        const edgeIdx = item.cfg.nodeIndex;
                        if (edgeIdx !== null && this.edgeLines[edgeIdx]) {
                            const qx = targetX * 0.45;
                            const qy = targetY * 0.15;
                            const pathD = `M 0 0 Q ${qx.toFixed(1)} ${qy.toFixed(1)} ${targetX.toFixed(1)} ${targetY.toFixed(1)}`;
                            this.edgeLines[edgeIdx].setAttribute('d', pathD);
                            this.edgeBgs[edgeIdx].setAttribute('d', pathD);

                            const dotEl = this.edgeDots[edgeIdx];
                            if (dotEl) {
                                const dotT = (p3 * 1.4 + edgeIdx * 0.12) % 1;
                                const dInv = 1 - dotT;
                                const dotX = 2 * dInv * dotT * qx + dotT * dotT * targetX;
                                const dotY = 2 * dInv * dotT * qy + dotT * dotT * targetY;

                                dotEl.setAttribute('cx', dotX.toFixed(1));
                                dotEl.setAttribute('cy', dotY.toFixed(1));
                                dotEl.style.opacity = p3 > 0.2 ? '1' : '0';
                            }
                        }
                    }

                    if (this.$refs.edgesGroup) {
                        this.$refs.edgesGroup.style.opacity = p3.toFixed(3);
                    }
                },
            }));
        });
    </script>
@endonce
