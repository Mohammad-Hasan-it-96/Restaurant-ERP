<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    /**
     * Display a listing of the languages.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $languages = Language::latest()->paginate(10);
        return view('admin.languages.index', compact('languages'));
    }

    /**
     * Show the form for creating a new language.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.languages.create');
    }

    /**
     * Store a newly created language in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Set default values for checkboxes if not present in request
        $request->merge([
            'status' => $request->has('status') ? 1 : 0,
            'is_default' => $request->has('is_default') ? 1 : 0,
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:languages'],
            'flag' => ['nullable', 'image', 'max:1024'],
            'status' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
            'direction' => ['required', 'in:ltr,rtl'],
        ]);

        // If this is set as default, unset all other defaults
        if ($request->is_default) {
            Language::where('is_default', 1)->update(['is_default' => 0]);
        }
        $language = new Language();
        $language->name = $request->name;
        $language->code = $request->code;
        $language->status = $request->status;
        $language->is_default = $request->is_default;
        $language->direction = $request->direction;

        if ($request->hasFile('flag')) {
            $path = $request->file('flag')->store('flags', 'public');
            $language->flag_path = $path;
        }

        $language->save();

        return redirect()->route('admin.languages.index')->with('success', 'Language created successfully');
    }

    /**
     * Show the form for editing the specified language.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $language = Language::findOrFail($id);
        return view('admin.languages.edit', compact('language'));
    }

    /**
     * Update the specified language in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $language = Language::findOrFail($id);

        // Set default values for checkboxes if not present in request
        $request->merge([
            'status' => $request->has('status') ? 1 : 0,
            'is_default' => $request->has('is_default') ? 1 : 0,
        ]);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', Rule::unique('languages')->ignore($language->id)],
            'flag' => ['nullable', 'image', 'max:1024'],
            'status' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
            'direction' => ['required', 'in:ltr,rtl'],
        ]);

        // If this is set as default, unset all other defaults
        if ($request->is_default) {
            Language::where('is_default', 1)->where('id', '!=', $language->id)->update(['is_default' => 0]);
        } else {
            // Ensure at least one language is default
            $defaultExists = Language::where('is_default', 1)->where('id', '!=', $language->id)->exists();
            if (!$defaultExists) {
                return redirect()->back()->with('error', 'At least one language must be set as default')->withInput();
            }
        }

        $language->name = $request->name;
        $language->code = $request->code;
        $language->status = $request->status;
        $language->is_default = $request->is_default;
        $language->direction = $request->direction;

        if ($request->hasFile('flag')) {
            // Delete old flag if exists
            if ($language->flag_path) {
                Storage::disk('public')->delete($language->flag_path);
            }

            // Store new flag
            $path = $request->file('flag')->store('flags', 'public');
            $language->flag_path = $path;
        }

        $language->save();

        return redirect()->route('admin.languages.index')->with('success', 'Language updated successfully');
    }

    /**
     * Remove the specified language from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $language = Language::findOrFail($id);

        // Prevent deleting the default language
        if ($language->is_default) {
            return redirect()->route('admin.languages.index')->with('error', 'Cannot delete the default language');
        }

        // Delete flag if exists
        if ($language->flag_path) {
            Storage::disk('public')->delete($language->flag_path);
        }

        $language->delete();

        return redirect()->route('admin.languages.index')->with('success', 'Language deleted successfully');
    }

    /**
     * Change the application language.
     *
     * @param  string  $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeLanguage($locale)
    {
        // Get the language from database
        $language = Language::where('code', $locale)
            ->where('status', 1)
            ->first();

        if (!$language) {
            // If language not found or not active, use default
            $defaultLanguage = Language::where('is_default', 1)->first();

            if ($defaultLanguage) {
                $locale = $defaultLanguage->code;
                session(['site_direction' => $defaultLanguage->direction]);
            } else {
                // If no default language is set, use English with LTR
                $locale = 'en';
                session(['site_direction' => 'ltr']);
            }
        } else {
            // Set the direction based on the selected language
            session(['site_direction' => $language->direction]);
        }

        // Set the application locale
        App::setLocale($locale);
        session(['locale' => $locale]);

        // Make sure the session is saved
        session()->save();

        return redirect()->back();
    }
}
