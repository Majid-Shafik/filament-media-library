<?php

namespace MajidShafik\FilamentMediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $guarded = [];

    protected $casts = [
        'file_size' => 'integer',
        'width'     => 'integer',
        'height'    => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MediaCategory::class, 'category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class, 'media_id');
    }

    /**
     * Get public URL for the media item.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?: 'public')->url($this->file_path);
    }

    /**
     * Scope a query to only include media matching a SHA-256 hash.
     */
    public function scopeByHash(\Illuminate\Database\Eloquent\Builder $query, string $hash): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('sha256_hash', strtolower($hash));
    }

    /**
     * Compute and save the SHA-256 hash if it is currently null and the physical file exists.
     */
    public function ensureHashCalculated(): ?string
    {
        if (!empty($this->sha256_hash)) {
            return $this->sha256_hash;
        }

        $disk = $this->disk ?: 'public';
        if (!Storage::disk($disk)->exists($this->file_path)) {
            return null;
        }

        $realPath = Storage::disk($disk)->path($this->file_path);
        if (!file_exists($realPath) || !is_readable($realPath)) {
            return null;
        }

        $hash = hash_file('sha256', $realPath);
        if ($hash) {
            $this->sha256_hash = strtolower($hash);
            $this->saveQuietly();
        }

        return $this->sha256_hash;
    }

    public function favoritedByUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'media_favorites', 'media_id', 'user_id')
            ->withTimestamps();
    }

    public function isFavoritedBy(?int $userId = null): bool
    {
        $userId = $userId ?? \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            return false;
        }

        if ($this->relationLoaded('favoritedByUsers')) {
            return $this->favoritedByUsers->contains('id', $userId);
        }

        return \Illuminate\Support\Facades\DB::table('media_favorites')
            ->where('user_id', $userId)
            ->where('media_id', $this->id)
            ->exists();
    }
}
