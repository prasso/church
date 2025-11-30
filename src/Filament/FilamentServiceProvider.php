<?php

namespace Prasso\Church\Filament;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('church')
            ->hasViews(__DIR__ . '/../Resources/views');
    }

    public function packageBooted(): void
    {
        // Resources and widgets are now discovered by panel providers
        // No need for manual registration here
    }
}
