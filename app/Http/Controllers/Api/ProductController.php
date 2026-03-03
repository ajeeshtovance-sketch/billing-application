<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected function getOrgId(): ?int
    {
        return auth('api')->user()?->organization_id;
    }

    protected function productQuery()
    {
        $orgId = $this->getOrgId();
        if (! $orgId) {
            return null;
        }

        return Item::where('organization_id', $orgId);
    }

    /**
     * List products/items with search, pagination, sort, and low-stock filter.
     *
     * @OA\Get(
     *     path="/products",
     *     tags={"Products"},
     *     summary="List Products",
     *     description="Get paginated list of products/items. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="search", in="query", description="Search by name, sku, barcode", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"name","sku","price","stock_quantity","created_at"}, default="created_at")),
     *     @OA\Parameter(name="sort_order", in="query", @OA\Schema(type="string", enum={"asc","desc"}, default="desc")),
     *     @OA\Parameter(name="low_stock", in="query", description="Filter only low stock items", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="item_type", in="query", description="Filter by type", @OA\Schema(type="string", enum={"product","service"})),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->productQuery();
        if (! $query) {
            return response()->json([
                'data' => [],
                'meta' => ['total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'from' => null, 'to' => null, 'path' => '', 'first_page_url' => '', 'last_page_url' => '', 'prev_page_url' => null, 'next_page_url' => null],
            ]);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->where('low_stock_alert', '>', 0)
                ->where('item_type', 'product');
        }

        if ($type = $request->input('item_type')) {
            $query->where('item_type', $type);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSort = ['name', 'sku', 'price', 'stock_quantity', 'created_at'];
        if (in_array($sortBy, $allowedSort)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginated = $query->with('category')->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'path' => $paginated->path(),
                'first_page_url' => $paginated->url(1),
                'last_page_url' => $paginated->url($paginated->lastPage()),
                'prev_page_url' => $paginated->previousPageUrl(),
                'next_page_url' => $paginated->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Product summary (stock value, low stock count).
     *
     * @OA\Get(
     *     path="/products/summary",
     *     tags={"Products"},
     *     summary="Product Summary",
     *     description="Get stock value and low stock count. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="stock_value", type="number"),
     *         @OA\Property(property="low_stock_count", type="integer"),
     *         @OA\Property(property="total_items", type="integer")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function summary(): JsonResponse
    {
        $query = $this->productQuery();
        if (! $query) {
            return response()->json([
                'stock_value' => 0,
                'low_stock_count' => 0,
                'total_items' => 0,
            ]);
        }

        $items = $query->where('item_type', 'product')->get();
        $stockValue = $items->sum(fn ($i) => (float) $i->stock_quantity * (float) ($i->cost ?? $i->price));
        $lowStockCount = $items->filter(fn ($i) => $i->low_stock_alert > 0 && $i->stock_quantity <= $i->low_stock_alert)->count();

        return response()->json([
            'stock_value' => round($stockValue, 2),
            'low_stock_count' => $lowStockCount,
            'total_items' => $items->count(),
        ]);
    }

    /**
     * Get single product details.
     *
     * @OA\Get(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Get Product",
     *     description="Get product/item details. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $product): JsonResponse
    {
        $item = $this->productQuery()?->with('category')->find($product);
        if (! $item) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json(['data' => $item]);
    }

    /**
     * Add a new product (item).
     *
     * @OA\Post(
     *     path="/products",
     *     tags={"Products"},
     *     summary="Add Product",
     *     description="Create a new product/item. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Widget A"),
     *             @OA\Property(property="sku", type="string", example="WDG-001"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="price", type="number", example=99.99),
     *             @OA\Property(property="cost", type="number"),
     *             @OA\Property(property="stock_quantity", type="number", example=0),
     *             @OA\Property(property="low_stock_alert", type="number", example=5),
     *             @OA\Property(property="unit", type="string", example="each"),
     *             @OA\Property(property="tax_rate", type="number", example=0),
     *             @OA\Property(property="category_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $orgId = $this->getOrgId();
        if (! $orgId) {
            return response()->json(['message' => 'Organization required'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:20'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['nullable', 'string', 'in:product,service'],
        ]);

        $validated['organization_id'] = $orgId;
        $validated['item_type'] = $validated['item_type'] ?? 'product';
        $validated['price'] = $validated['price'] ?? 0;
        $validated['stock_quantity'] = $validated['stock_quantity'] ?? 0;
        $validated['low_stock_alert'] = $validated['low_stock_alert'] ?? 0;
        $validated['unit'] = $validated['unit'] ?? 'each';
        $validated['tax_rate'] = $validated['tax_rate'] ?? 0;

        if (! empty($validated['category_id'])) {
            $cat = \App\Models\Category::where('organization_id', $orgId)->find($validated['category_id']);
            if (! $cat) {
                $validated['category_id'] = null;
            }
        }

        $product = Item::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    /**
     * Update product (edit item/service).
     *
     * @OA\Put(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Update Product",
     *     description="Update product details including price and stock. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="cost", type="number"),
     *             @OA\Property(property="stock_quantity", type="number"),
     *             @OA\Property(property="low_stock_alert", type="number"),
     *             @OA\Property(property="unit", type="string"),
     *             @OA\Property(property="tax_rate", type="number"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $product): JsonResponse
    {
        $item = $this->productQuery()?->find($product);
        if (! $item) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:20'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['nullable', 'string', 'in:product,service'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        if (! empty($validated['category_id'])) {
            $cat = \App\Models\Category::where('organization_id', $this->getOrgId())->find($validated['category_id']);
            if (! $cat) {
                $validated['category_id'] = null;
            }
        }

        $item->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $item->fresh()->load('category'),
        ]);
    }

    /**
     * Update stock only (quick stock update).
     *
     * @OA\Patch(
     *     path="/products/{id}/stock",
     *     tags={"Products"},
     *     summary="Update Stock",
     *     description="Quick update of stock quantity. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"stock_quantity"},
     *             @OA\Property(property="stock_quantity", type="number", example=50)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function updateStock(Request $request, int $product): JsonResponse
    {
        $item = $this->productQuery()?->find($product);
        if (! $item) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'stock_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $item->update(['stock_quantity' => $validated['stock_quantity']]);

        return response()->json([
            'message' => 'Stock updated successfully',
            'product' => $item->fresh(),
        ]);
    }

    /**
     * Delete product.
     *
     * @OA\Delete(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Delete Product",
     *     description="Delete a product/item. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $product): JsonResponse
    {
        $item = $this->productQuery()?->find($product);
        if (! $item) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
