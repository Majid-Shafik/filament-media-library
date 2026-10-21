<?php

namespace MajidShafik\FilamentMediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;

class PendingMediaDeletion extends Model
{
    protected $table = 'pending_media_deletions';

    protected $guarded = [];

    protected $casts = [
        'locked_at'    => 'datetime',
        'processed_at' => 'datetime',
        'attempts'     => 'integer',
    ];
}
