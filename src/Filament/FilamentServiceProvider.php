<?php

namespace Prasso\Church\Filament;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Prasso\Church\Filament\Resources\MemberResource;
use Prasso\Church\Filament\Resources\GroupResource;
use Prasso\Church\Filament\Resources\EventResource;
use Prasso\Church\Filament\Resources\PrayerRequestResource;
use Prasso\Church\Filament\Resources\PastoralVisitResource;
use Prasso\Church\Filament\Widgets\ChurchOverview;
use Prasso\Church\Filament\Widgets\ChurchMembershipGrowth;
use Prasso\Church\Filament\Widgets\ChurchQuickActions;
use Prasso\Church\Filament\Widgets\ChurchRecentActivity;

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
        // Register the resources
        $this->registerResources();
        
        // Register the widgets
        $this->registerWidgets();
    }

    protected function registerResources(): void
    {
        // Register all resources with Filament
        \Filament\Facades\Filament::registerResources([
            MemberResource::class,
            GroupResource::class,
            EventResource::class,
            PrayerRequestResource::class,
            PastoralVisitResource::class,
        ]);
    }

    protected function registerWidgets(): void
    {
        // Register all widgets with Filament
        \Filament\Facades\Filament::registerWidgets([
            ChurchOverview::class,
            ChurchMembershipGrowth::class,
            ChurchQuickActions::class,
            ChurchRecentActivity::class,
        ]);
    }
}
