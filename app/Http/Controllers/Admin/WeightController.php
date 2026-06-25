<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Weight;
use Illuminate\Http\Request;

class WeightController extends Controller
{
    public function index(Request $request)
    {
        $query = Weight::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $allowed = ['id', 'name', 'value_kg', 'sort_order'];
        $sortBy = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'sort_order';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $weights = $query->orderBy($sortBy, $direction)->paginate(20)->withQueryString();

        return view('admin.weights.index', compact('weights'));
    }

    public function create()
    {
        return view('admin.weights.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value_kg' => 'required|numeric|min:0.001',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        Weight::create($validated);

        return redirect()->route('admin.weights.index')
            ->with('success', __('app.weight_created'));
    }

    public function edit(Request $request, Weight $weight)
    {
        $back = $request->headers->get('referer', route('admin.weights.index'));

        return view('admin.weights.edit', compact('weight', 'back'));
    }

    public function update(Request $request, Weight $weight)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value_kg' => 'required|numeric|min:0.001',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $weight->update($validated);

        $back = $request->input('_back', route('admin.weights.index'));

        return redirect($back)->with('success', __('app.weight_updated'));
    }

    public function destroy(Weight $weight)
    {
        $weight->delete();

        return redirect()->route('admin.weights.index')
            ->with('success', __('app.weight_deleted'));
    }
}
