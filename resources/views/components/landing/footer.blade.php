<!-- Global Footer -->
<footer class="w-full bg-surface-container-low border-t border-outline-variant/15 mt-auto">
    <div
        class="max-w-[1200px] w-full mx-auto px-gutter py-space-lg flex flex-col md:flex-row items-center justify-between gap-space-md">
        <!-- Brand & Tagline -->
        <div class="flex items-center gap-space-xs text-center sm:text-left">
            <img src="{{ asset('images/landing/logo.png') }}" alt="Tsiyon Logo"
                class="h-7 w-auto object-contain rounded-md">
            <span class="text-lg font-bold text-on-surface tracking-tight">Tsiyon</span>
            <span class="text-outline-variant hidden lg:inline">•</span>
            <span class="text-xs text-on-surface-variant hidden lg:inline">A social home built for what matters most.</span>
        </div>

        <!-- Navigation Links -->
        <nav class="flex flex-wrap items-center justify-center gap-space-md text-xs">
            <a href="#" class="text-on-surface-variant hover:text-primary transition-colors duration-200">Terms of
                Service</a>
            <a href="#" class="text-on-surface-variant hover:text-primary transition-colors duration-200">Privacy
                Policy</a>
            <a href="#" class="text-on-surface-variant hover:text-primary transition-colors duration-200">Contact
                Support</a>
        </nav>

        <!-- Copyright -->
        <div class="text-xs text-on-surface-variant/70 text-center md:text-right shrink-0">
            <span>&copy; {{ date('Y') }} Tsiyon App. All rights reserved.</span>
        </div>
    </div>
</footer>