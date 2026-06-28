<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with search & filter.
     */
    public function index(Request $request)
    {
        $query = Category::with('parent');

        // Search by Arabic or English name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        // Filter by parent_id
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        $allowed = ['id', 'name_ar', 'name_en', 'sort_order', 'created_at'];
        $sortBy = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $categories = $query->orderBy($sortBy, $direction)->paginate(config('pagination.categories'))->withQueryString();

        // For filter dropdown
        $parentCategories = Category::whereNull('parent_id')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        $parentCategories = Category::query()->whereNull('parent_id')->orderBy('sort_order')->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', __('app.category_created'));
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Request $request, Category $category)
    {
        // Exclude the category itself from the parent list (prevent self-reference)
        $parentCategories = Category::query()->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->get();

        $back = $request->headers->get('referer', route('admin.categories.index'));

        return view('admin.categories.edit', compact('category', 'parentCategories', 'back'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Prevent setting a category as its own parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return back()->withErrors(['parent_id' => __('app.category_cannot_be_own_parent')])->withInput();
        }

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($validated);

        $back = $request->input('_back', route('admin.categories.index'));

        return redirect($back)->with('success', __('app.category_updated'));
    }

    /**
     * Delete the specified category.
     * Blocks deletion if it has children or products.
     */
    public function destroy(Category $category)
    {
        // Prevent delete if category has children
        if ($category->children()->count() > 0) {
            return back()->with('error', __('app.category_has_children'));
        }

        // Prevent delete if category has products
        if ($category->products()->count() > 0) {
            return back()->with('error', __('app.category_has_products'));
        }

        // Delete image from storage
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', __('app.category_deleted'));
    }
}
