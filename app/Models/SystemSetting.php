<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'global.system_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label',
        'description',
        'is_secret',
    ];

    protected $casts = [
        'is_secret' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting || $setting->value === null) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set/update a setting value by key.
     */
    public static function set(string $key, $value, ?string $group = null, ?string $type = null, ?string $label = null, ?string $description = null, bool $isSecret = false): self
    {
        $data = ['value' => is_array($value) ? json_encode($value) : (string) $value];
        if ($group) $data['group'] = $group;
        if ($type) $data['type'] = $type;
        if ($label) $data['label'] = $label;
        if ($description) $data['description'] = $description;
        if ($isSecret) $data['is_secret'] = $isSecret;

        return static::updateOrCreate(['key' => $key], $data);
    }

    /**
     * Get all settings grouped.
     */
    public static function getAllGrouped(): array
    {
        $settings = static::all();
        $grouped = [];
        foreach ($settings as $s) {
            $grouped[$s->group][] = $s;
        }
        return $grouped;
    }
}
