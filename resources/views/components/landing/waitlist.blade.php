<!-- WAITLIST & COMMUNITY SECTION: Pre-Footer CTA -->
<section class="max-w-[1200px] mx-auto px-gutter pb-space-xl lg:pb-32" id="waitlist">
    <div
        class="w-full rounded-3xl bg-surface-container border border-outline-variant/15 p-space-md sm:p-space-lg lg:p-16 text-center flex flex-col items-center gap-space-lg relative overflow-hidden shadow-[0_20px_50px_-12px_rgba(198,40,40,0.22)]">
        <!-- Subtle Ambient Red Glow Layer -->
        <div
            class="absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary-container/20 rounded-full blur-[100px] pointer-events-none">
        </div>

        <!-- Heart Emblem Badge -->
        <div
            class="w-14 h-14 rounded-2xl bg-primary-container text-on-primary-container flex items-center justify-center glow-primary z-10">
            <span class="material-symbols-outlined text-[32px] icon-fill">favorite</span>
        </div>

        <!-- Section Copy -->
        <div class="max-w-2xl space-y-space-xs z-10">
            <h2 class="text-3xl sm:text-4xl lg:text-[40px] font-extrabold text-on-surface tracking-tight leading-tight">
                Be a Founding Believer in the Tsiyon Community
            </h2>
            <p class="text-sm sm:text-base text-on-surface-variant leading-relaxed">
                Help us build a cleaner social home for the Body of Christ. Reserve your early beta spot on iOS &amp; Android.
            </p>
        </div>

        <!-- Large Interactive Email Input Bar (Alpine.js) -->
        <div class="w-full max-w-xl z-10 flex flex-col gap-2" x-data="waitlistForm('footer_cta')">
            <form @submit.prevent="submit('footer_cta')"
                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-2 rounded-2xl sm:rounded-full bg-surface-container-highest border border-outline-variant/20 shadow-inner">
                <div class="flex items-center pl-4 pr-2 py-2 flex-1 gap-2.5">
                    <span class="material-symbols-outlined text-outline text-[22px]">mark_email_read</span>
                    <input x-model="email"
                        class="bg-transparent text-on-surface placeholder:text-on-surface-variant/60 text-sm sm:text-base focus:outline-none w-full"
                        placeholder="Your primary email address" required type="email"
                        :disabled="status === 'submitting'" />
                </div>
                <button
                    class="px-8 py-3.5 rounded-full bg-primary-container text-on-primary-container text-xs uppercase tracking-wider font-bold glow-primary glow-primary-hover active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 shrink-0 disabled:opacity-50"
                    type="submit" :disabled="status === 'submitting'">
                    <span x-show="status !== 'submitting'">Reserve Your Beta Spot</span>
                    <span x-show="status === 'submitting'" x-cloak class="flex items-center gap-2">
                        <span
                            class="animate-spin h-3.5 w-3.5 border-2 border-on-primary-container border-t-transparent rounded-full"></span>
                        Recording...
                    </span>
                    <span class="material-symbols-outlined text-[18px]" x-show="status !== 'submitting'">arrow_forward</span>
                </button>
            </form>

            <!-- Success & Validation Messages -->
            <div x-show="status === 'success'" x-transition
                class="p-4 rounded-2xl bg-secondary-container/40 border border-primary/20 text-xs sm:text-sm text-primary font-bold flex items-center justify-center gap-2 shadow-sm"
                x-cloak>
                <span class="material-symbols-outlined text-[20px] icon-fill">check_circle</span>
                <span x-text="message || 'Your beta invitation request has been recorded. God bless you!'"></span>
            </div>

            <!-- Already Registered Notification -->
            <div x-show="status === 'already_registered'" x-transition
                class="p-4 rounded-2xl bg-surface-container-highest border border-primary/20 text-xs sm:text-sm text-on-surface font-semibold flex items-center justify-center gap-2 shadow-sm"
                x-cloak>
                <span class="material-symbols-outlined text-[20px] text-primary">mark_email_read</span>
                <span x-text="message || 'You\'re already on the waitlist! We\'ll reach out as soon as your invite is ready.'"></span>
            </div>

            <div x-show="errorMessage" x-transition class="p-2 text-xs text-primary font-medium text-center" x-text="errorMessage"
                x-cloak></div>
        </div>

        <!-- Trust Badges Strip -->
        <div
            class="flex flex-wrap items-center justify-center gap-x-space-md gap-y-2 text-on-surface-variant text-xs font-semibold uppercase tracking-wider z-10 pt-2">
            <span class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px] text-primary">check_circle</span>
                Instant Invitation Access
            </span>
            <span class="text-outline-variant hidden sm:inline">•</span>
            <span class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px] text-primary">phone_iphone</span>
                Early Access on iOS &amp; Android
            </span>
            <span class="text-outline-variant hidden sm:inline">•</span>
            <span class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px] text-primary">redeem</span>
                100% Free Always
            </span>
        </div>
    </div>
</section>