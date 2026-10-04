<?php

namespace Modules\Search\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SearchServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Search';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'search';

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
    }
}
