<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalUser extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'global.global_user';
    protected $primaryKey = 'global_user_id';

    protected $fillable = [
        'global_user_nama',
        'global_user_username',
        'global_user_email',
        'global_user_password',
        'global_user_telegram_id',
        'global_user_role',
        'global_user_can_code',
        'global_user_can_terminal',
        'global_user_is_active',
        'global_user_allowed_commands',
        'global_user_notes',
        'remember_token',
    ];

    protected $casts = [
        'global_user_can_code' => 'boolean',
        'global_user_can_terminal' => 'boolean',
        'global_user_is_active' => 'boolean',
        'global_user_allowed_commands' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'global_user_password',
        'password',
        'remember_token',
    ];

    protected $appends = [
        'id',
        'name',
        'email',
        'username',
        'telegram_id',
        'role',
        'is_active',
        'can_code',
        'can_terminal',
        'notes',
        'has_password',
    ];

    public function getAuthPassword()
    {
        return $this->global_user_password;
    }

    public function getAuthIdentifierName()
    {
        return 'global_user_id';
    }

    public function setGlobalUserPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['global_user_password'] = \Illuminate\Support\Facades\Hash::needsRehash($value)
                ? \Illuminate\Support\Facades\Hash::make($value)
                : $value;
        }
    }

    public function setPasswordAttribute($value)
    {
        $this->setGlobalUserPasswordAttribute($value);
    }

    public function getEmailAttribute()
    {
        return $this->global_user_email;
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['global_user_email'] = $value;
    }

    public function getHasPasswordAttribute(): bool
    {
        return !empty($this->global_user_password);
    }

    public function getIdAttribute()
    {
        return $this->global_user_id;
    }

    // Helper accessors for seamless display
    public function getNameAttribute()
    {
        return $this->global_user_nama;
    }

    public function getUsernameAttribute()
    {
        return $this->global_user_username;
    }

    public function getTelegramIdAttribute()
    {
        return $this->global_user_telegram_id;
    }

    public function getRoleAttribute()
    {
        return $this->global_user_role;
    }

    public function getIsActiveAttribute()
    {
        return $this->global_user_is_active;
    }

    public function getCanCodeAttribute()
    {
        return $this->global_user_can_code;
    }

    public function getCanTerminalAttribute()
    {
        return $this->global_user_can_terminal;
    }

    public function getNotesAttribute()
    {
        return $this->global_user_notes;
    }
}
