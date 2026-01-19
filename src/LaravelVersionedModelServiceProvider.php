<?php

declare(strict_types=1);

namespace Thumbrise\LaravelVersionedModel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelVersionedModelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-versioned-model');
        $package->discoversMigrations();
        $package->runsMigrations();
    }
}
