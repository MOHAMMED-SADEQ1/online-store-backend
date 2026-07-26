<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->groupBy('group');

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings'            => 'required|array',
            'settings.*.key'      => 'required|string|max:100',
            'settings.*.value'    => 'required|string',
            'settings.*.group'    => 'sometimes|string|max:50',
        ]);

        foreach ($data['settings'] as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value'], 'group' => $item['group'] ?? 'general']
            );
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
