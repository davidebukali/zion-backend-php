@props(['measurementId' => config('services.google_analytics.id')])

@if (!empty($measurementId))
    <!-- Google Analytics (gtag.js) -->
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ $measurementId }}');
    </script>
@endif

