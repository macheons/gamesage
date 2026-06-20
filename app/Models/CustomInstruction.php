<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomInstruction extends Model
{
    protected $fillable = ['user_id', 'about_you', 'behavior', 'is_enabled'];

    protected $casts = ['is_enabled' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}