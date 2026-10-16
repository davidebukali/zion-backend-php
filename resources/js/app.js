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

Alpine.data('waitlistForm', () => ({
    email: '',
    status: 'idle', // 'idle' | 'submitting' | 'success'
    errorMessage: '',
    submit() {
        if (!this.email || !this.email.includes('@')) {
            this.errorMessage = 'Please enter a valid email address.';
            return;
        }
        this.errorMessage = '';
        this.status = 'submitting';
        setTimeout(() => {
            this.status = 'success';
            this.email = '';
            setTimeout(() => {
                this.status = 'idle';
            }, 6000);
        }, 600);
    }
}));

Alpine.start();
