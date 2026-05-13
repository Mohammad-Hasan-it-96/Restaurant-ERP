<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProductController extends Controller
{
    private function authorizeProductAction($product, $action): void
    {
        if (! Gate::allows($action, $product)) {
            throw new AccessDeniedHttpException('You do not have permission to ' . $action . ' this product.');
        }
    }

    public function index(Request $request)
    {
        $query = Product::with(['user', 'category']);

        // Search by name_ar or name_en
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $allowed   = ['id', 'name_ar', 'price', 'discount_price', 'sort_order', 'created_at'];
        $sortBy    = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $products   = $query->orderBy($sortBy, $direction)->paginate(15)->withQueryString();
        $categories = Category::orderBy('name_ar')->get();

        // Keep legacy users list for export dropdown
        $users = \App\Models\User::all();

        return view('admin.products.index', compact('products', 'categories', 'users'));
    }

    public function create()
    {
        $this->authorizeProductAction(Product::class, 'create');
        $categories = Category::orderBy('name_ar')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorizeProductAction(Product::class, 'create');

        $validated = $request->validate([
            'name_ar'        => 'required|string|max:255',
            'name_en'        => 'nullable|string|max:255',
            'price'          => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'category_id'    => 'nullable|exists:categories,id',
            'image'          => 'nullable|image|max:2048',
            'is_available'   => 'boolean',
            'is_featured'    => 'boolean',
            'sort_order'     => 'nullable|integer',
            'is_active'      => 'boolean',
            // Legacy fields (optional)
            'name'           => 'nullable|string|max:255',
            'details'        => 'nullable|string',
            'quantity'       => 'nullable|integer|min:0',
        ]);

        // Handle checkboxes
        $validated['is_available'] = $request->boolean('is_available');
        $validated['is_featured']  = $request->boolean('is_featured');
        $validated['is_active']    = $request->boolean('is_active');

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // Fallback legacy name
        if (empty($validated['name'])) {
            $validated['name'] = $validated['name_ar'];
        }

        // Ensure legacy quantity always has a value (DB column has no default)
        $validated['quantity'] = $validated['quantity'] ?? 0;

        $product          = new Product($validated);
        $product->user_id = Auth::id();
        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('success', __('app.product_created'));
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeProductAction($product, 'update');
        $categories = Category::orderBy('name_ar')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeProductAction($product, 'update');

        $validated = $request->validate([
            'name_ar'        => 'required|string|max:255',
            'name_en'        => 'nullable|string|max:255',
            'price'          => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'category_id'    => 'nullable|exists:categories,id',
            'image'          => 'nullable|image|max:2048',
            'is_available'   => 'boolean',
            'is_featured'    => 'boolean',
            'sort_order'     => 'nullable|integer',
            'is_active'      => 'boolean',
            // Legacy fields
            'name'           => 'nullable|string|max:255',
            'details'        => 'nullable|string',
            'quantity'       => 'nullable|integer|min:0',
        ]);

        $validated['is_available'] = $request->boolean('is_available');
        $validated['is_featured']  = $request->boolean('is_featured');
        $validated['is_active']    = $request->boolean('is_active');

        // Handle image upload – delete old one
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // Sync legacy name
        if (empty($validated['name'])) {
            $validated['name'] = $validated['name_ar'];
        }

        // Ensure legacy quantity always has a value
        $validated['quantity'] = $validated['quantity'] ?? $product->quantity ?? 0;

        $product->update($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', __('app.product_updated'));
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeProductAction($product, 'delete');

        // Delete image from storage
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', __('app.product_deleted'));
    }

    /* ── Keep legacy methods for Excel import/export ──────────────── */

    public function export(Request $request)
    {
        // Delegate to the API controller which has the full Excel logic
        return app(\App\Http\Controllers\API\ProductController::class)->export($request);
    }

    public function import()
    {
        return view('admin.products.import');
    }

    public function downloadTemplate()
    {
        return app(\App\Http\Controllers\API\ProductController::class)->downloadTemplate();
    }

    public function processImport(Request $request)
    {
        return app(\App\Http\Controllers\API\ProductController::class)->processImport($request);
    }
}

