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
        /** @var static  */
         = filament(app(static::class)->getId());

        return ;
    }

    public function register(Panel ): void
    {
        
            ->resources([
                MediaResource::class,
                MediaCategoryResource::class,
            ]);
    }

    public function boot(Panel ): void
    {
        // Custom boot logic
    }
}
