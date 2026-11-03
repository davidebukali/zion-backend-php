<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GoogleAnalyticsViewTest extends TestCase
{
    public function test_component_renders_nothing_when_analytics_id_is_not_set(): void
    {
        Config::set('services.google_analytics.id', null);

        $view = Blade::render('<x-google-analytics />');

        $this->assertStringNotContainsString('googletagmanager.com', $view);
        $this->assertStringNotContainsString('gtag(', $view);
        $this->assertEmpty(trim($view));
    }

    public function test_component_renders_gtag_script_when_analytics_id_is_configured(): void
    {
        Config::set('services.google_analytics.id', 'G-TEST123456');

        $view = Blade::render('<x-google-analytics />');

        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-TEST123456', $view);
        $this->assertStringContainsString("gtag('config', 'G-TEST123456');", $view);
    }

    public function test_component_allows_explicit_measurement_id_override(): void
    {
        Config::set('services.google_analytics.id', null);

        $view = Blade::render('<x-google-analytics measurement-id="G-CUSTOMID99" />');

        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-CUSTOMID99', $view);
        $this->assertStringContainsString("gtag('config', 'G-CUSTOMID99');", $view);
    }

    public function test_layouts_include_google_analytics_component(): void
    {
        $componentLayout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
        $this->assertStringContainsString('<x-google-analytics />', $componentLayout);

        $defaultLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('<x-google-analytics />', $defaultLayout);
    }
}

