<?php

namespace MajidShafik\FilamentMediaLibrary;

use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MajidShafik\FilamentMediaLibrary\Commands\BackfillMediaHashesCommand;
use MajidShafik\FilamentMediaLibrary\Models\Media;
use MajidShafik\FilamentMediaLibrary\Models\MediaCategory;
use MajidShafik\FilamentMediaLibrary\Policies\MediaPolicy;
use MajidShafik\FilamentMediaLibrary\Policies\MediaCategoryPolicy;

class FilamentMediaLibraryServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-media-library';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('filament-media-library')
            ->hasViews('filament-media-library')
            ->hasTranslations()
            ->hasMigrations([
                '2026_10_18_190001_create_media_categories_table',
                '2026_10_18_190002_create_media_table',
                '2026_10_18_190003_create_media_usages_table',
                '2026_10_18_190004_create_pending_media_deletions_table',
                '2026_10_19_200001_add_sha256_hash_to_media_table',
                '2026_10_19_210000_create_media_favorites_table',
            ])
            ->hasCommand(BackfillMediaHashesCommand::class);
    }

    public function packageBooted(): void
    {
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(MediaCategory::class, MediaCategoryPolicy::class);
    }
}
