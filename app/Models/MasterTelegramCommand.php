<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterTelegramCommand extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master.master_telegram_command';
    protected $primaryKey = 'master_command_id';

    protected $fillable = [
        'master_command_command',
        'master_command_nama',
        'master_command_deskripsi',
        'master_command_target_profile',
        'master_command_prompt_template',
        'master_command_is_active',
    ];

    protected $casts = [
        'master_command_is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'id',
        'command',
        'name',
        'description',
        'target_profile',
        'prompt_template',
        'is_active',
    ];

    public function getIdAttribute()
    {
        return $this->master_command_id;
    }

    // Helper accessors
    public function getCommandAttribute()
    {
        return $this->master_command_command;
    }

    public function getNameAttribute()
    {
        return $this->master_command_nama;
    }

    public function getDescriptionAttribute()
    {
        return $this->master_command_deskripsi;
    }

    public function getTargetProfileAttribute()
    {
        return $this->master_command_target_profile;
    }

    public function getPromptTemplateAttribute()
    {
        return $this->master_command_prompt_template;
    }

    public function getIsActiveAttribute()
    {
        return $this->master_command_is_active;
    }
}
