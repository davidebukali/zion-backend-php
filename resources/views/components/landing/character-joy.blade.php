@props([
'class' => '',
'showSpeech' => true,
])

@php($uid = 'joy-' . \Illuminate\Support\Str::random(6))

<div {{ $attributes->merge(['class' => 'joy relative flex flex-col items-center select-none ' . $class]) }}>

    @once
    <style>
        .joy {
            --joy-cloth-hi: #E0504A;
            --joy-cloth: #C62828;
            --joy-cloth-lo: #8E1B1B;
            --joy-detail: #FFDAD6;
            --joy-skin-hi: #FFEEDD;
            --joy-skin: #FFDFC4;
            --joy-skin-lo: #E8B494;
            --joy-hair-hi: #5A413B;
            --joy-hair: #2C1D1A;
            --joy-hair-lo: #1A100E;
            --joy-pants-hi: #524242;
            --joy-pants: #382E2E;
            --joy-pants-lo: #241C1C;
            --joy-shoe-hi: #4A3C3C;
            --joy-shoe: #1C1414;
            --joy-sole: #E53935;
            --joy-book-hi: #5A1D24;
            --joy-book: #3B1015;
            --joy-page: #FFF9F5;
            --joy-page-lo: #E9DCD2;
            --joy-page-line: #D2C3B7;
            --joy-ribbon: #E0A800;
            --joy-eye-hi: #6B463C;
            --joy-ground: rgb(28 20 20 / 0.28);
        }

        .dark .joy {
            --joy-cloth-hi: #B83232;
            --joy-cloth: #9E1B1B;
            --joy-cloth-lo: #5E0E0E;
            --joy-ground: rgb(0 0 0 / 0.55);
        }

        .joy-sleeve {
            stroke: var(--joy-cloth);
        }

        .joy-cloth-lo {
            fill: var(--joy-cloth-lo);
        }

        .joy-detail {
            fill: var(--joy-detail);
        }

        .joy-detail-s {
            stroke: var(--joy-detail);
        }

        .joy-skin {
            fill: var(--joy-skin);
        }

        .joy-skin-lo {
            fill: var(--joy-skin-lo);
        }

        .joy-skin-ln {
            stroke: var(--joy-skin-lo);
        }

        .joy-hair-lo-s {
            stroke: var(--joy-hair-lo);
        }

        .joy-ink-s {
            stroke: var(--joy-hair);
        }

        .joy-sole {
            fill: var(--joy-sole);
        }

        .joy-page-ln {
            stroke: var(--joy-page-line);
        }

        .joy-ribbon {
            fill: var(--joy-ribbon);
        }

        .joy-ground {
            fill: var(--joy-ground);
        }

        @media (prefers-reduced-motion: reduce) {

            .joy .animate-character-arm-wave,
            .joy .animate-character-eye-blink {
                animation: none !important;
            }
        }
    </style>
    @endonce

    @if ($showSpeech)
    <div class="mb-2 flex justify-center" aria-live="polite">
        <div
            class="relative inline-flex items-center justify-center rounded-full border border-primary/20 bg-surface-container-highest px-3 py-1 text-xs tracking-tight shadow-sm whitespace-nowrap w-[148px] sm:w-[158px] text-center transition-colors duration-200">
            <span
                x-text="(status === 'success' || isCelebrating) ? 'Welcome to Tsiyon!' : (isInputFocused ? 'Almost there' : 'Reserve your spot')"
                :class="(status === 'success' || isCelebrating || isInputFocused) ? 'text-primary font-semibold' : 'text-on-surface font-medium'"
                class="transition-colors duration-200">
                Reserve your spot
            </span>

            <!-- Seamless Tail Pointer -->
            <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3.5 h-1.5 pointer-events-none">
                <svg viewBox="0 0 14 6" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full block overflow-visible">
                    <polygon points="0,0 7,6 14,0" style="fill: var(--color-surface-container-highest);" />
                    <polyline points="0,0 7,6 14,0" fill="none" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--color-primary); stroke-opacity: 0.25;" />
                    <line x1="0.5" y1="0" x2="13.5" y2="0" stroke-width="2" style="stroke: var(--color-surface-container-highest);" />
                </svg>
            </div>
        </div>
    </div>
    @endif

    <div class="relative aspect-[160/210] w-full max-w-[150px] [contain:paint_layout]">
        <svg viewBox="0 0 160 210" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full overflow-visible"
            role="img" aria-label="Joy, the Tsiyon guide, waving while holding a book">
            <defs>
                <!-- Soft blur levels used for ambient occlusion and highlights -->
                <filter id="{{ $uid }}-b1" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="1" />
                </filter>
                <filter id="{{ $uid }}-b3" x="-60%" y="-60%" width="220%" height="220%">
                    <feGaussianBlur stdDeviation="3" />
                </filter>
                <filter id="{{ $uid }}-b6" x="-100%" y="-100%" width="300%" height="300%">
                    <feGaussianBlur stdDeviation="6" />
                </filter>

                <!-- Key light sits top-left on every volume -->
                <radialGradient id="{{ $uid }}-cloth" cx="0.32" cy="0.18" r="0.95">
                    <stop offset="0" style="stop-color: var(--joy-cloth-hi)" />
                    <stop offset="0.5" style="stop-color: var(--joy-cloth)" />
                    <stop offset="1" style="stop-color: var(--joy-cloth-lo)" />
                </radialGradient>
                <radialGradient id="{{ $uid }}-skin" cx="0.35" cy="0.28" r="0.85">
                    <stop offset="0" style="stop-color: var(--joy-skin-hi)" />
                    <stop offset="0.55" style="stop-color: var(--joy-skin)" />
                    <stop offset="1" style="stop-color: var(--joy-skin-lo)" />
                </radialGradient>
                <radialGradient id="{{ $uid }}-hair" cx="0.35" cy="0.15" r="0.95">
                    <stop offset="0" style="stop-color: var(--joy-hair-hi)" />
                    <stop offset="0.55" style="stop-color: var(--joy-hair)" />
                    <stop offset="1" style="stop-color: var(--joy-hair-lo)" />
                </radialGradient>
                <linearGradient id="{{ $uid }}-pants" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0" style="stop-color: var(--joy-pants-hi)" />
                    <stop offset="0.5" style="stop-color: var(--joy-pants)" />
                    <stop offset="1" style="stop-color: var(--joy-pants-lo)" />
                </linearGradient>
                <linearGradient id="{{ $uid }}-shoe" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" style="stop-color: var(--joy-shoe-hi)" />
                    <stop offset="1" style="stop-color: var(--joy-shoe)" />
                </linearGradient>
                <radialGradient id="{{ $uid }}-eye" cx="0.35" cy="0.3" r="0.8">
                    <stop offset="0" style="stop-color: var(--joy-eye-hi)" />
                    <stop offset="1" style="stop-color: var(--joy-hair)" />
                </radialGradient>
                <linearGradient id="{{ $uid }}-book" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" style="stop-color: var(--joy-book-hi)" />
                    <stop offset="1" style="stop-color: var(--joy-book)" />
                </linearGradient>
                <linearGradient id="{{ $uid }}-page" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0" style="stop-color: var(--joy-page)" />
                    <stop offset="1" style="stop-color: var(--joy-page-lo)" />
                </linearGradient>

                <clipPath id="{{ $uid }}-torso">
                    <path
                        d="M 52 114 C 52 104, 108 104, 108 114 L 114 160 C 114 164, 110 166, 104 166 L 56 166 C 50 166, 46 164, 46 160 Z" />
                </clipPath>
                <clipPath id="{{ $uid }}-face">
                    <rect x="52" y="48" width="56" height="58" rx="24" />
                </clipPath>
            </defs>

            <!-- Ground contact shadow -->
            <ellipse cx="80" cy="197" rx="40" ry="6" class="joy-ground" filter="url(#{{ $uid }}-b3)" />

            <!-- Shoes -->
            <rect x="62" y="174" width="14" height="18" rx="7" fill="url(#{{ $uid }}-shoe)" />
            <rect x="84" y="174" width="14" height="18" rx="7" fill="url(#{{ $uid }}-shoe)" />
            <ellipse cx="67" cy="179" rx="3" ry="5" fill="#fff" opacity="0.2" filter="url(#{{ $uid }}-b1)" />
            <ellipse cx="89" cy="179" rx="3" ry="5" fill="#fff" opacity="0.2" filter="url(#{{ $uid }}-b1)" />
            <rect x="62" y="188" width="14" height="4" rx="2" class="joy-sole" />
            <rect x="84" y="188" width="14" height="4" rx="2" class="joy-sole" />

            <!-- Legs -->
            <rect x="64" y="152" width="10" height="26" rx="5" fill="url(#{{ $uid }}-pants)" />
            <rect x="86" y="152" width="10" height="26" rx="5" fill="url(#{{ $uid }}-pants)" />

            <!-- Torso -->
            <path
                d="M 52 114 C 52 104, 108 104, 108 114 L 114 160 C 114 164, 110 166, 104 166 L 56 166 C 50 166, 46 164, 46 160 Z"
                fill="url(#{{ $uid }}-cloth)" />
            <g clip-path="url(#{{ $uid }}-torso)">
                <!-- Core shadow on the far side, bounce light on the near side -->
                <ellipse cx="114" cy="140" rx="22" ry="40" class="joy-cloth-lo" opacity="0.55"
                    filter="url(#{{ $uid }}-b6)" />
                <ellipse cx="60" cy="122" rx="11" ry="16" fill="#fff" opacity="0.16" filter="url(#{{ $uid }}-b3)" />
                <rect x="40" y="159" width="80" height="10" fill="#000" opacity="0.22" filter="url(#{{ $uid }}-b3)" />
                <!-- Fabric folds -->
                <path d="M 58 150 Q 66 144 73 150" fill="none" stroke-width="2.5" stroke-linecap="round"
                    style="stroke: var(--joy-cloth-lo)" opacity="0.45" filter="url(#{{ $uid }}-b1)" />
                <path d="M 104 148 Q 96 142 89 148" fill="none" stroke-width="2.5" stroke-linecap="round"
                    style="stroke: var(--joy-cloth-lo)" opacity="0.45" filter="url(#{{ $uid }}-b1)" />
            </g>
            <!-- Torso shadow cast on legs -->
            <ellipse cx="80" cy="168" rx="28" ry="3" fill="#000" opacity="0.3" filter="url(#{{ $uid }}-b3)" />

            <!-- Neck with chin shadow -->
            <rect x="74" y="98" width="12" height="14" rx="4" fill="url(#{{ $uid }}-skin)" />
            <ellipse cx="80" cy="106" rx="10" ry="4" class="joy-skin-lo" opacity="0.7" filter="url(#{{ $uid }}-b3)" />

            <!-- Hood rim -->
            <path d="M 63 110 Q 80 127 97 110 Q 94 105 88 104 Q 80 119 72 104 Q 66 105 63 110 Z"
                fill="url(#{{ $uid }}-cloth)" />
            <path d="M 64 109 Q 80 125 96 109" fill="none" stroke="#fff" stroke-width="1" stroke-linecap="round"
                opacity="0.28" />

            <!-- Kangaroo pocket -->
            <path
                d="M 60 142 L 100 142 C 102 142, 104 144, 102 148 L 98 160 C 97 162, 95 163, 93 163 L 67 163 C 65 163, 63 162, 62 160 L 58 148 C 56 144, 58 142, 60 142 Z"
                class="joy-cloth-lo" opacity="0.5" />
            <path d="M 60 142.5 L 100 142.5" stroke="#fff" stroke-width="1" stroke-linecap="round" opacity="0.22" />
            <path d="M 58 148 L 62 160" stroke="#000" stroke-width="1" stroke-linecap="round" opacity="0.15" />

            <!-- Drawstrings -->
            <path d="M 74 116 Q 73 125 72 131" class="joy-detail-s" stroke-width="1.75" stroke-linecap="round"
                fill="none" />
            <path d="M 86 116 Q 87 125 88 131" class="joy-detail-s" stroke-width="1.75" stroke-linecap="round"
                fill="none" />
            <circle cx="72" cy="132" r="1.6" class="joy-detail" />
            <circle cx="88" cy="132" r="1.6" class="joy-detail" />

            <!-- Flame insignia -->
            <path
                d="M 80 128 C 76.5 130.5 75 133 75 135.5 C 75 138.5 77.5 141 80 141 C 82.5 141 85 138.5 85 135.5 C 85 133 83.5 130.5 80 128 Z"
                class="joy-detail" opacity="0.92" />

            <!-- Left arm (holding book) -->
            <path d="M 52 114 C 40 120, 36 138, 48 148 C 54 154, 62 150, 68 146" class="joy-sleeve" stroke-width="12"
                stroke-linecap="round" stroke-linejoin="round" fill="none" />
            <path d="M 51 113 C 40 119, 35.5 137, 47 147" stroke="#fff" stroke-width="3.5" stroke-linecap="round"
                fill="none" opacity="0.22" filter="url(#{{ $uid }}-b1)" />

            <!-- Book -->
            <g transform="translate(38, 132) rotate(-8)">
                <ellipse cx="16" cy="25" rx="15" ry="2.5" fill="#000" opacity="0.28" filter="url(#{{ $uid }}-b1)" />
                <rect width="30" height="22" rx="3.5" fill="url(#{{ $uid }}-book)" style="stroke: var(--joy-detail)"
                    stroke-width="0.75" />
                <rect x="2" y="2" width="26" height="18" rx="2" fill="url(#{{ $uid }}-page)" />
                <line x1="15" y1="2" x2="15" y2="20" class="joy-page-ln" stroke-width="1.2" />
                <g class="joy-page-ln" stroke-width="0.8" stroke-linecap="round">
                    <line x1="5" y1="6" x2="12" y2="6" />
                    <line x1="5" y1="10" x2="12" y2="10" />
                    <line x1="5" y1="14" x2="11" y2="14" />
                    <line x1="18" y1="6" x2="25" y2="6" />
                    <line x1="18" y1="10" x2="25" y2="10" />
                    <line x1="18" y1="14" x2="24" y2="14" />
                </g>
                <path d="M 15 2 L 15 23 L 13.5 21 L 12 23 L 12 2" class="joy-ribbon" />
                <circle cx="28" cy="14" r="5.5" fill="url(#{{ $uid }}-skin)" />
                <ellipse cx="26.5" cy="12" rx="2" ry="1.4" fill="#fff" opacity="0.4" filter="url(#{{ $uid }}-b1)" />
            </g>

            <!-- Right arm (wave) -->
            <g class="animate-character-arm-wave origin-[106px_115px]">
                <path d="M 106 115 Q 124 104 130 90" class="joy-sleeve" stroke-width="12" stroke-linecap="round"
                    fill="none" />
                <path d="M 106 111 Q 122 101 128 88" stroke="#fff" stroke-width="3.5" stroke-linecap="round" fill="none"
                    opacity="0.22" />
                <circle cx="132" cy="85" r="7.5" fill="url(#{{ $uid }}-skin)" />
                <path d="M 129 79 Q 134 73 138 78 Q 142 82 136 88" fill="url(#{{ $uid }}-skin)" />
                <ellipse cx="130" cy="82" rx="2.5" ry="1.8" fill="#fff" opacity="0.4" />
                <path d="M 127 82 Q 130 79 133 81" class="joy-skin-ln" stroke-width="1" stroke-linecap="round"
                    fill="none" />
            </g>

            <!-- Ears -->
            <circle cx="50" cy="78" r="6" fill="url(#{{ $uid }}-skin)" />
            <circle cx="50" cy="78" r="3" class="joy-skin-lo" opacity="0.8" />
            <circle cx="110" cy="78" r="6" fill="url(#{{ $uid }}-skin)" />
            <circle cx="110" cy="78" r="3" class="joy-skin-lo" opacity="0.8" />

            <!-- Face -->
            <rect x="52" y="48" width="56" height="58" rx="24" fill="url(#{{ $uid }}-skin)" />
            <g clip-path="url(#{{ $uid }}-face)">
                <ellipse cx="100" cy="102" rx="24" ry="18" class="joy-skin-lo" opacity="0.55"
                    filter="url(#{{ $uid }}-b6)" />
                <ellipse cx="70" cy="56" rx="12" ry="7" fill="#fff" opacity="0.35" filter="url(#{{ $uid }}-b3)" />
                <ellipse cx="80" cy="63" rx="26" ry="5" fill="#000" opacity="0.16" filter="url(#{{ $uid }}-b3)" />
                <ellipse cx="62" cy="86" rx="6" ry="3.5" fill="#F28B82" opacity="0.35" filter="url(#{{ $uid }}-b3)" />
                <ellipse cx="98" cy="86" rx="6" ry="3.5" fill="#F28B82" opacity="0.35" filter="url(#{{ $uid }}-b3)" />
                <ellipse cx="79" cy="77" rx="1.6" ry="3.2" fill="#fff" opacity="0.45" filter="url(#{{ $uid }}-b1)" />
                <ellipse cx="83" cy="82" rx="2.5" ry="1.8" class="joy-skin-lo" opacity="0.6"
                    filter="url(#{{ $uid }}-b1)" />
            </g>

            <!-- Hair -->
            <path
                d="M 48 64 C 48 38, 112 38, 112 64 C 114 74, 110 82, 108 84 C 104 74, 98 60, 80 60 C 64 60, 54 68, 50 82 C 48 78, 48 70, 48 64 Z"
                fill="url(#{{ $uid }}-hair)" />
            <path d="M 62 49 Q 78 40 98 49" stroke="#fff" stroke-width="3" stroke-linecap="round" fill="none"
                opacity="0.2" filter="url(#{{ $uid }}-b1)" />
            <path d="M 66 43 Q 80 33 96 41 Q 88 47 78 45 Z" style="fill: var(--joy-hair-hi)" opacity="0.8" />

            <!-- Eyes (blink) -->
            <g class="animate-character-eye-blink origin-[80px_76px]">
                <ellipse cx="66" cy="76" rx="4.5" ry="5.5" fill="url(#{{ $uid }}-eye)" />
                <circle cx="67.8" cy="74" r="1.7" fill="#fff" />
                <circle cx="64.6" cy="78" r="0.8" fill="#fff" opacity="0.7" />
                <ellipse cx="94" cy="76" rx="4.5" ry="5.5" fill="url(#{{ $uid }}-eye)" />
                <circle cx="95.8" cy="74" r="1.7" fill="#fff" />
                <circle cx="92.6" cy="78" r="0.8" fill="#fff" opacity="0.7" />
            </g>

            <!-- Brows -->
            <path d="M 60 67 Q 66 64 71 67" class="joy-ink-s" stroke-width="1.8" stroke-linecap="round" fill="none" />
            <path d="M 89 67 Q 94 64 100 67" class="joy-ink-s" stroke-width="1.8" stroke-linecap="round" fill="none" />

            <!-- Smile -->
            <path d="M 73 89.2 Q 80 95.2 87 89.2" class="joy-skin-ln" stroke-width="2" stroke-linecap="round"
                fill="none" opacity="0.6" filter="url(#{{ $uid }}-b1)" />
            <path d="M 73 88 Q 80 94 87 88" stroke="#8A3B2E" stroke-width="2" stroke-linecap="round" fill="none" />
        </svg>
    </div>
</div>