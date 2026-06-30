<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Option;
use App\Models\Product;
use App\Models\Weight;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProductController extends Controller
{
    private function authorizeProductAction($product, $action): void
    {
        if (! Gate::allows($action, $product)) {
            throw new AccessDeniedHttpException('You do not have permission to '.$action.' this product.');
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
            $categories_ids = Category::query()->select('id')
                ->where('id', $categoryId)
                ->orWhere('parent_id', $categoryId)->get()->toArray();
            $query->whereIn('category_id', $categories_ids);
        }
        // Filter by activation
        $is_available = $request->input('is_available');
        if ($is_available !== null && $is_available !== 'All') {
            $query->where('is_available', $is_available);
        }

        $allowed = ['id', 'name_ar', 'price', 'discount_price', 'sort_order', 'created_at'];
        $sortBy = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $products = $query->orderBy($sortBy, $direction)->paginate(config('pagination.products'))->withQueryString();
        $categories = Category::query()->orderBy('name_ar')->get();

        // Keep legacy users list for export dropdown
        $users = \App\Models\User::all();

        return view('admin.products.index', compact('products', 'categories', 'users'));
    }

    public function create()
    {
        $this->authorizeProductAction(Product::class, 'create');
        $categories = Category::query()->orderBy('name_ar')->get();
        $weights = Weight::active()->orderBy('sort_order')->get();
        $options = Option::active()->with('values')->orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'weights', 'options'));
    }

    public function store(Request $request)
    {
        $this->authorizeProductAction(Product::class, 'create');

        $isWeightBased = $request->boolean('is_weight_based');

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'price' => $isWeightBased ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'price_per_kg' => $isWeightBased ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'is_weight_based' => 'boolean',
            'weights' => 'nullable|array',
            'weights.*' => 'exists:weights,id',
            'option_values' => 'nullable|array',
            'option_values.*' => 'exists:option_values,id',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            // Legacy fields (optional)
            'name' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'quantity' => 'nullable|integer|min:0',
        ]);

        // Handle checkboxes
        $validated['is_available'] = $request->boolean('is_available');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_weight_based'] = $isWeightBased;

        // For weight-based products use price_per_kg as the base price too
        if ($isWeightBased) {
            $validated['price'] = $validated['price_per_kg'];
        }

        // Handle image upload (compressed + downscaled on the way in)
        if ($request->hasFile('image')) {
            $validated['image'] = app(ImageService::class)->store($request->file('image'), 'products');
        }

        // Fallback legacy name
        if (empty($validated['name'])) {
            $validated['name'] = $validated['name_ar'];
        }

        // Ensure legacy quantity always has a value (DB column has no default)
        $validated['quantity'] = $validated['quantity'] ?? 0;

        $product = new Product($validated);
        $product->user_id = Auth::id();
        $product->save();

        $product->weights()->sync($isWeightBased ? ($validated['weights'] ?? []) : []);
        $product->optionValues()->sync($isWeightBased ? ($validated['option_values'] ?? []) : []);

        activity()->log('product.created', $product, 'Product created: '.$product->name_ar);

        return redirect()
            ->route('admin.products.index')
            ->with('success', __('app.product_created'));
    }

    public function edit(Request $request, $id)
    {
        $product = Product::with('weights', 'optionValues')->findOrFail($id);
        $this->authorizeProductAction($product, 'update');
        $categories = Category::query()->orderBy('name_ar')->get();
        $weights = Weight::active()->orderBy('sort_order')->get();
        $selectedWeights = $product->weights->pluck('id')->toArray();
        $options = Option::active()->with('values')->orderBy('name')->get();
        $selectedOptionValues = $product->optionValues->pluck('id')->toArray();

        // Remember which page of the index the user came from so we can return there after save
        $back = $request->headers->get('referer', route('admin.products.index'));

        return view('admin.products.edit', compact(
            'product', 'categories', 'weights', 'selectedWeights',
            'options', 'selectedOptionValues', 'back'
        ));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeProductAction($product, 'update');

        $isWeightBased = $request->boolean('is_weight_based');

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'price' => $isWeightBased ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'price_per_kg' => $isWeightBased ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'is_weight_based' => 'boolean',
            'weights' => 'nullable|array',
            'weights.*' => 'exists:weights,id',
            'option_values' => 'nullable|array',
            'option_values.*' => 'exists:option_values,id',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            // Legacy fields
            'name' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $validated['is_available'] = $request->boolean('is_available');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_weight_based'] = $isWeightBased;

        if ($isWeightBased) {
            $validated['price'] = $validated['price_per_kg'];
        }

        // Handle image upload – delete old one
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = app(ImageService::class)->store($request->file('image'), 'products');
        }

        // Sync legacy name
        if (empty($validated['name'])) {
            $validated['name'] = $validated['name_ar'];
        }

        // Ensure legacy quantity always has a value
        $validated['quantity'] = $validated['quantity'] ?? $product->quantity ?? 0;

        $product->update($validated);

        $product->weights()->sync($isWeightBased ? ($validated['weights'] ?? []) : []);
        $product->optionValues()->sync($isWeightBased ? ($validated['option_values'] ?? []) : []);

        activity()->log('product.updated', $product, 'Product updated: '.$product->name_ar);

        $back = $request->input('_back', route('admin.products.index'));

        return redirect($back)->with('success', __('app.product_updated'));
    }

    public function toggleAvailability(Product $product)
    {
        $product->update(['is_available' => ! $product->is_available]);

        return redirect()->back()
            ->with('success', __('app.product_availability_updated'));
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

        activity()->log('product.deleted', $product, 'Product deleted: '.$product->name_ar);

        return redirect()
            ->route('admin.products.index')
            ->with('success', __('app.product_deleted'));
    }

    /* ── Excel Import / Export ───────────────────────────────────── */

    public function import()
    {
        return view('admin.products.import');
    }

    public function export(Request $request)
    {
        // Only the columns the export actually writes (no category relation — the
        // sheet emits the raw category_id). Chunked below to bound memory.
        $exportColumns = ['id', 'name_ar', 'name_en', 'description_ar', 'description_en',
            'price', 'discount_price', 'category_id', 'is_available', 'is_featured', 'is_active', 'sort_order'];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        // ── Headers ───────────────────────────────────────────────
        $headers = [
            'A' => 'name_ar',
            'B' => 'name_en',
            'C' => 'description_ar',
            'D' => 'description_en',
            'E' => 'price',
            'F' => 'discount_price',
            'G' => 'category_id',
            'H' => 'is_available',
            'I' => 'is_featured',
            'J' => 'is_active',
            'K' => 'sort_order',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        // Style the header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Auto-width
        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Data rows (chunked so a large catalogue isn't all hydrated at once) ──
        $row = 2;
        Product::select($exportColumns)->orderBy('sort_order')->orderBy('id')
            ->chunk(1000, function ($products) use ($sheet, &$row) {
                foreach ($products as $product) {
                    $sheet->setCellValue("A{$row}", $product->name_ar);
                    $sheet->setCellValue("B{$row}", $product->name_en);
                    $sheet->setCellValue("C{$row}", $product->description_ar);
                    $sheet->setCellValue("D{$row}", $product->description_en);
                    $sheet->setCellValue("E{$row}", $product->price);
                    $sheet->setCellValue("F{$row}", $product->discount_price);
                    $sheet->setCellValue("G{$row}", $product->category_id);
                    $sheet->setCellValue("H{$row}", $product->is_available ? 1 : 0);
                    $sheet->setCellValue("I{$row}", $product->is_featured ? 1 : 0);
                    $sheet->setCellValue("J{$row}", $product->is_active ? 1 : 0);
                    $sheet->setCellValue("K{$row}", $product->sort_order);
                    $row++;
                }
            });

        $filename = 'products_export_'.now()->format('Y-m-d').'.xlsx';

        return $this->streamXlsx($spreadsheet, $filename);
    }

    /* ── Download Template ───────────────────────────────────────── */

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products Import Template');

        $headers = [
            'A' => 'name_ar',
            'B' => 'name_en',
            'C' => 'description_ar',
            'D' => 'description_en',
            'E' => 'price',
            'F' => 'discount_price',
            'G' => 'category_id',
            'H' => 'is_available',
            'I' => 'is_featured',
            'J' => 'is_active',
            'K' => 'sort_order',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Example row
        $sheet->setCellValue('A2', 'برجر كلاسيك');
        $sheet->setCellValue('B2', 'Classic Burger');
        $sheet->setCellValue('C2', 'وصف المنتج بالعربية');
        $sheet->setCellValue('D2', 'Product description in English');
        $sheet->setCellValue('E2', '25.00');
        $sheet->setCellValue('F2', '');               // optional
        $sheet->setCellValue('G2', '');               // optional category_id
        $sheet->setCellValue('H2', '1');              // 1=available, 0=not
        $sheet->setCellValue('I2', '0');              // 1=featured
        $sheet->setCellValue('J2', '1');              // 1=active
        $sheet->setCellValue('K2', '0');              // sort order

        // Style example row
        $sheet->getStyle('A2:K2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
        ]);

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->streamXlsx($spreadsheet, 'products_import_template.xlsx');
    }

    /* ── Process Import ──────────────────────────────────────────── */

    public function processImport(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        try {
            $path = $request->file('excel_file')->getRealPath();
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);  // assoc by column letter
        } catch (\Throwable $e) {
            logService()->error('product.import.read_failed', [], $e);

            return back()->with('error', __('app.import_file_error').': '.$e->getMessage());
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $lineNo => $row) {
            // Skip header row
            if ($lineNo === 1) {
                continue;
            }

            $nameAr = trim((string) ($row['A'] ?? ''));

            // Skip completely empty rows
            if ($nameAr === '') {
                $skipped++;

                continue;
            }

            $price = (float) ($row['E'] ?? 0);
            if ($price <= 0) {
                $errors[] = "Row {$lineNo}: name_ar='{$nameAr}' skipped — price must be > 0";
                $skipped++;

                continue;
            }

            $categoryId = (int) ($row['G'] ?? 0) ?: null;

            try {
                Product::updateOrCreate(
                    ['name_ar' => $nameAr],
                    [
                        'name' => $nameAr,
                        'name_en' => trim((string) ($row['B'] ?? '')) ?: null,
                        'description_ar' => trim((string) ($row['C'] ?? '')) ?: null,
                        'description_en' => trim((string) ($row['D'] ?? '')) ?: null,
                        'price' => $price,
                        'discount_price' => $row['F'] !== '' && $row['F'] !== null ? (float) $row['F'] : null,
                        'category_id' => $categoryId,
                        'is_available' => (bool) ($row['H'] ?? 1),
                        'is_featured' => (bool) ($row['I'] ?? 0),
                        'is_active' => (bool) ($row['J'] ?? 1),
                        'sort_order' => (int) ($row['K'] ?? 0),
                        'quantity' => 0,
                        'user_id' => Auth::id(),
                    ]
                );
                $imported++;
            } catch (\Throwable $e) {
                logService()->warning('product.import.row_failed', ['row' => $lineNo], $e);
                $errors[] = "Row {$lineNo}: '{$nameAr}' — ".$e->getMessage();
            }
        }

        $message = __('app.import_success_count', ['count' => $imported]);
        if ($skipped) {
            $message .= ' '.__('app.import_skipped_count', ['count' => $skipped]);
        }
        if (! empty($errors)) {
            return back()
                ->with('success', $message)
                ->with('error', implode('<br>', $errors));
        }

        return back()->with('success', $message);
    }

    /* ── Shared XLSX stream helper ───────────────────────────────── */

    private function streamXlsx(Spreadsheet $spreadsheet, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }
}
