<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanLineItem;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\NumberSequence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryChallanController extends Controller
{
    protected function getOrgId(): ?int
    {
        return auth('api')->user()?->organization_id;
    }

    protected function dcQuery()
    {
        $orgId = $this->getOrgId();
        if (! $orgId) {
            return null;
        }

        return DeliveryChallan::where('organization_id', $orgId);
    }

    /**
     * @OA\Get(
     *     path="/delivery-challans",
     *     tags={"Sales - Delivery Challans"},
     *     summary="List Delivery Challans",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->dcQuery();
        if (! $query) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 15]]);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('dc_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSort = ['dc_number', 'delivery_date', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSort)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginated = $query->with('customer')->paginate($perPage);

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
     * @OA\Get(
     *     path="/delivery-challans/{id}",
     *     tags={"Sales - Delivery Challans"},
     *     summary="Get Delivery Challan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $deliveryChallan): JsonResponse
    {
        $dc = $this->dcQuery()?->with(['customer', 'lineItems'])->find($deliveryChallan);
        if (! $dc) {
            return response()->json(['message' => 'Delivery challan not found'], 404);
        }

        return response()->json(['data' => $dc]);
    }

    /**
     * @OA\Post(
     *     path="/delivery-challans",
     *     tags={"Sales - Delivery Challans"},
     *     summary="Create Delivery Challan",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"customer_id","line_items"},
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="delivery_date", type="string", format="date"),
     *         @OA\Property(property="shipping_address", type="object"),
     *         @OA\Property(property="notes", type="string"),
     *         @OA\Property(property="line_items", type="array", @OA\Items(
     *             @OA\Property(property="item_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="quantity", type="number"),
     *             @OA\Property(property="unit", type="string")
     *         ))
     *     )),
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'delivery_date' => ['nullable', 'date'],
            'shipping_address' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.item_id' => ['nullable', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit' => ['nullable', 'string', 'max:20'],
        ]);

        $customer = \App\Models\Customer::where('organization_id', $orgId)->findOrFail($validated['customer_id']);
        $dcNumber = NumberSequence::getNext($orgId, 'delivery_challan', 'DC');

        $dc = DeliveryChallan::create([
            'organization_id' => $orgId,
            'dc_number' => $dcNumber,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'delivery_date' => $validated['delivery_date'] ?? null,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth('api')->id(),
        ]);

        foreach ($validated['line_items'] as $i => $item) {
            $description = $item['description'] ?? null;
            if (! $description && ! empty($item['item_id'])) {
                $product = \App\Models\Item::find($item['item_id']);
                $description = $product?->name ?? 'Item';
            }
            DeliveryChallanLineItem::create([
                'delivery_challan_id' => $dc->id,
                'item_id' => $item['item_id'] ?? null,
                'description' => $description ?? 'Line item',
                'quantity' => (float) $item['quantity'],
                'unit' => $item['unit'] ?? 'each',
                'sort_order' => $i,
            ]);
        }

        $dc->load(['customer', 'lineItems']);

        return response()->json([
            'message' => 'Delivery challan created successfully',
            'delivery_challan' => $dc,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/delivery-challans/{id}",
     *     tags={"Sales - Delivery Challans"},
     *     summary="Update Delivery Challan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="delivery_date", type="string"),
     *         @OA\Property(property="line_items", type="array", @OA\Items(
     *             @OA\Property(property="item_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="quantity", type="number"),
     *             @OA\Property(property="unit", type="string")
     *         ))
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $deliveryChallan): JsonResponse
    {
        $dc = $this->dcQuery()?->find($deliveryChallan);
        if (! $dc) {
            return response()->json(['message' => 'Delivery challan not found'], 404);
        }

        if ($dc->invoice_id) {
            return response()->json(['message' => 'Cannot edit delivery challan already converted to invoice'], 422);
        }

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'delivery_date' => ['nullable', 'date'],
            'shipping_address' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'line_items' => ['sometimes', 'array', 'min:1'],
            'line_items.*.item_id' => ['nullable', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit' => ['nullable', 'string', 'max:20'],
        ]);

        if (isset($validated['customer_id'])) {
            $customer = \App\Models\Customer::where('organization_id', $this->getOrgId())->find($validated['customer_id']);
            if ($customer) {
                $dc->customer_id = $customer->id;
            }
        }
        if (array_key_exists('delivery_date', $validated)) {
            $dc->delivery_date = $validated['delivery_date'];
        }
        if (array_key_exists('shipping_address', $validated)) {
            $dc->shipping_address = $validated['shipping_address'];
        }
        if (array_key_exists('notes', $validated)) {
            $dc->notes = $validated['notes'];
        }

        if (isset($validated['line_items'])) {
            $dc->lineItems()->delete();
            foreach ($validated['line_items'] as $i => $item) {
                $description = $item['description'] ?? null;
                if (! $description && ! empty($item['item_id'])) {
                    $product = \App\Models\Item::find($item['item_id']);
                    $description = $product?->name ?? 'Item';
                }
                DeliveryChallanLineItem::create([
                    'delivery_challan_id' => $dc->id,
                    'item_id' => $item['item_id'] ?? null,
                    'description' => $description ?? 'Line item',
                    'quantity' => (float) $item['quantity'],
                    'unit' => $item['unit'] ?? 'each',
                    'sort_order' => $i,
                ]);
            }
        }

        $dc->save();

        return response()->json([
            'message' => 'Delivery challan updated successfully',
            'delivery_challan' => $dc->fresh()->load(['customer', 'lineItems']),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/delivery-challans/{id}",
     *     tags={"Sales - Delivery Challans"},
     *     summary="Delete Delivery Challan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $deliveryChallan): JsonResponse
    {
        $dc = $this->dcQuery()?->find($deliveryChallan);
        if (! $dc) {
            return response()->json(['message' => 'Delivery challan not found'], 404);
        }

        if ($dc->invoice_id) {
            return response()->json(['message' => 'Cannot delete delivery challan already converted to invoice'], 422);
        }

        $dc->delete();

        return response()->json(['message' => 'Delivery challan deleted successfully']);
    }

    /**
     * Mark as Delivered.
     *
     * @OA\Patch(
     *     path="/delivery-challans/{id}/mark-delivered",
     *     tags={"Sales - Delivery Challans"},
     *     summary="Mark as Delivered",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function markDelivered(int $deliveryChallan): JsonResponse
    {
        $dc = $this->dcQuery()?->find($deliveryChallan);
        if (! $dc) {
            return response()->json(['message' => 'Delivery challan not found'], 404);
        }

        $dc->update([
            'status' => 'delivered',
            'delivery_date' => $dc->delivery_date ?? now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Delivery challan marked as delivered',
            'delivery_challan' => $dc->fresh(),
        ]);
    }

    /**
     * Move DC to Invoice (convert).
     *
     * @OA\Post(
     *     path="/delivery-challans/{id}/convert-to-invoice",
     *     tags={"Sales - Delivery Challans"},
     *     summary="Convert DC to Invoice",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function convertToInvoice(int $deliveryChallan): JsonResponse
    {
        $dc = $this->dcQuery()?->with(['lineItems.item'])->find($deliveryChallan);
        if (! $dc) {
            return response()->json(['message' => 'Delivery challan not found'], 404);
        }

        if ($dc->invoice_id) {
            return response()->json(['message' => 'Delivery challan already converted to invoice'], 422);
        }

        $invoiceNumber = NumberSequence::getNext($dc->organization_id, 'invoice', 'INV');
        $issueDate = now()->toDateString();
        $dueDate = $dc->delivery_date?->toDateString() ?? now()->addDays(30)->toDateString();

        $subtotal = 0;
        $lineItemsData = [];
        foreach ($dc->lineItems as $dcli) {
            $unitPrice = $dcli->item?->price ?? 0;
            $amount = round((float) $dcli->quantity * $unitPrice, 2);
            $lineItemsData[] = [
                'item_id' => $dcli->item_id,
                'description' => $dcli->description,
                'quantity' => $dcli->quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
            ];
            $subtotal += $amount;
        }

        $invoice = Invoice::create([
            'organization_id' => $dc->organization_id,
            'invoice_number' => $invoiceNumber,
            'customer_id' => $dc->customer_id,
            'delivery_challan_id' => $dc->id,
            'invoice_type' => 'from_dc',
            'status' => 'draft',
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => $subtotal,
            'amount_paid' => 0,
            'balance_due' => $subtotal,
            'notes' => $dc->notes,
            'created_by' => auth('api')->id(),
        ]);

        foreach ($lineItemsData as $i => $li) {
            InvoiceLineItem::create([
                'invoice_id' => $invoice->id,
                'item_id' => $li['item_id'],
                'description' => $li['description'],
                'quantity' => $li['quantity'],
                'unit_price' => $li['unit_price'],
                'tax_rate' => 0,
                'amount' => $li['amount'],
                'sort_order' => $i,
            ]);
        }

        $dc->update(['invoice_id' => $invoice->id]);
        $invoice->load(['customer', 'lineItems', 'deliveryChallan']);

        return response()->json([
            'message' => 'Delivery challan converted to invoice successfully',
            'invoice' => $invoice,
        ], 201);
    }
}
