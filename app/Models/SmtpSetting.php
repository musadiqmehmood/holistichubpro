<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmtpSetting extends Model
{
    use HasFactory;

    protected $table = 'smtp_settings';

    protected $fillable = ['status', 'host', 'port', 'username', 'password', 'encryption'];

    protected $casts = [
        'status' => 'boolean',
        'port'   => 'integer',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getPasswordAttribute(string $value): string
    {
        try {
            return decrypt($value);
        } catch (\Throwable) {
            return '';
        }
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = encrypt($value);
    }
}
