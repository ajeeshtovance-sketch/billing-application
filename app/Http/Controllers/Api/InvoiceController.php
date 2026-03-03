<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected function getOrgId(): ?int
    {
        return auth('api')->user()?->organization_id;
    }

    protected function invoiceQuery()
    {
        $orgId = $this->getOrgId();
        if (! $orgId) {
            return null;
        }

        return Invoice::where('organization_id', $orgId);
    }

    protected function getDateRange(string $period): array
    {
        return match ($period) {
            'today', 'T' => [now()->startOfDay(), now()->endOfDay()],
            'week', 'W' => [now()->startOfWeek(), now()->endOfWeek()],
            'month', 'M' => [now()->startOfMonth(), now()->endOfMonth()],
            'year', 'Y' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /**
     * Invoice summary (Total Sales, Paid, Unpaid, Cancelled).
     *
     * @OA\Get(
     *     path="/invoices/summary",
     *     tags={"Sales - Invoices"},
     *     summary="Invoice Summary",
     *     description="Total Sales, Paid, Unpaid, Cancelled for period. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"T","W","M","Y"}, default="M")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function summary(Request $request): JsonResponse
    {
        $query = $this->invoiceQuery();
        if (! $query) {
            return response()->json([
                'total_sales' => 0,
                'paid' => 0,
                'unpaid' => 0,
                'cancelled' => 0,
                'period' => $request->input('period', 'M'),
            ]);
        }

        $period = $request->input('period', 'M');
        [$start, $end] = $this->getDateRange($period);

        $invoices = (clone $query)->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])->get();

        $totalSales = $invoices->whereNotIn('status', ['cancelled'])->sum('total');
        $paid = $invoices->where('status', 'paid')->sum('amount_paid');
        $unpaid = $invoices->whereIn('status', ['sent', 'unpaid', 'partial'])->sum('balance_due');
        $cancelled = $invoices->where('status', 'cancelled')->sum('total');

        return response()->json([
            'total_sales' => round((float) $totalSales, 2),
            'paid' => round((float) $paid, 2),
            'unpaid' => round((float) $unpaid, 2),
            'cancelled' => round((float) $cancelled, 2),
            'period' => $period,
        ]);
    }

    /**
     * List invoices (invoice history).
     *
     * @OA\Get(
     *     path="/invoices",
     *     tags={"Sales - Invoices"},
     *     summary="List Invoices",
     *     description="Invoice history with pagination. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","sent","paid","partial","unpaid","cancelled"})),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", default="issue_date")),
     *     @OA\Parameter(name="sort_order", in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->invoiceQuery();
        if (! $query) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 15]]);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortBy = $request->input('sort_by', 'issue_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSort = ['invoice_number', 'issue_date', 'total', 'status', 'created_at'];
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
     * Get invoice detail.
     *
     * @OA\Get(
     *     path="/invoices/{id}",
     *     tags={"Sales - Invoices"},
     *     summary="Get Invoice",
     *     description="Invoice detail with line items and payments. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $invoice): JsonResponse
    {
        $inv = $this->invoiceQuery()?->with(['customer', 'lineItems', 'payments'])->find($invoice);
        if (! $inv) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        return response()->json(['data' => $inv]);
    }

    /**
     * Update invoice.
     *
     * @OA\Put(
     *     path="/invoices/{id}",
     *     tags={"Sales - Invoices"},
     *     summary="Update Invoice",
     *     description="Edit invoice. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="customer_id", type="integer"),
     *         @OA\Property(property="issue_date", type="string", format="date"),
     *         @OA\Property(property="due_date", type="string", format="date"),
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
    public function update(Request $request, int $invoice): JsonResponse
    {
        $inv = $this->invoiceQuery()?->find($invoice);
        if (! $inv) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        if ($inv->status === 'cancelled') {
            return response()->json(['message' => 'Cannot update cancelled invoice'], 422);
        }

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.item_id' => ['nullable', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        if (isset($validated['customer_id'])) {
            $customer = \App\Models\Customer::where('organization_id', $this->getOrgId())->find($validated['customer_id']);
            if ($customer) {
                $inv->customer_id = $customer->id;
            }
        }
        if (isset($validated['issue_date'])) {
            $inv->issue_date = $validated['issue_date'];
        }
        if (isset($validated['due_date'])) {
            $inv->due_date = $validated['due_date'];
        }
        if (array_key_exists('notes', $validated)) {
            $inv->notes = $validated['notes'];
        }

        if (isset($validated['line_items'])) {
            $inv->lineItems()->delete();
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
                InvoiceLineItem::create([
                    'invoice_id' => $inv->id,
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
            $inv->subtotal = $subtotal;
            $inv->tax_amount = 0;
            $inv->total = $subtotal;
            $inv->balance_due = $subtotal - $inv->amount_paid;
        }

        $inv->save();

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => $inv->fresh()->load(['customer', 'lineItems']),
        ]);
    }

    /**
     * Cancel invoice.
     *
     * @OA\Patch(
     *     path="/invoices/{id}/cancel",
     *     tags={"Sales - Invoices"},
     *     summary="Cancel Invoice",
     *     description="Mark invoice as cancelled. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function cancel(int $invoice): JsonResponse
    {
        $inv = $this->invoiceQuery()?->find($invoice);
        if (! $inv) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $inv->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Invoice cancelled successfully',
            'invoice' => $inv->fresh(),
        ]);
    }

    /**
     * Delete invoice.
     *
     * @OA\Delete(
     *     path="/invoices/{id}",
     *     tags={"Sales - Invoices"},
     *     summary="Delete Invoice",
     *     description="Delete a draft invoice. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $invoice): JsonResponse
    {
        $inv = $this->invoiceQuery()?->find($invoice);
        if (! $inv) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $inv->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }
}
