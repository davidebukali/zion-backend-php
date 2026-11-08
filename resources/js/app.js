import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('themeSwitcher', () => ({
    isDark: true,
    init() {
        const saved = localStorage.getItem('theme');
        if (saved) {
            this.isDark = saved === 'dark';
        } else {
            // Default to dark mode as per Tsiyon design specification
            this.isDark = true;
        }
        this.apply();
    },
    toggle() {
        this.isDark = !this.isDark;
        localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        this.apply();
    },
    apply() {
        if (this.isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}));

Alpine.data('mobileNav', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
        if (this.open) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
    close() {
        this.open = false;
        document.body.style.overflow = '';
    }
}));

Alpine.data('waitlistForm', (defaultSource = 'hero') => ({
    email: '',
    status: 'idle', // 'idle' | 'submitting' | 'success' | 'already_registered'
    errorMessage: '',
    message: '',
    isCelebrating: false,
    async submit(customSource) {
        if (!this.email || !this.email.includes('@')) {
            this.errorMessage = 'Please enter a valid email address.';
            this.isCelebrating = false;
            return;
        }
        this.errorMessage = '';
        this.status = 'submitting';
        // Trigger celebratory animation immediately upon form submission
        this.isCelebrating = true;

        try {
            const response = await fetch('/api/waitlist', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    email: this.email,
                    source: customSource || defaultSource
                })
            });

            const data = await response.json();

            if (response.ok) {
                if (typeof window.gtag === 'function') {
                    window.gtag('event', 'generate_lead', {
                        event_category: 'waitlist',
                        event_label: customSource || defaultSource,
                        status: data.status
                    });
                }

                const submittedEmail = this.email;

                if (data.status === 'already_registered') {
                    this.status = 'already_registered';
                    this.message = data.message || "You're already on the waitlist! We'll reach out as soon as your invite is ready.";
                } else {
                    this.status = 'success';
                    this.message = data.message || "Your early beta slot is reserved! Check your inbox for confirmation.";
                    this.email = '';
                }

                // Trigger confirmation loop modal
                window.dispatchEvent(new CustomEvent('open-waitlist-confirmation', {
                    detail: {
                        status: data.status,
                        email: submittedEmail,
                        message: this.message
                    }
                }));

                setTimeout(() => {
                    this.status = 'idle';
                    this.message = '';
                    this.isCelebrating = false;
                }, 8000);
            } else if (response.status === 422) {
                this.status = 'idle';
                this.errorMessage = data.message || 'Please provide a valid email address.';
                this.isCelebrating = false;
            } else if (response.status === 429) {
                this.status = 'idle';
                this.errorMessage = 'Too many attempts. Please try again in a minute.';
                this.isCelebrating = false;
            } else {
                this.status = 'idle';
                this.errorMessage = data.message || 'Something went wrong. Please try again.';
                this.isCelebrating = false;
            }
        } catch (err) {
            this.status = 'idle';
            this.errorMessage = 'Network connection error. Please try again shortly.';
            this.isCelebrating = false;
        }
    }
}));

Alpine.data('waitlistConfirmationModal', () => ({
    open: false,
    status: 'success', // 'success' | 'already_registered'
    email: '',
    copied: false,
    copyToast: '',
    canNativeShare: typeof navigator !== 'undefined' && typeof navigator.share === 'function',
    shareUrl: window.location.origin || 'https://tsiyonapp.com',
    shareText: 'I just claimed my early beta spot for Tsiyon — a peaceful, ad-free social home built for fellowship, scripture reflection, and prayer. It’s open to everyone who loves wholesome community. Claim your spot here:',

    init() {
        window.addEventListener('open-waitlist-confirmation', (e) => {
            this.status = e.detail?.status || 'success';
            this.email = e.detail?.email || '';
            this.open = true;
            this.copied = false;
            this.copyToast = '';
            document.body.style.overflow = 'hidden';
        });
    },

    close() {
        this.open = false;
        document.body.style.overflow = '';
    },

    async shareNative() {
        if (this.canNativeShare) {
            try {
                await navigator.share({
                    title: 'Tsiyon — A Social Home Built for What Matters Most',
                    text: `${this.shareText} ${this.shareUrl}`,
                    url: this.shareUrl
                });
            } catch (err) {
                // User dismissed share sheet
            }
        }
    },

    shareWhatsApp() {
        const text = encodeURIComponent(`${this.shareText} ${this.shareUrl}`);
        window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank', 'noopener,noreferrer');
    },

    async shareLinkedIn() {
        const fullShare = `${this.shareText} ${this.shareUrl}`;

        // Copy to clipboard as safe backup
        try {
            await navigator.clipboard.writeText(fullShare);
            this.copied = true;
            this.copyToast = 'Invite text copied! Paste it into your LinkedIn post.';
            setTimeout(() => {
                this.copied = false;
            }, 4000);
        } catch (e) {}

        // LinkedIn post composer intent with pre-filled message text & link
        const linkedInUrl = `https://www.linkedin.com/feed/?shareActive=true&text=${encodeURIComponent(fullShare)}`;
        window.open(linkedInUrl, '_blank', 'noopener,noreferrer');
    },

    shareTwitter() {
        const text = encodeURIComponent(this.shareText);
        const url = encodeURIComponent(this.shareUrl);
        window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'noopener,noreferrer');
    },

    async copyForInstagram() {
        const fullShare = `${this.shareText} ${this.shareUrl}`;
        this.copyToast = 'Link and invite text copied! Paste it in your Instagram Story, Bio, or DM.';
        try {
            await navigator.clipboard.writeText(fullShare);
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 3500);
        } catch (e) {
            const input = document.createElement('textarea');
            input.value = fullShare;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 3500);
        }
    }
}));

Alpine.start();
