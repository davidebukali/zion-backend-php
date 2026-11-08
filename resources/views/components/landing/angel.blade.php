@props([
    'class' => '',
    'role' => 'lead', // 'lead' | 'left' | 'right'
    'mode' => 'fly',  // 'fly' | 'running-man' | 'two-step'
    'showSpeech' => true,
])

@php
    $uid = 'angel-' . $role . '-' . \Illuminate\Support\Str::random(6);
@endphp

<div {{ $attributes->merge(['class' => 'angel relative flex flex-col items-center select-none ' . $class]) }}>
    @once
    <style>
        .angel {
            --angel-robe-hi: #FFFFFF;
            --angel-robe: #F9F5F0;
            --angel-robe-lo: #E8DFD3;
            --angel-sash: #C62828;
            --angel-sash-lo: #8E1B1B;
            --angel-skin-hi: #FFEEDD;
            --angel-skin: #FFDFC4;
            --angel-skin-lo: #E8B494;
            --angel-wing-hi: #FFFFFF;
            --angel-wing: #FFF9F2;
            --angel-wing-lo: #EAE1D5;
            --angel-gold-hi: #FFEAA8;
            --angel-gold: #F2B705;
            --angel-gold-lo: #B88400;
            --angel-hair-hi: #7A533E;
            --angel-hair: #4E342E;
            --angel-hair-lo: #2E1F1B;
        }

        .dark .angel {
            --angel-robe-hi: #F5EFEB;
            --angel-robe: #E6DDD5;
            --angel-robe-lo: #BDB0A4;
            --angel-sash: #9E1B1B;
            --angel-sash-lo: #6B1010;
        }
    </style>
    @endonce

    @if ($showSpeech)
        <div class="mb-1.5 transition-transform duration-300" aria-live="polite">
            <div class="relative inline-flex items-center justify-center rounded-full border border-primary/20 bg-surface-container-highest px-3 py-1 text-[11px] font-semibold tracking-tight text-on-surface shadow-md whitespace-nowrap">
                @if ($role === 'lead')
                    <span x-show="isCelebrating || status === 'submitting' || status === 'success'" x-cloak class="text-primary font-bold">Welcome home! 🎉</span>
                    <span x-show="!(isCelebrating || status === 'submitting' || status === 'success') && isInputFocused" x-cloak class="text-primary font-medium">Let's go! 🏃💨</span>
                    <span x-show="!(isCelebrating || status === 'submitting' || status === 'success') && !isInputFocused" class="text-on-surface font-medium">Psst... reserve early! 🕊️</span>
                @elseif ($role === 'left')
                    <span class="text-primary font-bold">Glory! ✨</span>
                @else
                    <span class="text-primary font-bold">Hallelujah! 🙌</span>
                @endif

                <!-- Tail -->
                <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 border-b border-r border-primary/20 bg-surface-container-highest"></div>
            </div>
        </div>
    @endif

    <div class="relative aspect-[160/180] w-full max-w-[140px] drop-shadow-md">
        <svg viewBox="0 0 160 180" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full overflow-visible"
            role="img" aria-label="Dancing angel">
            <defs>
                <radialGradient id="{{ $uid }}-robe" cx="0.35" cy="0.2" r="0.85">
                    <stop offset="0" style="stop-color: var(--angel-robe-hi)" />
                    <stop offset="0.6" style="stop-color: var(--angel-robe)" />
                    <stop offset="1" style="stop-color: var(--angel-robe-lo)" />
                </radialGradient>
                <linearGradient id="{{ $uid }}-sash" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" style="stop-color: var(--angel-sash)" />
                    <stop offset="1" style="stop-color: var(--angel-sash-lo)" />
                </linearGradient>
                <radialGradient id="{{ $uid }}-skin" cx="0.35" cy="0.28" r="0.85">
                    <stop offset="0" style="stop-color: var(--angel-skin-hi)" />
                    <stop offset="0.55" style="stop-color: var(--angel-skin)" />
                    <stop offset="1" style="stop-color: var(--angel-skin-lo)" />
                </radialGradient>
                <linearGradient id="{{ $uid }}-gold" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" style="stop-color: var(--angel-gold-hi)" />
                    <stop offset="0.5" style="stop-color: var(--angel-gold)" />
                    <stop offset="1" style="stop-color: var(--angel-gold-lo)" />
                </linearGradient>
                <linearGradient id="{{ $uid }}-wing" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" style="stop-color: var(--angel-wing-hi)" />
                    <stop offset="0.6" style="stop-color: var(--angel-wing)" />
                    <stop offset="1" style="stop-color: var(--angel-wing-lo)" />
                </linearGradient>
            </defs>

            <!-- Dancing & Floating Root Group -->
            <g :class="{
                'animate-two-step-groove origin-bottom': isCelebrating || status === 'submitting' || status === 'success',
                'animate-running-man-bounce origin-bottom': !(isCelebrating || status === 'submitting' || status === 'success') && isInputFocused,
                'animate-angel-float origin-center': !(isCelebrating || status === 'submitting' || status === 'success') && !isInputFocused
            }">

                <!-- Left Wing (Behind Body) -->
                <g class="animate-angel-wing-left origin-[65px_95px]">
                    <path d="M 65 95 C 50 82, 25 72, 16 52 C 14 47, 18 42, 24 45 C 34 50, 42 62, 50 72 C 38 60, 26 48, 22 36 C 20 31, 25 27, 30 30 C 44 38, 56 56, 62 76 C 54 58, 45 42, 38 24 C 36 19, 42 16, 46 20 C 58 32, 68 56, 72 88 Z"
                          fill="url(#{{ $uid }}-wing)" stroke="#D4C8B8" stroke-width="0.8" opacity="0.95" />
                </g>

                <!-- Right Wing (Behind Body) -->
                <g class="animate-angel-wing-right origin-[95px_95px]">
                    <path d="M 95 95 C 110 82, 135 72, 144 52 C 146 47, 142 42, 136 45 C 126 50, 118 62, 110 72 C 122 60, 134 48, 138 36 C 140 31, 135 27, 130 30 C 116 38, 104 56, 98 76 C 106 58, 115 42, 122 24 C 124 19, 118 16, 114 20 C 102 32, 92 56, 88 88 Z"
                          fill="url(#{{ $uid }}-wing)" stroke="#D4C8B8" stroke-width="0.8" opacity="0.95" />
                </g>

                <!-- Golden Halo Ring with Radiance -->
                <g>
                    <ellipse cx="80" cy="32" rx="24" ry="6" fill="none" stroke="url(#{{ $uid }}-gold)" stroke-width="3" />
                    <path d="M 58 30 Q 80 23 102 30" fill="none" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round" opacity="0.65" />
                    <circle cx="80" cy="32" r="14" fill="#FFD54F" opacity="0.15" class="animate-pulse" />
                </g>

                <!-- Legs / Feet Layer -->
                <!-- Left Leg -->
                <g :class="{
                    'animate-running-man-leg-l origin-[68px_134px]': !(isCelebrating || status === 'submitting' || status === 'success') && isInputFocused,
                    'translate-y-0': isCelebrating || status === 'submitting' || status === 'success' || !isInputFocused
                }">
                    <rect x="65" y="132" width="8" height="22" rx="4" fill="url(#{{ $uid }}-skin)" />
                    <!-- Left Foot / Sandal -->
                    <ellipse cx="69" cy="154" rx="7" ry="4" fill="#8E1B1B" />
                    <ellipse cx="69" cy="153" rx="5" ry="2.5" fill="#C62828" />
                </g>

                <!-- Right Leg -->
                <g :class="{
                    'animate-running-man-leg-r origin-[92px_134px]': !(isCelebrating || status === 'submitting' || status === 'success') && isInputFocused,
                    'translate-y-0': isCelebrating || status === 'submitting' || status === 'success' || !isInputFocused
                }">
                    <rect x="87" y="132" width="8" height="22" rx="4" fill="url(#{{ $uid }}-skin)" />
                    <!-- Right Foot / Sandal -->
                    <ellipse cx="91" cy="154" rx="7" ry="4" fill="#8E1B1B" />
                    <ellipse cx="91" cy="153" rx="5" ry="2.5" fill="#C62828" />
                </g>

                <!-- Robe / Torso Body -->
                <g>
                    <!-- Flowing White Robe -->
                    <path d="M 54 94 C 54 84, 106 84, 106 94 L 114 140 C 114 145, 108 147, 102 147 L 58 147 C 52 147, 46 145, 46 140 Z"
                          fill="url(#{{ $uid }}-robe)" stroke="#D8CFC2" stroke-width="0.8" />

                    <!-- Flowing Hem & Shadow -->
                    <path d="M 47 138 Q 80 148 113 138" stroke="var(--angel-robe-lo)" stroke-width="2" fill="none" opacity="0.6" />

                    <!-- Tsiyon Crimson Sash -->
                    <path d="M 55 96 L 105 130 L 98 136 L 50 102 Z" fill="url(#{{ $uid }}-sash)" />
                    <circle cx="78" cy="115" r="4" fill="url(#{{ $uid }}-gold)" />
                </g>

                <!-- Arms Layer -->
                <!-- Left Arm -->
                <g :class="{
                    'animate-two-step-arm-l origin-[56px_98px]': isCelebrating || status === 'submitting' || status === 'success',
                    'animate-running-man-arm-l origin-[56px_98px]': !(isCelebrating || status === 'submitting' || status === 'success') && isInputFocused,
                    'rotate-0': !(isCelebrating || status === 'submitting' || status === 'success') && !isInputFocused
                }">
                    <!-- Arm Sleeve -->
                    <path d="M 56 96 C 46 104, 38 118, 52 126" stroke="url(#{{ $uid }}-robe)" stroke-width="10" stroke-linecap="round" fill="none" />
                    <!-- Hand -->
                    <circle cx="52" cy="126" r="5" fill="url(#{{ $uid }}-skin)" />
                </g>

                <!-- Right Arm -->
                <g :class="{
                    'animate-two-step-arm-r origin-[104px_98px]': isCelebrating || status === 'submitting' || status === 'success',
                    'animate-running-man-arm-r origin-[104px_98px]': !(isCelebrating || status === 'submitting' || status === 'success') && isInputFocused,
                    'rotate-0': !(isCelebrating || status === 'submitting' || status === 'success') && !isInputFocused
                }">
                    <!-- Arm Sleeve -->
                    <path d="M 104 96 C 114 104, 122 118, 108 126" stroke="url(#{{ $uid }}-robe)" stroke-width="10" stroke-linecap="round" fill="none" />
                    <!-- Hand -->
                    <circle cx="108" cy="126" r="5" fill="url(#{{ $uid }}-skin)" />
                </g>

                <!-- Neck -->
                <rect x="75" y="80" width="10" height="12" rx="3" fill="url(#{{ $uid }}-skin)" />

                <!-- Face -->
                <rect x="56" y="44" width="48" height="50" rx="20" fill="url(#{{ $uid }}-skin)" />

                <!-- Ears -->
                <circle cx="54" cy="68" r="4.5" fill="url(#{{ $uid }}-skin)" />
                <circle cx="106" cy="68" r="4.5" fill="url(#{{ $uid }}-skin)" />

                <!-- Hair (Warm Auburn/Golden Curl) -->
                <path d="M 52 58 C 52 32, 108 32, 108 58 C 110 66, 106 74, 104 76 C 100 68, 96 54, 80 54 C 64 54, 58 64, 54 74 C 52 70, 52 64, 52 58 Z"
                      fill="#4E342E" />
                <path d="M 68 38 Q 80 30 92 38 Q 84 44 76 42 Z" fill="#6D4C41" />

                <!-- Rosy Cheeks -->
                <ellipse cx="64" cy="74" rx="4.5" ry="2.8" fill="#F28B82" opacity="0.45" />
                <ellipse cx="96" cy="74" rx="4.5" ry="2.8" fill="#F28B82" opacity="0.45" />

                <!-- Eyes -->
                <g>
                    <!-- Left Eye (Joyful Crescent / Wink) -->
                    <path d="M 64 66 Q 68 70 72 66" stroke="#2C1D1A" stroke-width="2.2" stroke-linecap="round" fill="none" />
                    <!-- Right Eye (Joyful Crescent) -->
                    <path d="M 88 66 Q 92 70 96 66" stroke="#2C1D1A" stroke-width="2.2" stroke-linecap="round" fill="none" />
                </g>

                <!-- Brows -->
                <path d="M 62 60 Q 67 57 72 60" stroke="#4E342E" stroke-width="1.6" stroke-linecap="round" fill="none" />
                <path d="M 88 60 Q 93 57 98 60" stroke="#4E342E" stroke-width="1.6" stroke-linecap="round" fill="none" />

                <!-- Nose -->
                <path d="M 79 70 Q 80 72 81 71" stroke="#D79E7E" stroke-width="1.5" stroke-linecap="round" fill="none" />

                <!-- Joyful Open Smile -->
                <path d="M 73 77 Q 80 84 87 77" fill="#C62828" stroke="#8E1B1B" stroke-width="1" />
                <path d="M 75 78 Q 80 80 85 78" fill="#FFFFFF" />
            </g>
        </svg>
    </div>
</div>

