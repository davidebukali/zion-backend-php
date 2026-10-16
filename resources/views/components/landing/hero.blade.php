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
                <div class="w-full max-w-[340px] rounded-[48px] bg-[#1a1414] p-3 border border-outline-variant/30 shadow-[0_24px_50px_-10px_rgba(0,0,0,0.7),0_0_35px_0_rgba(198,40,40,0.22)]">
                    <!-- Screen Surface -->
                    <div class="w-full rounded-[40px] bg-[#130F0F] overflow-hidden flex flex-col h-[650px] relative border border-outline-variant/20 select-none">
                        <!-- Dynamic Island & Status Area -->
                        <div class="w-full px-6 pt-3 pb-1 flex justify-between items-center text-on-surface-variant text-[12px] font-semibold tracking-tight z-30">
                            <span class="text-white/90">9:41</span>
                            <!-- Dynamic Island Pill -->
                            <div class="w-24 h-5 bg-black rounded-full flex items-center justify-end pr-2 gap-1.5 border border-white/5 shadow-inner">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#1c1c1e] border border-white/10"></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-white/90">
                                <span class="material-symbols-outlined text-[14px]">signal_cellular_alt</span>
                                <span class="material-symbols-outlined text-[14px]">wifi</span>
                                <span class="material-symbols-outlined text-[15px] icon-fill">battery_full</span>
                            </div>
                        </div>

                        <!-- Tsiyon App Bar -->
                        <div class="px-4 py-2.5 flex items-center justify-between bg-[#130F0F]/90 backdrop-blur-md sticky top-0 z-20">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-[#C62828] flex items-center justify-center text-white shadow-sm">
                                    <span class="material-symbols-outlined text-[18px] icon-fill">local_fire_department</span>
                                </div>
                                <span class="text-[19px] font-black text-white tracking-tight">Tsiyon</span>
                            </div>
                            <div class="flex items-center gap-2 text-neutral-300">
                                <button type="button" class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">search</span>
                                </button>
                                <button type="button" class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center relative transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">notifications</span>
                                    <span class="w-2 h-2 rounded-full bg-[#E53935] absolute top-1.5 right-1.5 ring-2 ring-[#130F0F]"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Internal Scrollable Feed -->
                        <div class="flex-1 overflow-y-auto px-3 py-2 space-y-3 pb-20 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            <!-- Feed Card 1: Sarah Miller (Verse Study) -->
                            <div 
                                x-data="{ liked: true, count: 248, bookmarked: false }"
                                class="rounded-[22px] bg-[#1C1717] p-3.5 space-y-3 border border-white/5 shadow-sm"
                            >
                                <!-- Author Row -->
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <!-- Lime Green Avatar -->
                                        <div class="w-9 h-9 rounded-full bg-[#84E42B] text-black flex items-center justify-center font-extrabold text-[13px] tracking-tight shrink-0 shadow-sm">
                                            SM
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1 leading-tight">
                                                <span class="text-[13px] font-bold text-white">Sarah Miller</span>
                                                <span class="material-symbols-outlined text-[14px] text-[#FFB4AC] icon-fill">verified</span>
                                            </div>
                                            <div class="text-[11px] text-neutral-400">@sarahm • 2h</div>
                                        </div>
                                    </div>
                                    <button type="button" class="text-neutral-400 hover:text-white p-1">
                                        <span class="material-symbols-outlined text-[18px]">more_horiz</span>
                                    </button>
                                </div>

                                <!-- Post Text -->
                                <p class="text-[12.5px] text-neutral-200 leading-relaxed">
                                    Reflecting on morning prayer with the women's fellowship group. What a blessing to walk in peace without the noise of typical feeds! 🙌
                                </p>

                                <!-- Quote Box with Quotation Mark & Italicized Verse -->
                                <div class="rounded-full bg-[#151111] px-3.5 py-2 flex items-center gap-2 border border-white/10">
                                    <span class="text-[#FFB4AC] font-serif font-black text-sm leading-none select-none">””</span>
                                    <span class="text-[11.5px] text-neutral-300 italic truncate">“Peace I leave with you; my peace I give...</span>
                                </div>

                                <!-- Engagement Footer -->
                                <div class="flex items-center justify-between pt-1 text-neutral-400 text-[11px]">
                                    <div class="flex items-center gap-4">
                                        <!-- Heart with Alpine Toggle -->
                                        <button 
                                            type="button" 
                                            @click="liked = !liked; count += liked ? 1 : -1"
                                            class="flex items-center gap-1.5 transition-colors"
                                            :class="liked ? 'text-[#FF8A80]' : 'hover:text-white'"
                                        >
                                            <span class="material-symbols-outlined text-[16px]" :class="{ 'icon-fill': liked }">favorite</span>
                                            <span class="font-medium" x-text="count">248</span>
                                        </button>

                                        <!-- Comments -->
                                        <div class="flex items-center gap-1.5 hover:text-white transition-colors cursor-pointer">
                                            <span class="material-symbols-outlined text-[16px]">chat_bubble</span>
                                            <span class="font-medium">34</span>
                                        </div>

                                        <!-- Share -->
                                        <button type="button" class="hover:text-white transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">share</span>
                                        </button>
                                    </div>

                                    <!-- Bookmark -->
                                    <button 
                                        type="button" 
                                        @click="bookmarked = !bookmarked" 
                                        class="hover:text-white transition-colors"
                                        :class="bookmarked ? 'text-[#FFB4AC]' : ''"
                                    >
                                        <span class="material-symbols-outlined text-[16px]" :class="{ 'icon-fill': bookmarked }">bookmark</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Feed Card 2: Gracious (Video Post) -->
                            <div 
                                x-data="{ liked: false, count: 6700 }"
                                class="rounded-[22px] bg-[#1C1717] p-3.5 space-y-3 border border-white/5 shadow-sm"
                            >
                                <!-- Author Row -->
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <!-- Lime Green Avatar -->
                                        <div class="w-9 h-9 rounded-full bg-[#84E42B] text-black flex items-center justify-center font-extrabold text-[14px] shrink-0 shadow-sm">
                                            G
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1 leading-tight">
                                                <span class="text-[13px] font-bold text-white">Gracious</span>
                                                <span class="material-symbols-outlined text-[14px] text-[#FFB4AC] icon-fill">verified</span>
                                            </div>
                                            <div class="text-[11px] text-neutral-400">@gracious • 1h</div>
                                        </div>
                                    </div>
                                    <button type="button" class="text-neutral-400 hover:text-white p-1">
                                        <span class="material-symbols-outlined text-[18px]">more_horiz</span>
                                    </button>
                                </div>

                                <!-- Post Text -->
                                <p class="text-[12.5px] text-neutral-200 leading-relaxed">
                                    Still overwhelmed with joy from all the faith testimonies shared at last week's fellowship! God is moving in amazing ways. 🙏✨
                                </p>

                                <!-- Video Player Mockup Surface -->
                                <div class="rounded-2xl bg-[#5C1D1B] aspect-[16/9] flex items-center justify-center relative overflow-hidden group cursor-pointer border border-white/10 shadow-inner">
                                    <!-- Subtle ambient backdrop light -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                                    <!-- Circular Play Button -->
                                    <div class="w-12 h-12 rounded-full bg-black/60 backdrop-blur-sm flex items-center justify-center text-white shadow-lg transition-transform duration-200 group-hover:scale-110">
                                        <span class="material-symbols-outlined text-[26px] icon-fill ml-0.5">play_arrow</span>
                                    </div>
                                </div>

                                <!-- Engagement Footer -->
                                <div class="flex items-center justify-between pt-1 text-neutral-400 text-[11px]">
                                    <div class="flex items-center gap-4">
                                        <!-- Heart with Alpine Toggle -->
                                        <button 
                                            type="button" 
                                            @click="liked = !liked; count += liked ? 1 : -1"
                                            class="flex items-center gap-1.5 transition-colors"
                                            :class="liked ? 'text-[#FF8A80]' : 'hover:text-white'"
                                        >
                                            <span class="material-symbols-outlined text-[16px]" :class="{ 'icon-fill': liked }">favorite</span>
                                            <span class="font-medium" x-text="liked ? '6.7k' : '6.7k'">6.7k</span>
                                        </button>

                                        <!-- Comments -->
                                        <div class="flex items-center gap-1.5 hover:text-white transition-colors cursor-pointer">
                                            <span class="material-symbols-outlined text-[16px]">chat_bubble</span>
                                            <span class="font-medium">401</span>
                                        </div>

                                        <!-- Share -->
                                        <button type="button" class="hover:text-white transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">share</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Bottom Navigation Dock -->
                        <div class="absolute bottom-3 inset-x-3 rounded-full bg-[#1E1919]/90 backdrop-blur-md px-3 py-2 flex items-center justify-between text-neutral-400 border border-white/10 shadow-[0_8px_24px_rgba(0,0,0,0.6)] z-30">
                            <!-- 1. Home (Permanently Active) -->
                            <div class="flex flex-col items-center flex-1 text-white">
                                <span class="material-symbols-outlined text-[20px] icon-fill">home</span>
                                <span class="text-[9.5px] font-semibold tracking-tight">Home</span>
                            </div>

                            <!-- 2. Discover -->
                            <div class="flex flex-col items-center flex-1 hover:text-white transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[20px]">search</span>
                                <span class="text-[9.5px] font-medium tracking-tight">Discover</span>
                            </div>

                            <!-- 3. Center Red Floating Action Button (+) -->
                            <div class="flex items-center justify-center px-1">
                                <button 
                                    type="button" 
                                    class="w-11 h-11 -my-2.5 rounded-full bg-gradient-to-tr from-[#C62828] to-[#EF5350] text-white flex items-center justify-center shadow-[0_4px_14px_rgba(198,40,40,0.6)] hover:scale-105 active:scale-95 transition-transform"
                                    aria-label="Create New Post"
                                >
                                    <span class="material-symbols-outlined text-[24px] font-bold">add</span>
                                </button>
                            </div>

                            <!-- 4. Alerts -->
                            <div class="flex flex-col items-center flex-1 hover:text-white transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[20px]">notifications</span>
                                <span class="text-[9.5px] font-medium tracking-tight">Alerts</span>
                            </div>

                            <!-- 5. Me -->
                            <div class="flex flex-col items-center flex-1 hover:text-white transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[20px]">person</span>
                                <span class="text-[9.5px] font-medium tracking-tight">Me</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
