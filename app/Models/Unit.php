<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'units';

    protected $fillable = ['name', 'description', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public static function activeList(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->orderBy('name')->get(['id', 'name']);
    }
}
