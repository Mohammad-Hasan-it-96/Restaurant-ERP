<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Option::withCount('values');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $options = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.options.index', compact('options'));
    }

    public function create()
    {
        return view('admin.options.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:options,name',
            'is_active' => 'nullable|boolean',
            'values' => 'nullable|array',
            'values.*.name' => 'required|string|max:255',
            'values.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $option = Option::create([
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        foreach ($validated['values'] ?? [] as $i => $valueData) {
            if (trim($valueData['name']) === '') {
                continue;
            }
            $option->values()->create([
                'name' => $valueData['name'],
                'sort_order' => $valueData['sort_order'] ?? $i,
            ]);
        }

        return redirect()->route('admin.options.index')
            ->with('success', 'تم إضافة الخيار بنجاح.');
    }

    public function edit(Option $option)
    {
        $option->load('values');

        return view('admin.options.edit', compact('option'));
    }

    public function update(Request $request, Option $option)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:options,name,'.$option->id,
            'is_active' => 'nullable|boolean',
            'values' => 'nullable|array',
            'values.*.id' => 'nullable|integer|exists:option_values,id',
            'values.*.name' => 'required|string|max:255',
            'values.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $option->update([
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $submittedIds = [];

        foreach ($validated['values'] ?? [] as $i => $valueData) {
            if (trim($valueData['name']) === '') {
                continue;
            }

            $id = $valueData['id'] ?? null;

            if ($id) {
                $value = OptionValue::where('option_id', $option->id)->find($id);
                if ($value) {
                    $value->update([
                        'name' => $valueData['name'],
                        'sort_order' => $valueData['sort_order'] ?? $i,
                    ]);
                    $submittedIds[] = $value->id;
                }
            } else {
                $value = $option->values()->create([
                    'name' => $valueData['name'],
                    'sort_order' => $valueData['sort_order'] ?? $i,
                ]);
                $submittedIds[] = $value->id;
            }
        }

        // Delete values that were removed
        $option->values()->whereNotIn('id', $submittedIds)->delete();

        return redirect()->route('admin.options.index')
            ->with('success', 'تم تحديث الخيار بنجاح.');
    }

    public function destroy(Option $option)
    {
        $option->delete();

        return redirect()->route('admin.options.index')
            ->with('success', 'تم حذف الخيار بنجاح.');
    }
}
