<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    /**
     * Display a listing of all system configurations.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $configs = SystemConfig::orderBy('group', 'asc')
            ->orderBy('key', 'asc')
            ->get();

        $groups = SystemConfig::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        return view('admin.configs.index', compact('configs', 'groups'));
    }

    /**
     * Display configurations for a specific group.
     *
     * @param string $group
     * @return \Illuminate\View\View
     */
    public function group($group)
    {
        $configs = SystemConfig::where('group', $group)
            ->orderBy('key', 'asc')
            ->get();

        if ($configs->isEmpty()) {
            return redirect()
                ->route('admin.configs.index')
                ->with('error', __('app.group_not_found'));
        }

        return view('admin.configs.group', compact('configs', 'group'));
    }

    /**
     * Update system configurations.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $data = $request->except('_token', '_method');

        foreach ($data as $key => $value) {
            // Skip keys that don't start with 'config_'
            if (!str_starts_with($key, 'config_')) {
                continue;
            }

            // Extract the actual config key
            $configKey = substr($key, 7);

            // Get the config to update
            $config = SystemConfig::where('key', $configKey)->first();

            if ($config) {
                $config->value = $value;
                $config->save();

                // Clear the cache for this config
                SystemConfig::clearCache($configKey);
            }
        }

        return redirect()->back()->with('success', __('app.configs_updated'));
    }

    /**
     * Create a new system configuration.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:system_configs',
            'value' => 'nullable|string',
            'group' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        SystemConfig::create([
            'key' => $request->key,
            'value' => $request->value,
            'group' => $request->group,
        ]);

        return redirect()
            ->route('admin.configs.group', $request->group)
            ->with('success', __('app.config_created'));
    }

    /**
     * Delete a system configuration.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $config = SystemConfig::findOrFail($id);
        $group = $config->group;

        // Clear the cache for this config
        SystemConfig::clearCache($config->key);

        $config->delete();

        return redirect()
            ->route('admin.configs.group', $group)
            ->with('success', __('app.config_deleted'));
    }
}
