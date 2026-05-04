<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProductController extends Controller
{
    public function __construct()
    {
        // Remove the middleware call and handle auth in routes
    }

    // Custom method to check authorization
    private function authorizeProductAction($product, $action)
    {
        if (!Gate::allows($action, $product)) {
            throw new AccessDeniedHttpException('You do not have permission to ' . $action . ' this product.');
        }
    }

    public function index(Request $request)
    {
        // Get the user filter if provided
        $userId = $request->input('user_id');

        // Query products with optional user filter
        $query = Product::with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $products = $query->get();

        // Get all users for the filter dropdown
        $users = \App\Models\User::all();

        return view('admin.products.index', compact('products', 'users'));
    }

    // Add the export method
    public function export(Request $request)
    {
        $userId = $request->input('user_id');
        $currentUser = Auth::user();

        // Start query with user relationship
        $query = Product::with('user');

        // If user is moderator, they can only export their own products
        if ($currentUser->role === 'moderator') {
            $query->where('user_id', $currentUser->id);
            // Ignore any user_id filter for moderators
        }
        // For admins, apply the user filter if provided
        elseif ($currentUser->role === 'admin' && $userId) {
            $query->where('user_id', $userId);
        }

        $products = $query->get();

        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Price');
        $sheet->setCellValue('D1', 'Details');
        $sheet->setCellValue('E1', 'Quantity');
        $sheet->setCellValue('F1', 'Created By');
        $sheet->setCellValue('G1', 'Created At');

        // Style the header row
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        // Add data rows
        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->id);
            $sheet->setCellValue('B' . $row, $product->name);
            $sheet->setCellValue('C' . $row, $product->price);
            $sheet->setCellValue('D' . $row, $product->details);
            $sheet->setCellValue('E' . $row, $product->quantity);
            $sheet->setCellValue('F' . $row, $product->user->name ?? 'Unknown');
            $sheet->setCellValue('G' . $row, $product->created_at->format('Y-m-d H:i:s'));
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create the file
        $writer = new Xlsx($spreadsheet);
        $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Save to temp file and return as download
        $tempFile = tempnam(sys_get_temp_dir(), 'products_export');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function create()
    {
        // Check if user can create products
        $this->authorizeProductAction(Product::class, 'create');
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        // Check if user can create products
        $this->authorizeProductAction(Product::class, 'create');

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'details' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);
        // Create the product and associate it with the current user
        $product = new Product($validated);
        $product->user_id = Auth::id();  // Important: Associate the product with the current user
        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        // Check if user can update this product
        $this->authorizeProductAction($product, 'update');

        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        // Check if user can update this product
        $this->authorizeProductAction($product, 'update');

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'details' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        // Check if user can delete this product
        $this->authorizeProductAction($product, 'delete');

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    // Add these methods to your ProductController

    public function import()
    {
        return view('admin.products.import');
    }

    public function downloadTemplate()
    {
        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'Price');
        $sheet->setCellValue('C1', 'Details');
        $sheet->setCellValue('D1', 'Quantity');

        // Style the header row
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        // Add example row
        $sheet->setCellValue('A2', 'Example Product');
        $sheet->setCellValue('B2', '99.99');
        $sheet->setCellValue('C2', 'This is a sample product description.');
        $sheet->setCellValue('D2', '10');

        // Auto size columns
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create the file
        $writer = new Xlsx($spreadsheet);
        $filename = 'products_import_template.xlsx';

        // Save to temp file and return as download
        $tempFile = tempnam(sys_get_temp_dir(), 'products_template');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function processImport(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            // Load the file
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            array_shift($rows);

            $importCount = 0;
            $errors = [];

            // Process each row
            foreach ($rows as $index => $row) {
                // Skip empty rows
                if (empty($row[0]) && empty($row[1]) && empty($row[2]) && empty($row[3])) {
                    continue;
                }

                // Validate row data
                $rowNumber = $index + 2;  // +2 because we removed header and arrays are 0-indexed

                if (empty($row[0])) {
                    $errors[] = "Row {$rowNumber}: Name is required";
                    continue;
                }

                if (!is_numeric($row[1]) || $row[1] < 0) {
                    $errors[] = "Row {$rowNumber}: Price must be a positive number";
                    continue;
                }

                if (empty($row[2])) {
                    $errors[] = "Row {$rowNumber}: Details are required";
                    continue;
                }

                if (!is_numeric($row[3]) || $row[3] < 0 || !is_int((int) $row[3])) {
                    $errors[] = "Row {$rowNumber}: Quantity must be a positive integer";
                    continue;
                }

                // Create the product
                $product = new Product([
                    'name' => $row[0],
                    'price' => $row[1],
                    'details' => $row[2],
                    'quantity' => (int) $row[3],
                ]);

                $product->user_id = Auth::id();
                $product->save();

                $importCount++;
            }

            // Return with appropriate message
            if (count($errors) > 0) {
                return redirect()
                    ->route('admin.products.import')
                    ->with('error', 'Import completed with errors. ' . $importCount . ' products imported.')
                    ->with('import_errors', $errors);
            }

            return redirect()
                ->route('admin.products.import')
                ->with('success', 'Successfully imported ' . $importCount . ' products.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.products.import')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }
}
