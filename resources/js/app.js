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
    async submit(customSource) {
        if (!this.email || !this.email.includes('@')) {
            this.errorMessage = 'Please enter a valid email address.';
            return;
        }
        this.errorMessage = '';
        this.status = 'submitting';

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
                if (data.status === 'already_registered') {
                    this.status = 'already_registered';
                    this.message = data.message || "You're already on the waitlist! We'll reach out as soon as your invite is ready.";
                } else {
                    this.status = 'success';
                    this.message = data.message || "Your early beta slot is reserved! Check your inbox for confirmation.";
                    this.email = '';
                }

                setTimeout(() => {
                    this.status = 'idle';
                    this.message = '';
                }, 8000);
            } else if (response.status === 422) {
                this.status = 'idle';
                this.errorMessage = data.message || 'Please provide a valid email address.';
            } else if (response.status === 429) {
                this.status = 'idle';
                this.errorMessage = 'Too many attempts. Please try again in a minute.';
            } else {
                this.status = 'idle';
                this.errorMessage = data.message || 'Something went wrong. Please try again.';
            }
        } catch (err) {
            this.status = 'idle';
            this.errorMessage = 'Network connection error. Please try again shortly.';
        }
    }
}));

Alpine.start();
