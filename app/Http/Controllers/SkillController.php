<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SkillController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $skills = $this->hermes->getSkills($profile);
        $toolsets = $this->hermes->getToolsets($profile);
        $categories = array_values(array_unique(array_filter(array_column($skills, 'category'))));
        
        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        return Inertia::render('Skills/Index', [
            'skills' => $skills,
            'toolsets' => $toolsets,
            'categories' => $categories,
            'totalCount' => count($skills),
            'profiles' => array_values($uniqueProfiles),
            'currentProfile' => $profile,
        ]);
    }

    public function show(Request $request, string $name)
    {
        $profile = $request->query('profile', 'default');
        $skill = $this->hermes->getSkill($name, $profile);

        if (!$skill) {
            return response()->json(['error' => 'Skill not found'], 404);
        }

        return response()->json($skill);
    }

    public function toggle(Request $request, string $name)
    {
        $profile = $request->input('profile', 'default');
        $enabled = (bool) $request->input('enabled', true);

        $ok = $this->hermes->toggleSkill($name, $enabled, $profile);
        return response()->json([
            'success' => $ok,
            'name' => $name,
            'enabled' => $enabled,
        ]);
    }

    public function lock(Request $request, string $name)
    {
        $profile = $request->input('profile', 'default');
        $locked = (bool) $request->input('locked', true);

        $ok = $this->hermes->lockSkill($name, $locked, $profile);
        return response()->json([
            'success' => $ok,
            'name' => $name,
            'locked' => $locked,
        ]);
    }

    public function save(Request $request, string $name)
    {
        $profile = $request->input('profile', 'default');
        $content = $request->input('content', '');
        $category = $request->input('category');

        $ok = $this->hermes->saveSkill($name, $content, $category, $profile);
        return response()->json([
            'success' => $ok,
            'name' => $name,
        ]);
    }

    public function hubSources(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $sources = $this->hermes->getHubSources($profile);
        return response()->json($sources);
    }

    public function hubOfficial(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $skills = $this->hermes->getOfficialHubSkills($profile);
        return response()->json(['skills' => $skills]);
    }

    public function hubSearch(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $q = (string) $request->query('q', '');
        $source = (string) $request->query('source', 'all');
        $limit = (int) $request->query('limit', 20);

        $results = $this->hermes->searchHubSkills($q, $source, $limit, $profile);
        return response()->json($results);
    }

    public function hubPreview(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $identifier = (string) $request->query('identifier', '');
        $preview = $this->hermes->previewHubSkill($identifier, $profile);

        if (!$preview) {
            return response()->json(['error' => 'Skill preview not found'], 404);
        }

        return response()->json($preview);
    }

    public function hubScan(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $identifier = (string) $request->query('identifier', '');
        $scan = $this->hermes->scanHubSkill($identifier, $profile);

        if (!$scan) {
            return response()->json(['error' => 'Skill scan not found'], 404);
        }

        return response()->json($scan);
    }

    public function hubUpdates(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $updates = $this->hermes->checkHubUpdates($profile);
        return response()->json(['updates' => $updates]);
    }

    public function hubInstall(Request $request)
    {
        $profile = $request->input('profile', 'default');
        $identifier = (string) $request->input('identifier', '');

        $ok = $this->hermes->installHubSkill($identifier, $profile);
        return response()->json(['success' => $ok, 'identifier' => $identifier]);
    }

    public function hubUninstall(Request $request)
    {
        $profile = $request->input('profile', 'default');
        $name = (string) $request->input('name', '');

        $ok = $this->hermes->uninstallHubSkill($name, $profile);
        return response()->json(['success' => $ok, 'name' => $name]);
    }

    public function hubUpdateAll(Request $request)
    {
        $profile = $request->input('profile', 'default');
        $ok = $this->hermes->updateHubSkills($profile);
        return response()->json(['success' => $ok]);
    }
}
