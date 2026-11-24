<?php

namespace App\Http\Controllers;

use App\Models\MasterMenu;
use App\Models\MasterRole;
use App\Models\GlobalActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MenuManagementController extends Controller
{
    public function index()
    {
        $menus = MasterMenu::orderBy('section_order', 'asc')
            ->orderBy('order', 'asc')
            ->get();

        $roles = MasterRole::orderBy('id', 'asc')->get();

        $sections = [
            ['key' => 'core_chat', 'title' => 'Core & Chat', 'order' => 1],
            ['key' => 'intelligence_tools', 'title' => 'Intelligence & Tools', 'order' => 2],
            ['key' => 'channels_integrations', 'title' => 'Channels & Integrations', 'order' => 3],
            ['key' => 'config_models', 'title' => 'Config & Models', 'order' => 4],
            ['key' => 'system_operations', 'title' => 'System & Operations', 'order' => 5],
        ];

        return Inertia::render('Menus/Index', [
            'menus' => $menus,
            'roles' => $roles,
            'sections' => $sections,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_key' => 'required|string',
            'section_title' => 'required|string',
            'section_order' => 'required|integer',
            'name' => 'required|string|max:100',
            'url' => 'required|string|max:150',
            'icon' => 'required|string|max:100',
            'badge' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'feature_code' => 'nullable|string',
        ]);

        $menu = MasterMenu::create([
            'section_key' => $validated['section_key'],
            'section_title' => $validated['section_title'],
            'section_order' => $validated['section_order'] ?? 1,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'icon' => $validated['icon'],
            'badge' => $validated['badge'] ?? null,
            'order' => $validated['order'] ?? (MasterMenu::where('section_key', $validated['section_key'])->max('order') + 1),
            'is_active' => $request->boolean('is_active', true),
            'roles' => $validated['roles'] ?? ['admin'],
            'feature_code' => $validated['feature_code'] ?? null,
        ]);

        GlobalActivityLog::create([
            'global_activity_action' => 'menu_create',
            'global_activity_description' => "Menambahkan item menu baru: {$menu->name} ({$menu->url})",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "Menu '{$menu->name}' berhasil ditambahkan!");
    }

    public function update(Request $request, $id)
    {
        $menu = MasterMenu::findOrFail($id);

        $validated = $request->validate([
            'section_key' => 'required|string',
            'section_title' => 'required|string',
            'section_order' => 'nullable|integer',
            'name' => 'required|string|max:100',
            'url' => 'required|string|max:150',
            'icon' => 'required|string|max:100',
            'badge' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'feature_code' => 'nullable|string',
        ]);

        $menu->update($validated);

        GlobalActivityLog::create([
            'global_activity_action' => 'menu_update',
            'global_activity_description' => "Memperbarui konfigurasi menu: {$menu->name}",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "Menu '{$menu->name}' berhasil diperbarui!");
    }

    public function toggle(Request $request, $id)
    {
        $menu = MasterMenu::findOrFail($id);
        $menu->is_active = !$menu->is_active;
        $menu->save();

        GlobalActivityLog::create([
            'global_activity_action' => 'menu_toggle',
            'global_activity_description' => "Mengubah status aktif menu {$menu->name} menjadi " . ($menu->is_active ? 'Aktif' : 'Nonaktif'),
            'global_activity_status' => 'info',
        ]);

        return redirect()->back()->with('success', "Status menu '{$menu->name}' berhasil diubah!");
    }

    public function updateMatrix(Request $request)
    {
        $request->validate([
            'matrix' => 'required|array', // [menuId => [role1, role2, ...]]
        ]);

        $matrix = $request->input('matrix', []);
        foreach ($matrix as $menuId => $roleList) {
            $menu = MasterMenu::find($menuId);
            if ($menu) {
                // Ensure admin is always preserved unless intentionally changed
                $cleanRoles = is_array($roleList) ? array_values(array_unique($roleList)) : [];
                $menu->roles = $cleanRoles;
                $menu->save();
            }
        }

        GlobalActivityLog::create([
            'global_activity_action' => 'rbac_matrix_update',
            'global_activity_description' => "Memperbarui matriks hak akses Role-Based Access Control (RBAC) Menu",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', 'Matriks hak akses RBAC menu berhasil disimpan ke database!');
    }

    public function destroy($id)
    {
        $menu = MasterMenu::findOrFail($id);
        $name = $menu->name;
        $menu->delete();

        GlobalActivityLog::create([
            'global_activity_action' => 'menu_delete',
            'global_activity_description' => "Menghapus item menu: {$name}",
            'global_activity_status' => 'warning',
        ]);

        return redirect()->back()->with('success', "Menu '{$name}' berhasil dihapus.");
    }
}
