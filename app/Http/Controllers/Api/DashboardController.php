<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get date range for period (T/W/M/Y).
     */
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
     * Net Profit (T/W/M/Y).
     * Net Profit = Income (invoice totals) - COGS - Expenses
     *
     * @OA\Get(
     *     path="/dashboard/net-profit",
     *     tags={"Dashboard"},
     *     summary="Net Profit",
     *     description="Get net profit for period (T=Today, W=Week, M=Month, Y=Year). Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, @OA\Schema(type="string", enum={"T","W","M","Y"}, default="M")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="net_profit", type="number"),
     *         @OA\Property(property="income", type="number"),
     *         @OA\Property(property="cogs", type="number"),
     *         @OA\Property(property="expenses", type="number"),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function netProfit(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId();
        if (! $orgId) {
            return $this->emptyNetProfit($request->input('period', 'M'));
        }

        $period = $request->input('period', 'M');
        [$start, $end] = $this->getDateRange($period);

        // Income: invoice totals (excluding cancelled)
        $income = Invoice::where('organization_id', $orgId)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total');

        // COGS: cost of goods sold from invoice line items (quantity * item.cost)
        $cogs = DB::table('invoice_line_items')
            ->join('invoices', 'invoice_line_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('items', 'invoice_line_items.item_id', '=', 'items.id')
            ->where('invoices.organization_id', $orgId)
            ->whereNotIn('invoices.status', ['cancelled'])
            ->whereBetween('invoices.issue_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(invoice_line_items.quantity * COALESCE(items.cost, 0)), 0) as cogs')
            ->value('cogs') ?? 0;

        // Expenses
        $expenses = Expense::where('organization_id', $orgId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $netProfit = (float) $income - (float) $cogs - (float) $expenses;

        return response()->json([
            'net_profit' => round($netProfit, 2),
            'income' => round((float) $income, 2),
            'cogs' => round((float) $cogs, 2),
            'expenses' => round((float) $expenses, 2),
            'period' => $period,
        ]);
    }

    /**
     * Received Amount (T/W/M/Y).
     *
     * @OA\Get(
     *     path="/dashboard/received-amount",
     *     tags={"Dashboard"},
     *     summary="Received Amount",
     *     description="Get total received (payments) for period. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, @OA\Schema(type="string", enum={"T","W","M","Y"}, default="M")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="received_amount", type="number"),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function receivedAmount(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId();
        if (! $orgId) {
            return response()->json([
                'received_amount' => 0,
                'period' => $request->input('period', 'M'),
            ]);
        }

        $period = $request->input('period', 'M');
        [$start, $end] = $this->getDateRange($period);

        $received = Payment::where('organization_id', $orgId)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        return response()->json([
            'received_amount' => round((float) $received, 2),
            'period' => $period,
        ]);
    }

    /**
     * Income and Expense (T/W/M/Y).
     *
     * @OA\Get(
     *     path="/dashboard/income-expense",
     *     tags={"Dashboard"},
     *     summary="Income and Expense",
     *     description="Get income (invoice totals) and expenses for period. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, @OA\Schema(type="string", enum={"T","W","M","Y"}, default="M")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="income", type="number"),
     *         @OA\Property(property="expense", type="number"),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function incomeExpense(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId();
        if (! $orgId) {
            return response()->json([
                'income' => 0,
                'expense' => 0,
                'period' => $request->input('period', 'M'),
            ]);
        }

        $period = $request->input('period', 'M');
        [$start, $end] = $this->getDateRange($period);

        $income = Invoice::where('organization_id', $orgId)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total');

        $expense = Expense::where('organization_id', $orgId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        return response()->json([
            'income' => round((float) $income, 2),
            'expense' => round((float) $expense, 2),
            'period' => $period,
        ]);
    }

    /**
     * Low Stock Items.
     *
     * @OA\Get(
     *     path="/dashboard/low-stock-items",
     *     tags={"Dashboard"},
     *     summary="Low Stock Items",
     *     description="Get items where stock is at or below low stock alert. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="items", type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="stock_quantity", type="number"),
     *             @OA\Property(property="low_stock_alert", type="number"),
     *             @OA\Property(property="unit", type="string")
     *         ))
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function lowStockItems(): JsonResponse
    {
        $orgId = $this->getOrganizationId();
        if (! $orgId) {
            return response()->json(['items' => []]);
        }

        $items = Item::where('organization_id', $orgId)
            ->where('status', 'active')
            ->where('item_type', 'product')
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->where('low_stock_alert', '>', 0)
            ->orderBy('stock_quantity')
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_alert', 'unit']);

        return response()->json(['items' => $items]);
    }

    /**
     * Payment Method Chart.
     *
     * @OA\Get(
     *     path="/dashboard/payment-method-chart",
     *     tags={"Dashboard"},
     *     summary="Payment Method Chart",
     *     description="Get payment totals grouped by payment method for chart. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, @OA\Schema(type="string", enum={"T","W","M","Y"}, default="M")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(
     *             @OA\Property(property="payment_method_id", type="integer"),
     *             @OA\Property(property="payment_method_name", type="string"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="count", type="integer")
     *         ))
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function paymentMethodChart(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId();
        if (! $orgId) {
            return response()->json(['data' => []]);
        }

        $period = $request->input('period', 'M');
        [$start, $end] = $this->getDateRange($period);

        $data = Payment::where('payments.organization_id', $orgId)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->select(
                'payments.payment_method_id',
                DB::raw('COALESCE(payment_methods.name, \'Other\') as payment_method_name'),
                DB::raw('SUM(payments.amount) as amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('payments.payment_method_id', 'payment_methods.name')
            ->get()
            ->map(function ($row) {
                return [
                    'payment_method_id' => $row->payment_method_id,
                    'payment_method_name' => $row->payment_method_name ?? 'Other',
                    'amount' => round((float) $row->amount, 2),
                    'count' => (int) $row->count,
                ];
            });

        return response()->json(['data' => $data]);
    }

    /**
     * CTA - Call to Action links (New Bill, Add Product, Add Customer).
     *
     * @OA\Get(
     *     path="/dashboard/cta",
     *     tags={"Dashboard"},
     *     summary="CTA Links",
     *     description="Get URLs for New Bill, Add Product, Add Customer. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="new_bill", type="object", @OA\Property(property="label", type="string"), @OA\Property(property="web_url", type="string"), @OA\Property(property="api_url", type="string"), @OA\Property(property="method", type="string")),
     *         @OA\Property(property="add_product", type="object"),
     *         @OA\Property(property="add_customer", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function cta(): JsonResponse
    {
        $base = config('app.url', 'http://127.0.0.1:8000');
        $apiBase = $base.'/api/v1';

        return response()->json([
            'new_bill' => [
                'label' => 'New Bill',
                'web_url' => $base.'/bills/new',
                'api_url' => $apiBase.'/bills',
                'method' => 'POST',
            ],
            'add_product' => [
                'label' => 'Add Product',
                'web_url' => $base.'/products/create',
                'api_url' => $apiBase.'/products',
                'method' => 'POST',
            ],
            'add_customer' => [
                'label' => 'Add Customer',
                'web_url' => $base.'/customers/create',
                'api_url' => $apiBase.'/customers',
                'method' => 'POST',
            ],
        ]);
    }

    protected function getOrganizationId(): ?int
    {
        $user = auth('api')->user();

        return $user?->organization_id;
    }

    protected function emptyNetProfit(string $period): JsonResponse
    {
        return response()->json([
            'net_profit' => 0,
            'income' => 0,
            'cogs' => 0,
            'expenses' => 0,
            'period' => $period,
        ]);
    }
}
