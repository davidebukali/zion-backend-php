<!-- Header / Sticky Navigation -->
<header
    class="fixed top-0 w-full z-50 bg-surface/85 backdrop-blur-xl border-b border-outline-variant/15 shadow-[0_1px_8px_rgba(0,0,0,0.12)]">
    <div class="h-20 max-w-[1200px] mx-auto px-gutter flex items-center justify-between">
        <!-- Brand Logo & Name -->
        <a href="{{ route('home') }}" class="flex items-center gap-space-sm group">
            <img src="{{ asset('images/landing/logo.webp') }}" alt="Tsiyon Logo" width="36" height="36"
                class="h-9 w-9 object-contain rounded-lg shadow-sm transition-transform duration-200 group-hover:scale-105">
            <span class="text-xl font-extrabold text-on-surface tracking-tight">Tsiyon</span>
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center gap-gutter">
            <a href="{{ route('home') }}"
                class="font-medium text-sm text-on-surface-variant hover:text-primary transition-colors duration-200">Home</a>
            <a href="{{ url('/#features') }}"
                class="font-medium text-sm text-on-surface-variant hover:text-primary transition-colors duration-200">Features</a>
            <a href="{{ url('/#waitlist') }}"
                class="font-medium text-sm text-on-surface-variant hover:text-primary transition-colors duration-200">Early
                Beta</a>
        </nav>

        <!-- Actions: Theme Toggle & Join CTA -->
        <div class="flex items-center gap-space-md">
            <!-- Theme Switcher Pill (Alpine.js) -->
            <div x-data="themeSwitcher">
                <button type="button" @click="toggle()"
                    class="h-9 px-1 rounded-full bg-surface-container-highest flex items-center gap-0.5 border border-outline-variant/20 transition-colors duration-200"
                    aria-label="Toggle Light / Dark Theme">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center transition-all duration-200"
                        :class="isDark ? 'bg-primary-container text-on-primary-container shadow-sm' : 'text-on-surface-variant opacity-60'">
                        <span class="material-symbols-outlined text-[17px]">dark_mode</span>
                    </div>
                    <div class="w-7 h-7 rounded-full flex items-center justify-center transition-all duration-200"
                        :class="!isDark ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant opacity-60'">
                        <span class="material-symbols-outlined text-[17px]">light_mode</span>
                    </div>
                </button>
            </div>

            <!-- Join Waitlist Button -->
            <a href="{{ url('/#waitlist') }}"
                class="hidden sm:inline-flex items-center justify-center bg-primary-container text-on-primary-container text-xs font-bold uppercase tracking-wider px-5 py-2.5 rounded-full transition-all duration-200 glow-primary glow-primary-hover active:scale-95">
                Reserve Your Spot
            </a>

            <!-- Mobile Hamburger Button -->
            <div x-data="mobileNav" class="md:hidden">
                <button type="button" @click="toggle()"
                    class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface border border-outline-variant/20 hover:bg-surface-container-highest transition-colors"
                    aria-label="Open mobile navigation">
                    <span class="material-symbols-outlined text-[22px]" x-show="!open">menu</span>
                    <span class="material-symbols-outlined text-[22px]" x-show="open" x-cloak>close</span>
                </button>

                <!-- Mobile Drawer Backdrop -->
                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" @click="close()"
                    class="fixed inset-0 top-20 bg-black/60 backdrop-blur-sm z-40" x-cloak></div>

                <!-- Mobile Sliding Drawer -->
                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="-translate-y-4 opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="-translate-y-4 opacity-0"
                    class="fixed top-20 inset-x-0 bg-surface-container-high border-b border-outline-variant/20 p-6 flex flex-col gap-4 z-50 shadow-2xl"
                    x-cloak>
                    <a href="{{ route('home') }}" @click="close()"
                        class="text-base font-medium text-on-surface py-2 border-b border-outline-variant/10">Home</a>
                    <a href="{{ url('/#features') }}" @click="close()"
                        class="text-base font-medium text-on-surface py-2 border-b border-outline-variant/10">Features</a>
                    <a href="{{ url('/#waitlist') }}" @click="close()"
                        class="w-full text-center bg-primary-container text-on-primary-container font-bold text-sm uppercase tracking-wider py-3 rounded-full mt-2 glow-primary">
                        Reserve Your Beta Spot
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>