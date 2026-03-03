<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\CreditNoteLineItem;
use App\Models\NumberSequence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    protected function getOrgId(): ?int
    {
        return auth('api')->user()?->organization_id;
    }

    protected function creditNoteQuery()
    {
        $orgId = $this->getOrgId();
        if (! $orgId) {
            return null;
        }

        return CreditNote::where('organization_id', $orgId);
    }

    /**
     * @OA\Get(
     *     path="/credit-notes",
     *     tags={"Sales - Credit Notes"},
     *     summary="List Credit Notes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="refund_status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->creditNoteQuery();
        if (! $query) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 15]]);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($refundStatus = $request->input('refund_status')) {
            $query->where('refund_status', $refundStatus);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSort = ['credit_note_number', 'total', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSort)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginated = $query->with(['customer', 'invoice'])->paginate($perPage);

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
     *     path="/credit-notes/{id}",
     *     tags={"Sales - Credit Notes"},
     *     summary="Get Credit Note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $creditNote): JsonResponse
    {
        $cn = $this->creditNoteQuery()?->with(['customer', 'invoice', 'lineItems'])->find($creditNote);
        if (! $cn) {
            return response()->json(['message' => 'Credit note not found'], 404);
        }

        return response()->json(['data' => $cn]);
    }

    /**
     * @OA\Post(
     *     path="/credit-notes",
     *     tags={"Sales - Credit Notes"},
     *     summary="Create Credit Note",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"customer_id","line_items"},
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="invoice_id", type="integer"),
     *         @OA\Property(property="refund_status", type="string", enum={"refund","no_refund"}),
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
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'refund_status' => ['nullable', 'string', 'in:refund,no_refund'],
            'notes' => ['nullable', 'string'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.item_id' => ['nullable', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $customer = \App\Models\Customer::where('organization_id', $orgId)->findOrFail($validated['customer_id']);
        $cnNumber = NumberSequence::getNext($orgId, 'credit_note', 'CN');

        $total = 0;
        foreach ($validated['line_items'] as $item) {
            $total += (float) $item['quantity'] * (float) $item['unit_price'];
        }
        $total = round($total, 2);

        $cn = CreditNote::create([
            'organization_id' => $orgId,
            'credit_note_number' => $cnNumber,
            'customer_id' => $customer->id,
            'invoice_id' => $validated['invoice_id'] ?? null,
            'status' => 'open',
            'total' => $total,
            'balance' => $total,
            'refund_status' => $validated['refund_status'] ?? 'no_refund',
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['line_items'] as $i => $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $amount = round($quantity * $unitPrice, 2);
            $description = $item['description'] ?? null;
            if (! $description && ! empty($item['item_id'])) {
                $product = \App\Models\Item::find($item['item_id']);
                $description = $product?->name ?? 'Item';
            }
            CreditNoteLineItem::create([
                'credit_note_id' => $cn->id,
                'item_id' => $item['item_id'] ?? null,
                'description' => $description ?? 'Line item',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'sort_order' => $i,
            ]);
        }

        $cn->load(['customer', 'invoice', 'lineItems']);

        return response()->json([
            'message' => 'Credit note created successfully',
            'credit_note' => $cn,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/credit-notes/{id}",
     *     tags={"Sales - Credit Notes"},
     *     summary="Update Credit Note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="invoice_id", type="integer"),
     *         @OA\Property(property="refund_status", type="string"),
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
    public function update(Request $request, int $creditNote): JsonResponse
    {
        $cn = $this->creditNoteQuery()?->find($creditNote);
        if (! $cn) {
            return response()->json(['message' => 'Credit note not found'], 404);
        }

        if ($cn->status === 'refunded') {
            return response()->json(['message' => 'Cannot edit refunded credit note'], 422);
        }

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'refund_status' => ['nullable', 'string', 'in:refund,no_refund'],
            'notes' => ['nullable', 'string'],
            'line_items' => ['sometimes', 'array', 'min:1'],
            'line_items.*.item_id' => ['nullable', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        if (isset($validated['customer_id'])) {
            $customer = \App\Models\Customer::where('organization_id', $this->getOrgId())->find($validated['customer_id']);
            if ($customer) {
                $cn->customer_id = $customer->id;
            }
        }
        if (array_key_exists('invoice_id', $validated)) {
            $cn->invoice_id = $validated['invoice_id'];
        }
        if (array_key_exists('refund_status', $validated)) {
            $cn->refund_status = $validated['refund_status'];
        }
        if (array_key_exists('notes', $validated)) {
            $cn->notes = $validated['notes'];
        }

        if (isset($validated['line_items'])) {
            $cn->lineItems()->delete();
            $total = 0;
            foreach ($validated['line_items'] as $i => $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $amount = round($quantity * $unitPrice, 2);
                $description = $item['description'] ?? null;
                if (! $description && ! empty($item['item_id'])) {
                    $product = \App\Models\Item::find($item['item_id']);
                    $description = $product?->name ?? 'Item';
                }
                CreditNoteLineItem::create([
                    'credit_note_id' => $cn->id,
                    'item_id' => $item['item_id'] ?? null,
                    'description' => $description ?? 'Line item',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'sort_order' => $i,
                ]);
                $total += $amount;
            }
            $cn->total = round($total, 2);
            $cn->balance = round($total, 2);
        }

        $cn->save();

        return response()->json([
            'message' => 'Credit note updated successfully',
            'credit_note' => $cn->fresh()->load(['customer', 'invoice', 'lineItems']),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/credit-notes/{id}",
     *     tags={"Sales - Credit Notes"},
     *     summary="Delete Credit Note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $creditNote): JsonResponse
    {
        $cn = $this->creditNoteQuery()?->find($creditNote);
        if (! $cn) {
            return response()->json(['message' => 'Credit note not found'], 404);
        }

        $cn->delete();

        return response()->json(['message' => 'Credit note deleted successfully']);
    }

    /**
     * Mark as REFUND.
     *
     * @OA\Patch(
     *     path="/credit-notes/{id}/mark-refund",
     *     tags={"Sales - Credit Notes"},
     *     summary="Mark as Refunded",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function markRefund(int $creditNote): JsonResponse
    {
        $cn = $this->creditNoteQuery()?->find($creditNote);
        if (! $cn) {
            return response()->json(['message' => 'Credit note not found'], 404);
        }

        $cn->update([
            'status' => 'refunded',
            'refund_status' => 'refund',
            'balance' => 0,
        ]);

        return response()->json([
            'message' => 'Credit note marked as refunded',
            'credit_note' => $cn->fresh(),
        ]);
    }
}
