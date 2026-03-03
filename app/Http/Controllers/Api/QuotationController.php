<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\NumberSequence;
use App\Models\Quotation;
use App\Models\QuotationLineItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    protected function getOrgId(): ?int
    {
        return auth('api')->user()?->organization_id;
    }

    protected function quotationQuery()
    {
        $orgId = $this->getOrgId();
        if (! $orgId) {
            return null;
        }

        return Quotation::where('organization_id', $orgId);
    }

    /**
     * @OA\Get(
     *     path="/quotations",
     *     tags={"Sales - Quotations"},
     *     summary="List Quotations",
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
        $query = $this->quotationQuery();
        if (! $query) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 15]]);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSort = ['quotation_number', 'valid_until', 'total', 'status', 'created_at'];
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
     *     path="/quotations/{id}",
     *     tags={"Sales - Quotations"},
     *     summary="Get Quotation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $quotation): JsonResponse
    {
        $q = $this->quotationQuery()?->with(['customer', 'lineItems'])->find($quotation);
        if (! $q) {
            return response()->json(['message' => 'Quotation not found'], 404);
        }

        return response()->json(['data' => $q]);
    }

    /**
     * @OA\Post(
     *     path="/quotations",
     *     tags={"Sales - Quotations"},
     *     summary="Create Quotation",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"customer_id","line_items"},
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="valid_until", type="string", format="date"),
     *         @OA\Property(property="notes", type="string"),
     *         @OA\Property(property="line_items", type="array", @OA\Items(
     *             @OA\Property(property="item_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="quantity", type="number"),
     *             @OA\Property(property="unit_price", type="number")
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
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.item_id' => ['nullable', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $customer = \App\Models\Customer::where('organization_id', $orgId)->findOrFail($validated['customer_id']);
        $quotationNumber = NumberSequence::getNext($orgId, 'quotation', 'QT');

        $quotation = Quotation::create([
            'organization_id' => $orgId,
            'quotation_number' => $quotationNumber,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'valid_until' => $validated['valid_until'] ?? null,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
            'created_by' => auth('api')->id(),
        ]);

        $subtotal = 0;
        foreach ($validated['line_items'] as $i => $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $amount = round($quantity * $unitPrice, 2);
            $description = $item['description'] ?? null;
            if (! $description && ! empty($item['item_id'])) {
                $product = \App\Models\Item::find($item['item_id']);
                $description = $product?->name ?? 'Item';
            }
            QuotationLineItem::create([
                'quotation_id' => $quotation->id,
                'item_id' => $item['item_id'] ?? null,
                'description' => $description ?? 'Line item',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => 0,
                'amount' => $amount,
                'sort_order' => $i,
            ]);
            $subtotal += $amount;
        }

        $quotation->update(['subtotal' => $subtotal, 'total' => $subtotal]);
        $quotation->load(['customer', 'lineItems']);

        return response()->json([
            'message' => 'Quotation created successfully',
            'quotation' => $quotation,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/quotations/{id}",
     *     tags={"Sales - Quotations"},
     *     summary="Update Quotation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="valid_until", type="string"),
     *         @OA\Property(property="notes", type="string"),
     *         @OA\Property(property="line_items", type="array", @OA\Items(
     *             @OA\Property(property="item_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="quantity", type="number"),
     *             @OA\Property(property="unit_price", type="number")
     *         ))
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $quotation): JsonResponse
    {
        $q = $this->quotationQuery()?->find($quotation);
        if (! $q) {
            return response()->json(['message' => 'Quotation not found'], 404);
        }

        if ($q->invoice_id) {
            return response()->json(['message' => 'Cannot edit quotation already converted to invoice'], 422);
        }

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'line_items' => ['sometimes', 'array', 'min:1'],
            'line_items.*.item_id' => ['nullable', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        if (isset($validated['customer_id'])) {
            $customer = \App\Models\Customer::where('organization_id', $this->getOrgId())->find($validated['customer_id']);
            if ($customer) {
                $q->customer_id = $customer->id;
            }
        }
        if (array_key_exists('valid_until', $validated)) {
            $q->valid_until = $validated['valid_until'];
        }
        if (array_key_exists('notes', $validated)) {
            $q->notes = $validated['notes'];
        }
        if (array_key_exists('terms', $validated)) {
            $q->terms = $validated['terms'];
        }

        if (isset($validated['line_items'])) {
            $q->lineItems()->delete();
            $subtotal = 0;
            foreach ($validated['line_items'] as $i => $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $amount = round($quantity * $unitPrice, 2);
                $description = $item['description'] ?? null;
                if (! $description && ! empty($item['item_id'])) {
                    $product = \App\Models\Item::find($item['item_id']);
                    $description = $product?->name ?? 'Item';
                }
                QuotationLineItem::create([
                    'quotation_id' => $q->id,
                    'item_id' => $item['item_id'] ?? null,
                    'description' => $description ?? 'Line item',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => 0,
                    'amount' => $amount,
                    'sort_order' => $i,
                ]);
                $subtotal += $amount;
            }
            $q->subtotal = $subtotal;
            $q->total = $subtotal;
        }

        $q->save();

        return response()->json([
            'message' => 'Quotation updated successfully',
            'quotation' => $q->fresh()->load(['customer', 'lineItems']),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/quotations/{id}",
     *     tags={"Sales - Quotations"},
     *     summary="Delete Quotation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $quotation): JsonResponse
    {
        $q = $this->quotationQuery()?->find($quotation);
        if (! $q) {
            return response()->json(['message' => 'Quotation not found'], 404);
        }

        if ($q->invoice_id) {
            return response()->json(['message' => 'Cannot delete quotation already converted to invoice'], 422);
        }

        $q->delete();

        return response()->json(['message' => 'Quotation deleted successfully']);
    }

    /**
     * Move Quotation to Invoice (convert).
     *
     * @OA\Post(
     *     path="/quotations/{id}/convert-to-invoice",
     *     tags={"Sales - Quotations"},
     *     summary="Convert Quotation to Invoice",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function convertToInvoice(int $quotation): JsonResponse
    {
        $q = $this->quotationQuery()?->with('lineItems')->find($quotation);
        if (! $q) {
            return response()->json(['message' => 'Quotation not found'], 404);
        }

        if ($q->invoice_id) {
            return response()->json(['message' => 'Quotation already converted to invoice'], 422);
        }

        $invoiceNumber = NumberSequence::getNext($q->organization_id, 'invoice', 'INV');
        $issueDate = now()->toDateString();
        $dueDate = $q->valid_until?->toDateString() ?? now()->addDays(30)->toDateString();

        $invoice = Invoice::create([
            'organization_id' => $q->organization_id,
            'invoice_number' => $invoiceNumber,
            'customer_id' => $q->customer_id,
            'quotation_id' => $q->id,
            'invoice_type' => 'from_quotation',
            'status' => 'draft',
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $q->subtotal,
            'tax_amount' => $q->tax_amount,
            'discount_amount' => $q->discount_amount,
            'total' => $q->total,
            'amount_paid' => 0,
            'balance_due' => $q->total,
            'notes' => $q->notes,
            'terms' => $q->terms,
            'created_by' => auth('api')->id(),
        ]);

        foreach ($q->lineItems as $qli) {
            InvoiceLineItem::create([
                'invoice_id' => $invoice->id,
                'item_id' => $qli->item_id,
                'description' => $qli->description,
                'quantity' => $qli->quantity,
                'unit_price' => $qli->unit_price,
                'tax_rate' => $qli->tax_rate,
                'amount' => $qli->amount,
                'sort_order' => $qli->sort_order,
            ]);
        }

        $q->update(['invoice_id' => $invoice->id, 'status' => 'accepted']);
        $invoice->load(['customer', 'lineItems', 'quotation']);

        return response()->json([
            'message' => 'Quotation converted to invoice successfully',
            'invoice' => $invoice,
        ], 201);
    }
}
