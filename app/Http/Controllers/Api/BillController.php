<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\NumberSequence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillController extends Controller
{
    /**
     * Create a new bill (invoice).
     *
     * @OA\Post(
     *     path="/bills",
     *     tags={"CTA"},
     *     summary="New Bill",
     *     description="Create a new bill/invoice. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_id"},
     *             @OA\Property(property="customer_id", type="integer", example=1),
     *             @OA\Property(property="issue_date", type="string", format="date", example="2026-03-03"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2026-04-03"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="line_items", type="array", @OA\Items(
     *                 @OA\Property(property="item_id", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="quantity", type="number"),
     *                 @OA\Property(property="unit_price", type="number")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Bill created successfully"),
     *         @OA\Property(property="invoice", type="object")
     *     )),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $orgId = auth('api')->user()?->organization_id;
        if (! $orgId) {
            return response()->json(['message' => 'Organization required'], 403);
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.item_id' => ['nullable', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        // Ensure customer belongs to org
        $customer = \App\Models\Customer::where('organization_id', $orgId)->findOrFail($validated['customer_id']);

        $issueDate = $validated['issue_date'] ?? now()->toDateString();
        $dueDate = $validated['due_date'] ?? now()->addDays(30)->toDateString();

        $invoiceNumber = NumberSequence::getNext($orgId, 'invoice', 'INV');

        $invoice = Invoice::create([
            'organization_id' => $orgId,
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customer->id,
            'invoice_type' => 'standard',
            'status' => 'draft',
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance_due' => 0,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth('api')->id(),
        ]);

        $subtotal = 0;
        $lineItems = $validated['line_items'] ?? [];
        foreach ($lineItems as $i => $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $amount = round($quantity * $unitPrice, 2);
            $description = $item['description'] ?? null;

            if (! $description && ! empty($item['item_id'])) {
                $product = \App\Models\Item::where('organization_id', $orgId)->find($item['item_id']);
                $description = $product?->name ?? 'Item';
            }

            InvoiceLineItem::create([
                'invoice_id' => $invoice->id,
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

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'total' => $subtotal,
            'balance_due' => $subtotal,
        ]);

        $invoice->load(['customer', 'lineItems']);

        return response()->json([
            'message' => 'Bill created successfully',
            'invoice' => $invoice,
        ], 201);
    }
}
