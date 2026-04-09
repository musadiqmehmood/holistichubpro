<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'password'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ✅ USER RELATIONSHIP
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Scope for recent passwords
    public function scopeRecent($query, int $count = 5)
    {
        return $query->latest()->limit($count);
    }
}
