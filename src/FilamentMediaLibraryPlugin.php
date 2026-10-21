<?php

namespace MajidShafik\FilamentMediaLibrary;

use Filament\Contracts\Plugin;
use Filament\Panel;
use MajidShafik\FilamentMediaLibrary\Resources\MediaResource;
use MajidShafik\FilamentMediaLibrary\Resources\MediaCategoryResource;

class FilamentMediaLibraryPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-media-library';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                MediaResource::class,
                MediaCategoryResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Custom boot logic
    }
}
