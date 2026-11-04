<?php

namespace MajidShafik\FilamentMediaLibrary\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use MajidShafik\FilamentMediaLibrary\Models\Media;

trait HasMediaLibraryFavorites
{
    public function favoriteMedia(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_favorites', 'user_id', 'media_id')
            ->withTimestamps();
    }
}

