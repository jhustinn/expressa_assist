<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalActivityLog extends Model
{
    use HasFactory;

    protected $table = 'global.global_activity_log';
    protected $primaryKey = 'global_activity_id';

    protected $fillable = [
        'global_activity_action',
        'global_activity_description',
        'global_activity_sender',
        'global_activity_status',
        'global_activity_metadata',
        // Legacy compatibility aliases
        'type',
        'action',
        'message',
        'description',
        'sender',
        'status',
        'metadata',
    ];

    protected $casts = [
        'global_activity_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'id',
        'action',
        'description',
        'status',
        'type',
        'message',
        'sender',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->global_activity_action)) {
                $model->global_activity_action = $model->action ?? $model->type ?? 'general_action';
            }
            if (empty($model->global_activity_description)) {
                $model->global_activity_description = $model->description ?? $model->message ?? '-';
            }
            if (empty($model->global_activity_sender)) {
                $model->global_activity_sender = $model->sender ?? 'Dashboard Web';
            }
            if (empty($model->global_activity_status)) {
                $model->global_activity_status = $model->status ?? 'success';
            }
            if (empty($model->global_activity_metadata) && !empty($model->metadata)) {
                $model->global_activity_metadata = $model->metadata;
            }
        });
    }

    public function getIdAttribute()
    {
        return $this->global_activity_id;
    }

    public function getActionAttribute()
    {
        return $this->global_activity_action;
    }

    public function getTypeAttribute()
    {
        return $this->global_activity_action;
    }

    public function getDescriptionAttribute()
    {
        return $this->global_activity_description;
    }

    public function getMessageAttribute()
    {
        return $this->global_activity_description;
    }

    public function getSenderAttribute()
    {
        return $this->global_activity_sender;
    }

    public function getStatusAttribute()
    {
        return $this->global_activity_status;
    }

    public function setActionAttribute($value)
    {
        $this->attributes['global_activity_action'] = $value;
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['global_activity_action'] = $value;
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['global_activity_description'] = $value;
    }

    public function setMessageAttribute($value)
    {
        $this->attributes['global_activity_description'] = $value;
    }

    public function setSenderAttribute($value)
    {
        $this->attributes['global_activity_sender'] = $value;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['global_activity_status'] = $value;
    }

    public function setMetadataAttribute($value)
    {
        $this->attributes['global_activity_metadata'] = is_array($value) ? json_encode($value) : $value;
    }
}
