<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class File extends Model
{
    /** @use HasFactory<\Database\Factories\FileFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'create_datetime',
        'path',
        'checksum',
    ];

    protected $casts = [
        'create_datetime' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'user_id',
        'path',
        'status_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }
}
