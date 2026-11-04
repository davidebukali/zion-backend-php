<!-- WAITLIST CONFIRMATION MODAL -->
<div x-data="waitlistConfirmationModal()" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="confirmation-modal-title" role="dialog" aria-modal="true" @keydown.escape.window="close()">

    <!-- Backdrop with Blur Effect -->
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/70 backdrop-blur-md transition-opacity" @click="close()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <!-- Modal Card Container -->
        <div x-show="open" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-3xl bg-surface-container-high border border-outline-variant/20 p-6 sm:p-8 text-left shadow-2xl transition-all w-full max-w-lg glow-card">

            <!-- Ambient Glow Effect inside Modal -->
            <div
                class="absolute -top-24 -right-24 w-60 h-60 bg-primary-container/20 rounded-full blur-[80px] pointer-events-none -z-10">
            </div>
            <div
                class="absolute -bottom-24 -left-24 w-60 h-60 bg-secondary-container/20 rounded-full blur-[80px] pointer-events-none -z-10">
            </div>

            <!-- Close Button -->
            <button @click="close()" type="button"
                class="absolute top-4 right-4 sm:top-6 sm:right-6 text-on-surface-variant hover:text-on-surface p-2 rounded-full hover:bg-surface-container-highest transition-colors"
                aria-label="Close modal">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>

            <!-- Header Badge & Embellishment -->
            <div class="flex flex-col items-center text-center gap-4 pt-2">
                <div
                    class="w-16 h-16 rounded-2xl bg-primary-container text-on-primary-container flex items-center justify-center shadow-lg glow-primary relative">
                    <span class="material-symbols-outlined text-[32px] icon-fill"
                        x-show="status !== 'already_registered'">favorite</span>
                    <span class="material-symbols-outlined text-[32px]" x-show="status === 'already_registered'"
                        x-cloak>mark_email_read</span>
                </div>

                <div class="space-y-2">
                    <h3 id="confirmation-modal-title"
                        class="text-2xl sm:text-3xl font-extrabold text-on-surface tracking-tight"
                        x-text="status === 'already_registered' ? 'You\'re on the Founding List!' : 'You\'re on the Founding List!'">
                    </h3>
                    <p class="text-sm sm:text-base text-on-surface-variant leading-relaxed max-w-md"
                        x-text="status === 'already_registered'
                                ? 'Good news! Your email is already recorded for early access.'
                                : 'Your early beta spot on iOS & Android has been reserved. Check your inbox for confirmation. God bless you!'">
                    </p>
                </div>
            </div>

            <!-- Sharing Callout Box (The Confirmation Loop) -->
            <div
                class="mt-6 p-4 sm:p-5 rounded-2xl bg-surface-container-highest border border-outline-variant/25 space-y-3.5">
                <div class="flex items-start gap-2.5">
                    <span
                        class="material-symbols-outlined text-primary text-[20px] shrink-0 mt-0.5">rocket_launch</span>
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-on-surface uppercase tracking-wider">
                            Help us test early
                        </h4>
                        <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">
                            Share Tsiyon with your friends, church community, and prayer partners to help us test early
                            and skip the beta queue.
                        </p>
                    </div>
                </div>

                <!-- Message Preview Box -->
                <div
                    class="p-3 sm:p-3.5 rounded-xl bg-surface-container border border-outline-variant/15 text-xs text-on-surface-variant flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-[18px] text-primary shrink-0 mt-0.5">format_quote</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] sm:text-xs text-on-surface italic leading-relaxed select-all">
                            "<span x-text="shareText"></span> <span class="text-primary font-medium"
                                x-text="shareUrl"></span>"
                        </p>
                    </div>
                    <button @click="copyForInstagram()" type="button"
                        class="px-2.5 py-1 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-[11px] font-bold text-primary border border-outline-variant/20 shrink-0 transition-colors cursor-pointer"
                        x-text="copied ? 'Copied!' : 'Copy'"></button>
                </div>

                <!-- Native Mobile Share Sheet Button (Shown when navigator.share is available) -->
                <template x-if="canNativeShare">
                    <button @click="shareNative()" type="button"
                        class="w-full py-2.5 px-4 rounded-xl bg-primary-container text-on-primary-container text-xs font-bold uppercase tracking-wider glow-primary hover:opacity-95 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">share</span>
                        <span>Share on Any App</span>
                    </button>
                </template>

                <!-- One-Click Social Platform Sharing Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                    <!-- WhatsApp -->
                    <button @click="shareWhatsApp()" type="button"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl bg-surface-container hover:bg-[#25D366]/15 hover:border-[#25D366]/40 border border-outline-variant/20 text-on-surface transition-all group active:scale-95">
                        <svg class="w-5 h-5 text-[#25D366] transition-transform group-hover:scale-110"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 15 3.8 13.47 3.8 11.91C3.81 7.37 7.5 3.67 12.05 3.67M9.53 7.64C9.33 7.64 9 7.72 8.78 7.96C8.56 8.21 7.94 8.79 7.94 9.97C7.94 11.15 8.8 12.28 8.92 12.44C9.05 12.6 10.6 15 12.97 16.03C14.93 16.88 15.34 16.71 15.75 16.67C16.16 16.63 17.07 16.12 17.26 15.58C17.45 15.04 17.45 14.58 17.39 14.48C17.33 14.38 17.17 14.32 16.92 14.2C16.67 14.08 15.46 13.48 15.23 13.4C15.01 13.32 14.85 13.28 14.69 13.52C14.53 13.76 14.07 14.32 13.93 14.48C13.79 14.64 13.65 14.66 13.4 14.54C13.15 14.42 12.35 14.16 11.4 13.31C10.66 12.65 10.16 11.84 10.02 11.6C9.88 11.36 10 11.23 10.13 11.11C10.24 11 10.38 10.82 10.5 10.68C10.63 10.54 10.67 10.44 10.75 10.28C10.83 10.12 10.79 9.98 10.73 9.86C10.67 9.74 10.21 8.6 10.02 8.14C9.83 7.69 9.64 7.75 9.53 7.64Z" />
                        </svg>
                        <span class="text-xs font-semibold">WhatsApp</span>
                    </button>

                    <!-- LinkedIn -->
                    <button @click="shareLinkedIn()" type="button"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl bg-surface-container hover:bg-[#0A66C2]/15 hover:border-[#0A66C2]/40 border border-outline-variant/20 text-on-surface transition-all group active:scale-95">
                        <svg class="w-5 h-5 text-[#0A66C2] transition-transform group-hover:scale-110"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z" />
                        </svg>
                        <span class="text-xs font-semibold">LinkedIn</span>
                    </button>

                    <!-- X / Twitter -->
                    <button @click="shareTwitter()" type="button"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl bg-surface-container hover:bg-white/10 hover:border-white/30 border border-outline-variant/20 text-on-surface transition-all group active:scale-95">
                        <svg class="w-5 h-5 text-on-surface transition-transform group-hover:scale-110"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                        <span class="text-xs font-semibold">X / Twitter</span>
                    </button>

                    <!-- Instagram / Copy Link -->
                    <button @click="copyForInstagram()" type="button"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl bg-surface-container hover:bg-[#E4405F]/15 hover:border-[#E4405F]/40 border border-outline-variant/20 text-on-surface transition-all group active:scale-95 relative">
                        <svg class="w-5 h-5 text-[#E4405F] transition-transform group-hover:scale-110"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3Z" />
                        </svg>
                        <span class="text-xs font-semibold" x-text="copied ? 'Copied!' : 'Instagram'"></span>
                    </button>
                </div>

                <!-- Copy Notification Toast Bar -->
                <div x-show="copied" x-transition
                    class="p-2.5 rounded-xl bg-secondary-container/40 border border-primary/20 text-xs text-primary font-bold flex items-center justify-center gap-1.5"
                    x-cloak>
                    <span class="material-symbols-outlined text-[16px] icon-fill">check_circle</span>
                    <span
                        x-text="copyToast || 'Link and invite text copied! Paste it into your message or story.'"></span>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="mt-6 flex items-center justify-end">
                <button @click="close()" type="button"
                    class="px-5 py-2.5 rounded-full text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest transition-colors">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>