<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductSale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// Import OpenSpout for Excel
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Reader\CSV\Reader as CsvReader;

class ProductSaleController extends Controller
{
    // 1. Get List
    public function index()
    {
        $sales = ProductSale::with([
            'product' => function ($q) {
                $q->select('id', 'name', 'thumbnail', 'price_buy');
            }
        ])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $sales
        ]);
    }

    // 2. Batch Store (Modified to match the "Save" button in the UI)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'date_begin' => 'required|date',
            'date_end' => 'required|date|after:date_begin',
            'products' => 'required|array', // Expecting array of { product_id, price_sale }
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.price_sale' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $createdSales = [];

            foreach ($request->products as $item) {
                // Check if sale exists for this product in this timeframe? 
                // For simplicity, we create new or update existing if logic requires.
                // Here we simply create new records.

                $sale = ProductSale::create([
                    'name' => $request->name,
                    'product_id' => $item['product_id'],
                    'price_sale' => $item['price_sale'],
                    'date_begin' => $request->date_begin,
                    'date_end' => $request->date_end,
                    'status' => 1,
                    'created_by' => auth('api')->id() ?? 1
                ]);
                $createdSales[] = $sale;
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đã lưu chương trình khuyến mãi cho ' . count($createdSales) . ' sản phẩm',
                'data' => $createdSales
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 3. Update Single (Keep for individual edit if needed)
    public function update(Request $request, $id)
    {
        $sale = ProductSale::find($id);
        if (!$sale)
            return response()->json(['status' => false, 'message' => 'Not found'], 404);

        $sale->update($request->all());
        return response()->json(['status' => true, 'message' => 'Update success', 'data' => $sale]);
    }

    // 4. Delete
    public function destroy($id)
    {
        $sale = ProductSale::find($id);
        if ($sale)
            $sale->delete();
        return response()->json(['status' => true, 'message' => 'Deleted success']);
    }

    // 5. Import Excel (New Feature)
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getPathname();

        $reader = ($extension === 'csv') ? new CsvReader() : new XlsxReader();
        $reader->open($filePath);

        $importedData = [];
        $isHeader = true;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }
                $cells = $row->toArray();

                // Expected Format: [0] Product ID, [1] Sale Price
                if (count($cells) < 2)
                    continue;

                $productId = $cells[0];
                $salePrice = $cells[1];

                // Validate product existence
                $product = Product::find($productId);
                if ($product) {
                    $importedData[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'price_buy' => $product->price_buy,
                        'price_sale' => $salePrice,
                        'thumbnail' => $product->thumbnail
                    ];
                }
            }
            break; // Only read first sheet
        }
        $reader->close();

        return response()->json([
            'status' => true,
            'message' => 'Đọc file thành công',
            'data' => $importedData
        ]);
    }
    // --- NEW: Batch Store/Update (Upsert) ---
    public function storeBatch(Request $request)
    {
        // 1. Validate dữ liệu đầu vào cơ bản
        $request->validate([
            'name' => 'required|string',
            'date_begin' => 'required|date',
            'date_end' => 'required|date|after:date_begin',
            'products' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $newStart = $request->date_begin;
            $newEnd = $request->date_end;

            foreach ($request->products as $item) {
                $productId = $item['product_id'];
                $saleId = isset($item['sale_id']) ? $item['sale_id'] : null;

                // --- CHECK RÀNG BUỘC THỜI GIAN & TRÙNG LẶP ---
                // Logic: Tìm xem có bản ghi nào khác của Product này
                // mà thời gian của nó giao nhau với [newStart, newEnd] hay không.
                // Điều kiện giao nhau: (StartA < EndB) AND (EndA > StartB)

                $conflictingSale = ProductSale::where('product_id', $productId)
                    ->where(function ($query) use ($newStart, $newEnd) {
                        $query->where('date_begin', '<', $newEnd)
                            ->where('date_end', '>', $newStart);
                    });

                // Nếu là update (có sale_id), phải loại trừ chính nó ra khỏi check
                if ($saleId) {
                    $conflictingSale->where('id', '!=', $saleId);
                }

                $conflict = $conflictingSale->first();

                if ($conflict) {
                    // Lấy tên sản phẩm để thông báo lỗi cho rõ ràng
                    $productName = Product::find($productId)->name ?? "ID: $productId";

                    // Rollback ngay lập tức
                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' => "Sản phẩm '{$productName}' đang chạy khuyến mãi khác trong khung giờ này (từ {$conflict->date_begin} đến {$conflict->date_end}). Vui lòng kiểm tra lại!"
                    ], 409); // 409 Conflict
                }
                // --- KẾT THÚC CHECK ---

                // Chuẩn bị dữ liệu
                $data = [
                    'name' => $request->name,
                    'date_begin' => $newStart,
                    'date_end' => $newEnd,
                    'price_sale' => $item['price_sale'],
                    'product_id' => $productId,
                    'status' => 1,
                    'updated_by' => auth('api')->id() ?? 1
                ];

                if ($saleId) {
                    ProductSale::where('id', $saleId)->update($data);
                } else {
                    $data['created_by'] = auth('api')->id() ?? 1;
                    ProductSale::create($data);
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Lưu chương trình thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
        }
    }
}