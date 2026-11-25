<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasFactory;

    protected $table = 'global.ai_models';
    protected $primaryKey = 'ai_model_id';

    protected $fillable = [
        'name',
        'model_key',
        'provider',
        'base_url',
        'api_key_env',
        'context_window',
        'max_output_tokens',
        'description',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'context_window' => 'integer',
        'max_output_tokens' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Scope for active models
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
