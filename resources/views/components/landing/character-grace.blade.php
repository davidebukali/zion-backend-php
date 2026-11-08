@props([
    'class' => '',
    'showSpeech' => true,
])

@php
    $uid = 'grace-' . \Illuminate\Support\Str::random(6);
    $done = "isCelebrating || status === 'submitting' || status === 'success' || status === 'already_registered'";
@endphp

<div {{ $attributes->merge(['class' => 'grace relative flex flex-col items-center select-none ' . $class]) }}>

    @once
    <style>
        .grace {
            --grace-cloth-hi: #A8323B;
            --grace-cloth: #7A1D24;
            --grace-cloth-lo: #4A0F15;
            --grace-skin-hi: #FFEEDD;
            --grace-skin: #FFDFC4;
            --grace-skin-lo: #E8B494;
            --grace-hair-hi: #5C3A35;
            --grace-hair: #301A18;
            --grace-hair-lo: #1C0F0E;
            --grace-pants-hi: #4F3A3A;
            --grace-pants: #382626;
            --grace-pants-lo: #241818;
            --grace-shoe-hi: #4A3C3C;
            --grace-shoe: #1C1414;
            --grace-sole: #9E1B1B;
            --grace-gold-hi: #FFEAA8;
            --grace-gold: #F2B705;
            --grace-gold-lo: #B88400;
            --grace-lip: #9B2B34;
            --grace-ground: rgb(28 20 20 / 0.28);
        }

        .dark .grace {
            --grace-cloth-hi: #8A2630;
            --grace-cloth: #63141A;
            --grace-cloth-lo: #3B0A0F;
            --grace-ground: rgb(0 0 0 / 0.55);
        }

        .grace-sleeve {
            stroke: var(--grace-cloth);
        }

        .grace-cloth-lo {
            fill: var(--grace-cloth-lo);
        }

        .grace-skin-lo {
            fill: var(--grace-skin-lo);
        }

        .grace-skin-ln {
            stroke: var(--grace-skin-lo);
        }

        .grace-hair-s {
            stroke: var(--grace-hair);
        }

        .grace-hair-lo-s {
            stroke: var(--grace-hair-lo);
        }

        .grace-lip-s {
            stroke: var(--grace-lip);
        }

        .grace-sole {
            fill: var(--grace-sole);
        }

        .grace-ground {
            fill: var(--grace-ground);
        }
    </style>
    @endonce

    @if ($showSpeech)
        <div class="mb-2 flex justify-center" aria-live="polite">
            <div
                class="relative inline-flex items-center justify-center rounded-full border border-primary/20 bg-surface-container-highest px-3 py-1 text-xs tracking-tight shadow-sm whitespace-nowrap w-[148px] sm:w-[158px] text-center transition-colors duration-200">
                <span
                    x-text="({!! $done !!}) ? 'God bless you' : (isInputFocused ? 'We’re praying for you' : 'Fellowship in peace')"
                    :class="(({!! $done !!}) || isInputFocused) ? 'text-primary font-semibold' : 'text-on-surface font-medium'"
                    class="transition-colors duration-200">
                    Fellowship in peace
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
            role="img" aria-label="Grace, the Tsiyon guide, in quiet prayer with a halo above her head">
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
                    <stop offset="0" style="stop-color: var(--grace-cloth-hi)" />
                    <stop offset="0.5" style="stop-color: var(--grace-cloth)" />
                    <stop offset="1" style="stop-color: var(--grace-cloth-lo)" />
                </radialGradient>
                <radialGradient id="{{ $uid }}-skin" cx="0.35" cy="0.28" r="0.85">
                    <stop offset="0" style="stop-color: var(--grace-skin-hi)" />
                    <stop offset="0.55" style="stop-color: var(--grace-skin)" />
                    <stop offset="1" style="stop-color: var(--grace-skin-lo)" />
                </radialGradient>
                <radialGradient id="{{ $uid }}-hair" cx="0.35" cy="0.15" r="0.95">
                    <stop offset="0" style="stop-color: var(--grace-hair-hi)" />
                    <stop offset="0.55" style="stop-color: var(--grace-hair)" />
                    <stop offset="1" style="stop-color: var(--grace-hair-lo)" />
                </radialGradient>
                <linearGradient id="{{ $uid }}-pants" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0" style="stop-color: var(--grace-pants-hi)" />
                    <stop offset="0.5" style="stop-color: var(--grace-pants)" />
                    <stop offset="1" style="stop-color: var(--grace-pants-lo)" />
                </linearGradient>
                <linearGradient id="{{ $uid }}-shoe" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" style="stop-color: var(--grace-shoe-hi)" />
                    <stop offset="1" style="stop-color: var(--grace-shoe)" />
                </linearGradient>
                <linearGradient id="{{ $uid }}-gold" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" style="stop-color: var(--grace-gold-hi)" />
                    <stop offset="0.5" style="stop-color: var(--grace-gold)" />
                    <stop offset="1" style="stop-color: var(--grace-gold-lo)" />
                </linearGradient>

                <!-- Knit rib texture -->
                <pattern id="{{ $uid }}-rib" width="4" height="6" patternUnits="userSpaceOnUse">
                    <rect width="1.6" height="6" fill="#000" opacity="0.14" />
                </pattern>

                <clipPath id="{{ $uid }}-torso">
                    <path
                        d="M 50 114 C 50 104, 110 104, 110 114 L 115 160 C 115 164, 111 166, 105 166 L 55 166 C 49 166, 45 164, 45 160 Z" />
                </clipPath>
                <clipPath id="{{ $uid }}-face">
                    <rect x="52" y="48" width="56" height="58" rx="24" />
                </clipPath>
            </defs>

            <!-- Ground contact shadow -->
            <ellipse cx="80" cy="197" rx="40" ry="6" class="grace-ground" filter="url(#{{ $uid }}-b3)" />

            <!-- Halo (static ring with a lit top edge) -->
            <ellipse cx="80" cy="36" rx="26" ry="6.5" fill="none" stroke="url(#{{ $uid }}-gold)" stroke-width="3" />
            <path d="M 56 34 Q 80 26.5 104 34" fill="none" stroke="#fff" stroke-width="1" stroke-linecap="round"
                opacity="0.55" />

            <!-- Shoes -->
            <rect x="63" y="174" width="13" height="18" rx="6.5" fill="url(#{{ $uid }}-shoe)" />
            <rect x="84" y="174" width="13" height="18" rx="6.5" fill="url(#{{ $uid }}-shoe)" />
            <ellipse cx="67.5" cy="179" rx="2.8" ry="5" fill="#fff" opacity="0.2" filter="url(#{{ $uid }}-b1)" />
            <ellipse cx="88.5" cy="179" rx="2.8" ry="5" fill="#fff" opacity="0.2" filter="url(#{{ $uid }}-b1)" />
            <rect x="63" y="188" width="13" height="4" rx="2" class="grace-sole" />
            <rect x="84" y="188" width="13" height="4" rx="2" class="grace-sole" />

            <!-- Legs -->
            <rect x="65" y="152" width="9" height="26" rx="4.5" fill="url(#{{ $uid }}-pants)" />
            <rect x="86" y="152" width="9" height="26" rx="4.5" fill="url(#{{ $uid }}-pants)" />

            <!-- Neck with chin shadow -->
            <rect x="74" y="98" width="12" height="14" rx="4" fill="url(#{{ $uid }}-skin)" />
            <ellipse cx="80" cy="105" rx="10" ry="4" class="grace-skin-lo" opacity="0.7" filter="url(#{{ $uid }}-b3)" />

            <!-- Torso, collar, arms and hands (static and crisp, matching Joy) -->
            <g>
                <path
                    d="M 50 114 C 50 104, 110 104, 110 114 L 115 160 C 115 164, 111 166, 105 166 L 55 166 C 49 166, 45 164, 45 160 Z"
                    fill="url(#{{ $uid }}-cloth)" />
                <g clip-path="url(#{{ $uid }}-torso)">
                    <rect x="40" y="104" width="80" height="64" fill="url(#{{ $uid }}-rib)" />
                    <ellipse cx="116" cy="140" rx="22" ry="40" class="grace-cloth-lo" opacity="0.55"
                        filter="url(#{{ $uid }}-b6)" />
                    <ellipse cx="58" cy="122" rx="11" ry="16" fill="#fff" opacity="0.14" filter="url(#{{ $uid }}-b3)" />
                    <rect x="40" y="159" width="80" height="10" fill="#000" opacity="0.22"
                        filter="url(#{{ $uid }}-b3)" />
                    <path d="M 56 152 Q 64 146 71 152" fill="none" stroke-width="2.5" stroke-linecap="round"
                        style="stroke: var(--grace-cloth-lo)" opacity="0.45" filter="url(#{{ $uid }}-b1)" />
                    <path d="M 106 150 Q 98 144 91 150" fill="none" stroke-width="2.5" stroke-linecap="round"
                        style="stroke: var(--grace-cloth-lo)" opacity="0.45" filter="url(#{{ $uid }}-b1)" />
                </g>
                <ellipse cx="80" cy="168" rx="28" ry="3" fill="#000" opacity="0.3" filter="url(#{{ $uid }}-b3)" />

                <!-- Rolled turtleneck collar -->
                <path d="M 65 106 Q 80 116 95 106 Q 90 118 80 118 Q 70 118 65 106 Z" fill="url(#{{ $uid }}-cloth)" />
                <path d="M 67 107.5 Q 80 115.5 93 107.5" fill="none" stroke="#fff" stroke-width="1"
                    stroke-linecap="round" opacity="0.28" />
                <path d="M 70 116 Q 80 121 90 116" fill="none" stroke-width="2" stroke-linecap="round"
                    style="stroke: var(--grace-cloth-lo)" opacity="0.4" filter="url(#{{ $uid }}-b1)" />

                <!-- Arms folded forward in prayer -->
                <path d="M 52 116 C 44 126, 54 144, 74 140" class="grace-sleeve" stroke-width="12"
                    stroke-linecap="round" stroke-linejoin="round" fill="none" />
                <path d="M 108 116 C 116 126, 106 144, 86 140" class="grace-sleeve" stroke-width="12"
                    stroke-linecap="round" stroke-linejoin="round" fill="none" />
                <path d="M 51 115 C 43.5 125, 53 142, 72 138" stroke="#fff" stroke-width="3.5" stroke-linecap="round"
                    fill="none" opacity="0.2" filter="url(#{{ $uid }}-b1)" />
                <path d="M 112 124 C 114 134, 104 144, 88 142" stroke="#000" stroke-width="4" stroke-linecap="round"
                    fill="none" opacity="0.18" filter="url(#{{ $uid }}-b1)" />

                <!-- Folded hands -->
                <ellipse cx="80" cy="140" rx="9" ry="3" fill="#000" opacity="0.2" filter="url(#{{ $uid }}-b1)" />
                <path
                    d="M 74 134 C 74 127, 77 123, 80 123 C 83 123, 86 127, 86 134 L 86 143 C 86 146, 83 148, 80 148 C 77 148, 74 146, 74 143 Z"
                    fill="url(#{{ $uid }}-skin)" />
                <path d="M 80 125 L 80 142" class="grace-skin-ln" stroke-width="1.2" stroke-linecap="round"
                    fill="none" />
                <ellipse cx="77.2" cy="130" rx="1.6" ry="4" fill="#fff" opacity="0.4" filter="url(#{{ $uid }}-b1)" />
            </g>

            <!-- Ears -->
            <circle cx="50" cy="78" r="6" fill="url(#{{ $uid }}-skin)" />
            <circle cx="50" cy="78" r="3" class="grace-skin-lo" opacity="0.8" />
            <circle cx="110" cy="78" r="6" fill="url(#{{ $uid }}-skin)" />
            <circle cx="110" cy="78" r="3" class="grace-skin-lo" opacity="0.8" />

            <!-- Face (Restored to exact full original detail) -->
            <rect x="52" y="48" width="56" height="58" rx="24" fill="url(#{{ $uid }}-skin)" />
            <g clip-path="url(#{{ $uid }}-face)">
                <ellipse cx="100" cy="102" rx="24" ry="18" class="grace-skin-lo" opacity="0.55"
                    filter="url(#{{ $uid }}-b6)" />
                <ellipse cx="70" cy="57" rx="12" ry="7" fill="#fff" opacity="0.35" filter="url(#{{ $uid }}-b3)" />
                <ellipse cx="80" cy="64" rx="26" ry="5" fill="#000" opacity="0.16" filter="url(#{{ $uid }}-b3)" />
                <ellipse cx="62" cy="86" rx="6" ry="3.5" fill="#F28B82" opacity="0.38" filter="url(#{{ $uid }}-b3)" />
                <ellipse cx="98" cy="86" rx="6" ry="3.5" fill="#F28B82" opacity="0.38" filter="url(#{{ $uid }}-b3)" />
                <ellipse cx="79.5" cy="78" rx="1.6" ry="3.2" fill="#fff" opacity="0.45" filter="url(#{{ $uid }}-b1)" />
                <ellipse cx="83" cy="83" rx="2.5" ry="1.8" class="grace-skin-lo" opacity="0.6"
                    filter="url(#{{ $uid }}-b1)" />
                <!-- Soft lid shading -->
                <ellipse cx="67" cy="73" rx="6" ry="2.5" class="grace-skin-lo" opacity="0.5"
                    filter="url(#{{ $uid }}-b1)" />
                <ellipse cx="93" cy="73" rx="6" ry="2.5" class="grace-skin-lo" opacity="0.5"
                    filter="url(#{{ $uid }}-b1)" />
            </g>

            <!-- Hair -->
            <path
                d="M 46 68 C 46 36, 114 36, 114 68 C 114 86, 112 108, 108 114 C 104 110, 102 96, 102 86 C 96 68, 84 64, 80 64 C 76 64, 64 68, 58 86 C 58 96, 56 110, 52 114 C 48 108, 46 86, 46 68 Z"
                fill="url(#{{ $uid }}-hair)" />
            <path
                d="M 54 62 C 66 58, 76 64, 80 68 C 84 64, 94 58, 106 62 C 102 54, 92 48, 80 48 C 68 48, 58 54, 54 62 Z"
                style="fill: var(--grace-hair-hi)" opacity="0.85" />
            <!-- Sheen and strands -->
            <path d="M 60 52 Q 78 43 100 52" stroke="#fff" stroke-width="3" stroke-linecap="round" fill="none"
                opacity="0.2" filter="url(#{{ $uid }}-b1)" />
            <path d="M 52 74 Q 50 92 54 108" stroke="#fff" stroke-width="2.5" stroke-linecap="round" fill="none"
                opacity="0.14" filter="url(#{{ $uid }}-b1)" />
            <path d="M 60 58 Q 52 80 55 106" class="grace-hair-lo-s" stroke-width="0.8" stroke-linecap="round"
                fill="none" opacity="0.5" />
            <path d="M 100 58 Q 108 80 105 106" class="grace-hair-lo-s" stroke-width="0.8" stroke-linecap="round"
                fill="none" opacity="0.5" />
            <!-- Halo shadow on hair -->
            <ellipse cx="80" cy="47" rx="18" ry="2.5" fill="#000" opacity="0.22" filter="url(#{{ $uid }}-b1)" />

            <!-- Closed eyes -->
            <path d="M 62 76 Q 67 82 72 76" class="grace-hair-s" stroke-width="2.5" stroke-linecap="round"
                fill="none" />
            <path d="M 88 76 Q 93 82 98 76" class="grace-hair-s" stroke-width="2.5" stroke-linecap="round"
                fill="none" />

            <!-- Brows -->
            <path d="M 61 70 Q 66 67 72 70" class="grace-hair-s" stroke-width="1.8" stroke-linecap="round"
                fill="none" />
            <path d="M 88 70 Q 94 67 99 70" class="grace-hair-s" stroke-width="1.8" stroke-linecap="round"
                fill="none" />

            <!-- Smile -->
            <path d="M 74 90.2 Q 80 95.2 86 90.2" class="grace-skin-ln" stroke-width="2" stroke-linecap="round"
                fill="none" opacity="0.6" filter="url(#{{ $uid }}-b1)" />
            <path d="M 74 89 Q 80 94 86 89" class="grace-lip-s" stroke-width="2" stroke-linecap="round" fill="none" />
        </svg>
    </div>
</div>
