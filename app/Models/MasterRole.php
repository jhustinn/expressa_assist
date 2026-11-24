<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterRole extends Model
{
    use HasFactory;

    protected $table = 'global.roles';

    protected $fillable = [
        'name',
        'label',
        'description',
        'feature_permissions',
        'is_system',
    ];

    protected $casts = [
        'feature_permissions' => 'array',
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
