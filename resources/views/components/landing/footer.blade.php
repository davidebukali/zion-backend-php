<!-- Global Footer -->
<footer class="w-full bg-surface-container-low border-t border-outline-variant/15 mt-auto">
    <div class="max-w-[1200px] mx-auto px-gutter py-space-xl flex flex-col md:flex-row items-center justify-between gap-space-lg">
        <div class="flex flex-col sm:flex-row items-center gap-space-md text-center sm:text-left">
            <div class="flex items-center gap-space-xs">
                <img src="{{ asset('images/landing/logo.png') }}" alt="Tsiyon Logo" class="h-7 w-auto object-contain rounded-md">
                <span class="text-lg font-bold text-on-surface tracking-tight">Tsiyon</span>
            </div>
            <span class="text-outline-variant hidden sm:inline">•</span>
            <span class="text-xs text-on-surface-variant">A social home built for what matters most.</span>
        </div>

        <nav class="flex flex-wrap items-center justify-center gap-space-md text-xs">
            <a href="#" class="text-on-surface-variant hover:text-primary transition-colors duration-200">Terms of Service</a>
            <a href="#" class="text-on-surface-variant hover:text-primary transition-colors duration-200">Privacy Policy</a>
            <a href="#" class="text-on-surface-variant hover:text-primary transition-colors duration-200">Contact Support</a>
        </nav>

        <div class="w-full pt-4 border-t border-outline-variant/15 flex flex-col sm:flex-row items-center justify-between text-center gap-2 text-xs text-on-surface-variant/70">
            <span>&copy; {{ date('Y') }} Tsiyon Social App. All rights reserved.</span>
            <span>Built with Material Design 3 guidelines.</span>
        </div>
    </div>
</footer>
