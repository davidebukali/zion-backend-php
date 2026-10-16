<!-- HERO SECTION: Tsiyon Landing Page -->
<div class="relative w-full overflow-hidden" id="hero">
    <!-- Ambient Red Background Glows -->
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[720px] h-[480px] bg-primary-container/15 rounded-full blur-[140px] pointer-events-none -z-10"></div>
    <div class="absolute top-[480px] -right-24 w-[420px] h-[420px] bg-secondary-container/20 rounded-full blur-[120px] pointer-events-none -z-10"></div>

    <section class="max-w-[1200px] mx-auto px-gutter py-space-xl lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-center">
            <!-- Left Column: Copy & Actions -->
            <div class="lg:col-span-7 flex flex-col items-start gap-space-lg">
                <!-- Early Beta Pill Indicator -->
                <div class="inline-flex items-center gap-space-xs px-3.5 py-1.5 rounded-full bg-surface-container-high border border-outline-variant/20 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                    </span>
                    <span class="text-xs text-primary font-bold tracking-wider uppercase">Early Beta Access</span>
                    <span class="text-outline-variant text-xs mx-0.5">•</span>
                    <span class="text-xs text-on-surface-variant font-medium">Limited Slots Available</span>
                </div>

                <!-- Main Headline -->
                <div class="space-y-space-sm max-w-xl">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl text-on-surface font-extrabold tracking-tight leading-tight">
                        A Social Home Built for <span class="bg-gradient-to-r from-primary via-primary-fixed-dim to-tertiary bg-clip-text text-transparent">What Matters Most</span>
                    </h1>
                    <p class="text-base sm:text-lg text-on-surface-variant font-normal leading-relaxed">
                        A place to spend time in the Word of God with Christ-centered, family-friendly content that builds up your life.
                    </p>
                </div>

                <!-- Scripture Quote Card (Material 3 Surface Layer 2) -->
                <div class="w-full max-w-xl p-space-md sm:p-space-lg rounded-2xl bg-surface-container-high border border-outline-variant/15 glow-card glow-card-hover flex items-start gap-space-md transition-all duration-300">
                    <div class="w-10 h-10 rounded-full bg-secondary-container/40 flex items-center justify-center shrink-0 text-primary">
                        <span class="material-symbols-outlined text-[20px] icon-fill">auto_stories</span>
                    </div>
                    <div class="flex-1 space-y-1">
                        <p class="text-sm sm:text-base text-on-surface italic font-medium leading-snug">
                            “Above all else, guard your heart, for everything you do flows from it.”
                        </p>
                        <div class="flex items-center gap-2 pt-0.5">
                            <span class="text-xs text-primary font-bold tracking-wide uppercase">— Proverbs 4:23</span>
                            <span class="w-1 h-1 rounded-full bg-outline"></span>
                            <span class="text-xs text-on-surface-variant">Daily Scripture</span>
                        </div>
                    </div>
                </div>

                <!-- Interactive Waitlist Module (Alpine.js) -->
                <div class="w-full max-w-xl flex flex-col gap-space-xs" x-data="waitlistForm">
                    <form @submit.prevent="submit()" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-1.5 rounded-2xl sm:rounded-full bg-surface-container-highest border border-outline-variant/20 shadow-inner">
                        <div class="flex items-center pl-3.5 pr-2 py-2 flex-1 gap-2">
                            <span class="material-symbols-outlined text-outline text-[20px]">mail</span>
                            <input 
                                x-model="email" 
                                class="bg-transparent text-on-surface placeholder:text-on-surface-variant/60 text-sm focus:outline-none w-full" 
                                placeholder="Enter your email address" 
                                required 
                                type="email"
                                :disabled="status === 'submitting'"
                            />
                        </div>
                        <button 
                            class="px-6 py-3 rounded-full bg-primary-container text-on-primary-container text-xs uppercase tracking-wider font-bold glow-primary glow-primary-hover active:scale-95 transition-all duration-200 flex items-center justify-center gap-1.5 shrink-0 disabled:opacity-50" 
                            type="submit"
                            :disabled="status === 'submitting'"
                        >
                            <span x-show="status !== 'submitting'">Get Early Beta Access</span>
                            <span x-show="status === 'submitting'" x-cloak class="flex items-center gap-2">
                                <span class="animate-spin h-3.5 w-3.5 border-2 border-on-primary-container border-t-transparent rounded-full"></span>
                                Joining...
                            </span>
                            <span class="material-symbols-outlined text-[18px]" x-show="status !== 'submitting'">arrow_forward</span>
                        </button>
                    </form>

                    <!-- Feedback Notifications -->
                    <div class="flex items-center justify-between px-space-xs pt-1">
                        <p class="text-xs text-on-surface-variant flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[15px] text-primary icon-fill">verified_user</span>
                            <span>Be the first to know when we launch on iOS &amp; Android. No spam, ever.</span>
                        </p>
                    </div>

                    <div 
                        x-show="status === 'success'" 
                        x-transition 
                        class="p-3 rounded-xl bg-secondary-container/40 border border-primary/20 text-xs text-primary font-semibold flex items-center gap-2 mt-1"
                        x-cloak
                    >
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                        <span>Your early beta slot is reserved! God bless you.</span>
                    </div>

                    <div 
                        x-show="errorMessage" 
                        x-transition 
                        class="p-2 text-xs text-primary font-medium" 
                        x-text="errorMessage"
                        x-cloak
                    ></div>
                </div>

                <!-- Trust Badges Row -->
                <div class="pt-space-sm flex flex-wrap items-center gap-space-lg text-on-surface-variant">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary">format_image_left</span>
                        <span class="text-xs font-medium">Curated Christian Spaces</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary">family_restroom</span>
                        <span class="text-xs font-medium">Family-First Safety</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Interactive Smartphone Viewport -->
            <div class="lg:col-span-5 flex justify-center relative mt-8 lg:mt-0" id="preview">
                <!-- Ambient Red Rim Halo -->
                <div class="absolute inset-4 bg-primary-container/20 rounded-[48px] blur-2xl -z-10 transform scale-95"></div>

                <!-- Smartphone Chassis -->
                <div class="w-full max-w-[340px] rounded-[44px] bg-surface-container-lowest p-3 border border-outline-variant/30 shadow-[0_24px_50px_-10px_rgba(0,0,0,0.6),0_0_30px_0_rgba(198,40,40,0.2)]">
                    <!-- Screen Surface -->
                    <div class="w-full rounded-[36px] bg-surface-container-low overflow-hidden flex flex-col h-[640px] relative border border-outline-variant/15">
                        <!-- Dynamic Notch & Status Area -->
                        <div class="w-full px-6 pt-3 pb-1 flex justify-between items-center text-on-surface-variant text-[11px] font-semibold tracking-wide">
                            <span>9:41</span>
                            <div class="w-20 h-4 bg-surface-container-highest rounded-full flex items-center justify-center">
                                <span class="w-2.5 h-2.5 rounded-full bg-surface-container-low"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">signal_cellular_alt</span>
                                <span class="material-symbols-outlined text-[13px]">wifi</span>
                                <span class="material-symbols-outlined text-[14px]">battery_full</span>
                            </div>
                        </div>

                        <!-- Tsiyon App Bar -->
                        <div class="px-4 py-2.5 flex items-center justify-between bg-surface-container-low/90 backdrop-blur-md sticky top-0 z-20 border-b border-outline-variant/10">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-md bg-primary-container flex items-center justify-center text-on-primary-container">
                                    <span class="material-symbols-outlined text-[16px] icon-fill">local_fire_department</span>
                                </div>
                                <span class="text-[17px] font-extrabold text-on-surface tracking-tight">Tsiyon</span>
                            </div>
                            <div class="flex items-center gap-1 text-on-surface-variant">
                                <button class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[19px]">search</span>
                                </button>
                                <button class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center relative">
                                    <span class="material-symbols-outlined text-[19px]">notifications</span>
                                    <span class="w-2 h-2 rounded-full bg-primary absolute top-1.5 right-1.5"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Internal Scrollable Feed -->
                        <div class="flex-1 overflow-y-auto px-3 py-2 space-y-3 pb-16">
                            <!-- Daily Scripture Highlight Ribbon -->
                            <div class="rounded-xl p-3 bg-gradient-to-br from-secondary-container via-primary-container to-surface-container-high text-white shadow-sm flex flex-col gap-1 relative overflow-hidden">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] uppercase font-bold tracking-widest text-primary-fixed">Daily Scripture</span>
                                    <span class="material-symbols-outlined text-[14px] text-primary-fixed">bookmark</span>
                                </div>
                                <p class="text-[12px] font-semibold leading-tight text-white">
                                    “The Lord is my shepherd; I shall not want.”
                                </p>
                                <span class="text-[10px] text-secondary-fixed opacity-90 font-medium">Psalm 23:1 • Fellowship Meditation</span>
                            </div>

                            <!-- Feed Card 1: Reflection & Verse -->
                            <div class="rounded-xl bg-surface-container p-3 space-y-2.5 border border-outline-variant/15 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-xs">
                                            SM
                                        </div>
                                        <div>
                                            <div class="text-[12px] font-bold text-on-surface leading-none">Sarah Miller</div>
                                            <div class="text-[10px] text-on-surface-variant mt-0.5">2h ago • Fellowship Group</div>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full bg-secondary-container/30 text-secondary text-[10px] font-semibold">Verse Study</span>
                                </div>
                                <p class="text-[12px] text-on-surface leading-snug">
                                    Reflecting on morning prayer with the women's fellowship group. What a blessing to walk in peace without the noise of typical feeds!
                                </p>
                                <div class="rounded-lg bg-surface-container-high p-2.5 flex items-center gap-2 border border-outline-variant/10">
                                    <span class="material-symbols-outlined text-primary text-[18px]">format_quote</span>
                                    <span class="text-[11px] text-on-surface italic line-clamp-1">“Peace I leave with you; my peace I give you.”</span>
                                </div>
                                <!-- Engagement Footer -->
                                <div class="flex items-center justify-between pt-1 text-on-surface-variant text-[11px]">
                                    <div class="flex items-center gap-3">
                                        <button class="flex items-center gap-1 text-primary hover:opacity-80">
                                            <span class="material-symbols-outlined text-[15px] icon-fill">favorite</span>
                                            <span>248</span>
                                        </button>
                                        <button class="flex items-center gap-1 hover:text-on-surface">
                                            <span class="material-symbols-outlined text-[15px]">chat_bubble</span>
                                            <span>34</span>
                                        </button>
                                    </div>
                                    <button class="flex items-center gap-1 hover:text-on-surface">
                                        <span class="material-symbols-outlined text-[15px]">share</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Feed Card 2: Community Worship Audio -->
                            <div class="rounded-xl bg-surface-container p-3 space-y-2 border border-outline-variant/15 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container text-[11px] font-bold">
                                        TG
                                    </div>
                                    <div>
                                        <div class="text-[12px] font-bold text-on-surface leading-none">Tsiyon Global Worship</div>
                                        <div class="text-[10px] text-on-surface-variant mt-0.5">Live Audio Session • 412 listening</div>
                                    </div>
                                </div>
                                <!-- Waveform player container -->
                                <div class="rounded-lg bg-surface-container-high p-2 flex items-center gap-2.5 border border-outline-variant/10">
                                    <button class="w-7 h-7 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[14px] icon-fill">play_arrow</span>
                                    </button>
                                    <div class="flex-1 flex items-center gap-0.5 h-6">
                                        <span class="w-1 h-2 rounded-full bg-primary"></span>
                                        <span class="w-1 h-4 rounded-full bg-primary"></span>
                                        <span class="w-1 h-5 rounded-full bg-primary"></span>
                                        <span class="w-1 h-3 rounded-full bg-outline"></span>
                                        <span class="w-1 h-6 rounded-full bg-primary"></span>
                                        <span class="w-1 h-4 rounded-full bg-primary"></span>
                                        <span class="w-1 h-2 rounded-full bg-outline"></span>
                                        <span class="w-1 h-5 rounded-full bg-primary"></span>
                                        <span class="w-1 h-3 rounded-full bg-primary"></span>
                                        <span class="w-1 h-1.5 rounded-full bg-outline"></span>
                                    </div>
                                    <span class="text-[10px] font-mono text-on-surface-variant">04:12</span>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Bottom Navigation -->
                        <div class="absolute bottom-2 inset-x-2 rounded-full bg-surface-container-highest/95 backdrop-blur-md px-3 py-2 flex justify-around items-center text-on-surface-variant border border-outline-variant/20 shadow-lg z-30">
                            <button class="flex flex-col items-center text-primary">
                                <span class="material-symbols-outlined text-[18px] icon-fill">home</span>
                                <span class="text-[9px] font-semibold">Feed</span>
                            </button>
                            <button class="flex flex-col items-center hover:text-on-surface">
                                <span class="material-symbols-outlined text-[18px]">explore</span>
                                <span class="text-[9px]">Discover</span>
                            </button>
                            <button class="flex flex-col items-center hover:text-on-surface">
                                <span class="material-symbols-outlined text-[18px]">volunteer_activism</span>
                                <span class="text-[9px]">Prayer</span>
                            </button>
                            <button class="flex flex-col items-center hover:text-on-surface">
                                <span class="material-symbols-outlined text-[18px]">chat</span>
                                <span class="text-[9px]">Messages</span>
                            </button>
                            <button class="flex flex-col items-center hover:text-on-surface">
                                <span class="material-symbols-outlined text-[18px]">account_circle</span>
                                <span class="text-[9px]">Profile</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
