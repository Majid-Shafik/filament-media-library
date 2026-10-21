<?php

namespace MajidShafik\FilamentMediaLibrary;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MajidShafik\FilamentMediaLibrary\Commands\BackfillMediaHashesCommand;

class FilamentMediaLibraryServiceProvider extends PackageServiceProvider
{
    public static string  = 'filament-media-library';

    public function configurePackage(Package ): void
    {
        
            ->name(static::)
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
}
