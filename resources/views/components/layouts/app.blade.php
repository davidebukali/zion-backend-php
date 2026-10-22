<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tsiyon — A social home built for what matters most. Christ-centered, family-friendly content that builds up your life.">
    <title>{{ $title ?? 'Tsiyon — A Social Home for What Matters Most' }}</title>

    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon_48x48.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_32x32.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_16x16.png') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple_touch_icon_180x180.png') }}?v=2">

    <!-- Google Fonts & Icons (Non-render-blocking with display=swap) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="https://fonts.gstatic.com/s/materialsymbolsoutlined/v380/kJF4BvYX7BgnkSrUwT8OhrdQw4oELdPIeeII9v6oDMzBwG-RpA6RzaxHMPdY40KH8nGzv3fzfVJU22ZZLsYEpzC_1ver5Y0J1Llf.woff2" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap">
    </noscript>

    <!-- Anti-FOUC Theme Initialization Script -->
    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            if (saved === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- Scripts & Styles (Inlined in production to eliminate render-blocking CSS) -->
    @if (!Vite::isRunningHot() && file_exists(public_path('build/manifest.json')))
        <style>{!! Vite::content('resources/css/app.css') !!}</style>
        @vite(['resources/js/app.js'])
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-surface font-sans text-on-surface antialiased selection:bg-primary-container selection:text-on-primary-container min-h-screen flex flex-col justify-between transition-colors duration-200">
    <!-- Header / Sticky Navigation -->
    <x-landing.header />


    <!-- Main Content Area -->
    <main class="w-full pt-20 bg-surface flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Global Footer -->
    <x-landing.footer />

</body>
</html>
