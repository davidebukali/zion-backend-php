<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tsiyon — A social home built for what matters most. Christ-centered, family-friendly content that builds up your life.">
    <title>{{ $title ?? 'Tsiyon — A Social Home for What Matters Most' }}</title>

    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
