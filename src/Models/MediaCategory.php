<?php

namespace MajidShafik\FilamentMediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaCategory extends Model
{
    protected $table = 'media_categories';

    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'category_id');
    }

    /**
     * Get all recursive descendant category IDs.
     *
     * @return \Illuminate\Support\Collection<int>
     */
    public function getAllDescendantIds(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child->id);
            $descendants = $descendants->merge($child->getAllDescendantIds());
        }

        return $descendants;
    }
}
