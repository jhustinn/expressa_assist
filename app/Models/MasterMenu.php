<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMenu extends Model
{
    use HasFactory;

    protected $table = 'global.menus';

    protected $fillable = [
        'section_key',
        'section_title',
        'section_order',
        'name',
        'url',
        'icon',
        'badge',
        'order',
        'is_active',
        'roles',
        'feature_code',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'roles' => 'array',
        'section_order' => 'integer',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for menus visible to a specific role.
     */
    public static function getSectionsForRole(?string $role = null): array
    {
        $role = $role ?: 'guest';
        $menus = static::where('is_active', true)
            ->orderBy('section_order', 'asc')
            ->orderBy('order', 'asc')
            ->get();

        $sections = [];
        foreach ($menus as $m) {
            $allowedRoles = $m->roles;
            // Admin always has access to all active menus
            $hasAccess = ($role === 'admin') ||
                empty($allowedRoles) ||
                in_array('*', $allowedRoles) ||
                in_array($role, $allowedRoles);

            if (!$hasAccess) {
                continue;
            }

            if (!isset($sections[$m->section_key])) {
                $sections[$m->section_key] = [
                    'key' => $m->section_key,
                    'title' => $m->section_title,
                    'order' => $m->section_order,
                    'items' => [],
                ];
            }

            $sections[$m->section_key]['items'][] = [
                'id' => $m->id,
                'name' => $m->name,
                'url' => $m->url,
                'icon' => $m->icon,
                'badge' => $m->badge,
                'roles' => $m->roles,
            ];
        }

        return array_values($sections);
    }
}
