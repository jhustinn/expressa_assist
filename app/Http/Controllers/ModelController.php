<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $modelInfo = $this->hermes->getModelInfo($profile);
        $modelOptions = $this->hermes->getModelOptions($profile);

        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        return Inertia::render('Models/Index', [
            'modelInfo' => $modelInfo,
            'modelOptions' => $modelOptions,
            'profiles' => array_values($uniqueProfiles),
            'currentProfile' => $profile,
        ]);
    }

    public function set(Request $request)
    {
        $profile = $request->input('profile', 'default');
        $res = $this->hermes->setModelAssignment($request->all(), $profile);
        return response()->json($res ?? ['ok' => false]);
    }
}
