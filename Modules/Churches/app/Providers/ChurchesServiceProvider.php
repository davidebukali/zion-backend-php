<?php

namespace Modules\Churches\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ChurchesServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Churches';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'churches';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $sourcePath = module_path($this->name, 'resources/views');

        if (is_dir($sourcePath)) {
            parent::registerViews();
        }
    }
}

