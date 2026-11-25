<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\SystemSetting;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $defaultHome = SystemSetting::get('hermes_home_path', 'E:\\hermes');
        $requestedPath = $request->query('path', $defaultHome);

        // Normalize separators
        $cleanPath = rtrim(str_replace(['/', '\\\\'], '\\', $requestedPath), '\\');
        if (strlen($cleanPath) === 2 && substr($cleanPath, 1, 1) === ':') {
            $cleanPath .= '\\';
        }

        // Check if directory exists; fallback if invalid
        if (!File::isDirectory($cleanPath)) {
            $cleanPath = $defaultHome;
            if (!File::isDirectory($cleanPath)) {
                $cleanPath = base_path();
            }
        }

        // Quick Root Presets
        $quickRoots = [
            [
                'name' => 'Hermes Root',
                'path' => $defaultHome,
                'icon' => 'fa-solid fa-server',
                'exists' => File::isDirectory($defaultHome),
            ],
            [
                'name' => 'Jhustyn Profile',
                'path' => $defaultHome . '\\profiles\\jhustyn',
                'icon' => 'fa-solid fa-user-astronaut',
                'exists' => File::isDirectory($defaultHome . '\\profiles\\jhustyn'),
            ],
            [
                'name' => 'ERP JPP Workspace',
                'path' => 'E:\\App\\SWA\\JPP-ERP',
                'icon' => 'fa-solid fa-building-gear',
                'exists' => File::isDirectory('E:\\App\\SWA\\JPP-ERP'),
            ],
            [
                'name' => 'Expressa Dashboard',
                'path' => base_path(),
                'icon' => 'fa-solid fa-laptop-code',
                'exists' => true,
            ],
        ];

        // Parent Path
        $parentPath = null;
        $parentCandidate = dirname($cleanPath);
        if ($parentCandidate && $parentCandidate !== $cleanPath && File::isDirectory($parentCandidate)) {
            $parentPath = $parentCandidate;
        }

        // Build Breadcrumbs
        $breadcrumbs = $this->buildBreadcrumbs($cleanPath);

        // Scan Directories & Files
        $directories = [];
        $files = [];
        $totalSize = 0;

        try {
            // Read Subdirectories
            $rawDirs = File::directories($cleanPath);
            foreach ($rawDirs as $dir) {
                $baseName = basename($dir);
                // Count child items safely
                $childCount = 0;
                try {
                    $childCount = count(File::directories($dir)) + count(File::files($dir));
                } catch (\Throwable $e) {}

                $directories[] = [
                    'name' => $baseName,
                    'path' => $dir,
                    'type' => 'dir',
                    'items_count' => $childCount,
                    'last_modified' => date('Y-m-d H:i', File::lastModified($dir)),
                ];
            }

            // Read Files
            $rawFiles = File::files($cleanPath);
            foreach ($rawFiles as $file) {
                $size = $file->getSize();
                $totalSize += $size;
                $ext = strtolower($file->getExtension());

                $files[] = [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'type' => 'file',
                    'extension' => $ext,
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'last_modified' => date('Y-m-d H:i', $file->getMTime()),
                    'is_editable' => in_array($ext, ['md', 'json', 'yaml', 'yml', 'env', 'txt', 'js', 'vue', 'php', 'sql', 'css', 'html', 'log', 'sh', 'bat', 'ps1']),
                ];
            }

            // Sort alphabetically
            usort($directories, fn($a, $b) => strcasecmp($a['name'], $b['name']));
            usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        } catch (\Throwable $e) {
            // Handle permission or read errors gracefully
        }

        return Inertia::render('Files/Index', [
            'currentPath' => $cleanPath,
            'parentPath' => $parentPath,
            'breadcrumbs' => $breadcrumbs,
            'directories' => $directories,
            'files' => $files,
            'stats' => [
                'total_directories' => count($directories),
                'total_files' => count($files),
                'total_size_formatted' => $this->formatBytes($totalSize),
            ],
            'quickRoots' => $quickRoots,
        ]);
    }

    /**
     * Read file contents for code / markdown preview
     */
    public function readFile(Request $request)
    {
        $path = $request->query('path');
        if (!$path || !File::exists($path) || File::isDirectory($path)) {
            return response()->json(['success' => false, 'message' => 'Berkas tidak ditemukan.'], 404);
        }

        // Limit preview size to 5MB
        if (File::size($path) > 5 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'Ukuran berkas terlalu besar untuk pratinjau (> 5 MB).'
            ], 400);
        }

        try {
            $content = File::get($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            return response()->json([
                'success' => true,
                'name' => basename($path),
                'path' => $path,
                'extension' => $ext,
                'size' => $this->formatBytes(File::size($path)),
                'last_modified' => date('Y-m-d H:i:s', File::lastModified($path)),
                'content' => $content,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membaca berkas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download file directly
     */
    public function download(Request $request): BinaryFileResponse
    {
        $path = $request->query('path');
        if (!$path || !File::exists($path) || File::isDirectory($path)) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        return response()->download($path);
    }

    /**
     * Save edited file content safely
     */
    public function saveFile(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
            'content' => 'required|string',
        ]);

        $path = $validated['path'];

        if (!File::exists($path) || File::isDirectory($path)) {
            return response()->json(['success' => false, 'message' => 'Berkas tujuan tidak valid atau tidak ditemukan.'], 404);
        }

        // Prevent modification of dangerous root files
        $lowerPath = strtolower($path);
        if (str_contains($lowerPath, '\\windows\\') || str_contains($lowerPath, '\\system32\\')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: berkas sistem operasi dilindungi.'], 403);
        }

        try {
            File::put($path, $validated['content']);

            \App\Models\GlobalActivityLog::create([
                'global_activity_action' => 'file_edit',
                'global_activity_description' => 'Menyimpan perubahan berkas: ' . basename($path) . " ({$path})",
                'global_activity_status' => 'success',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berkas ' . basename($path) . ' berhasil disimpan!',
                'last_modified' => date('Y-m-d H:i:s', File::lastModified($path)),
                'size' => $this->formatBytes(File::size($path)),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan berkas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create new file or folder
     */
    public function createItem(Request $request)
    {
        $validated = $request->validate([
            'parent_path' => 'required|string',
            'name' => 'required|string|max:100',
            'type' => 'required|in:file,folder',
        ]);

        $parent = rtrim($validated['parent_path'], '\\');
        $cleanName = trim(preg_replace('/[\\/\\\\:*?"<>|]/', '', $validated['name']));
        if (empty($cleanName)) {
            return redirect()->back()->with('error', 'Nama berkas/folder tidak valid.');
        }

        $targetPath = $parent . DIRECTORY_SEPARATOR . $cleanName;

        if (File::exists($targetPath)) {
            return redirect()->back()->with('error', "Item dengan nama '{$cleanName}' sudah ada.");
        }

        try {
            if ($validated['type'] === 'folder') {
                File::makeDirectory($targetPath, 0755, true);
                $msg = "Folder '{$cleanName}' berhasil dibuat.";
            } else {
                File::put($targetPath, "# {$cleanName}\n\n");
                $msg = "Berkas '{$cleanName}' berhasil dibuat.";
            }

            \App\Models\GlobalActivityLog::create([
                'global_activity_action' => 'file_create',
                'global_activity_description' => "Membuat {$validated['type']}: {$cleanName} di {$parent}",
                'global_activity_status' => 'success',
            ]);

            return redirect()->route('files.index', ['path' => $parent])->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membuat item: ' . $e->getMessage());
        }
    }

    /**
     * Delete file or folder
     */
    public function deleteItem(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        $path = $validated['path'];

        if (!File::exists($path)) {
            return redirect()->back()->with('error', 'Berkas atau folder tidak ditemukan.');
        }

        // Safety: Do not allow deleting root drives or core directories
        $clean = rtrim($path, '\\');
        if (strlen($clean) <= 3 || strtolower($clean) === 'e:\\hermes' || strtolower($clean) === strtolower(base_path())) {
            return redirect()->back()->with('error', 'Aksi ditolak: Direktori root sistem tidak boleh dihapus.');
        }

        $parent = dirname($path);
        $name = basename($path);

        try {
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
                $msg = "Folder '{$name}' berhasil dihapus.";
            } else {
                File::delete($path);
                $msg = "Berkas '{$name}' berhasil dihapus.";
            }

            \App\Models\GlobalActivityLog::create([
                'global_activity_action' => 'file_delete',
                'global_activity_description' => "Menghapus {$name} dari {$path}",
                'global_activity_status' => 'success',
            ]);

            return redirect()->route('files.index', ['path' => $parent])->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    private function buildBreadcrumbs(string $path): array
    {
        $parts = explode('\\', rtrim($path, '\\'));
        $breadcrumbs = [];
        $accumulator = '';

        foreach ($parts as $idx => $part) {
            if ($idx === 0) {
                $accumulator = $part . '\\';
                $breadcrumbs[] = [
                    'name' => $part ?: 'Root',
                    'path' => $accumulator,
                ];
            } else {
                $accumulator = rtrim($accumulator, '\\') . '\\' . $part;
                $breadcrumbs[] = [
                    'name' => $part,
                    'path' => $accumulator,
                ];
            }
        }

        return $breadcrumbs;
    }

    private function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $floor = floor($base);
        return round(pow(1024, $base - $floor), $precision) . ' ' . ($units[$floor] ?? 'B');
    }
}
