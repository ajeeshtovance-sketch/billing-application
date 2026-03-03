<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected function getOrgId(): ?int
    {
        return auth('api')->user()?->organization_id;
    }

    protected function customerQuery()
    {
        $orgId = $this->getOrgId();
        if (! $orgId) {
            return null;
        }

        return Customer::where('organization_id', $orgId);
    }

    /**
     * List customers with pagination, search, and sort.
     *
     * @OA\Get(
     *     path="/customers",
     *     tags={"Customers"},
     *     summary="List Customers",
     *     description="Get paginated list of customers. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="search", in="query", description="Search by name, email, phone", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"name","email","created_at"}, default="created_at")),
     *     @OA\Parameter(name="sort_order", in="query", @OA\Schema(type="string", enum={"asc","desc"}, default="desc")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->customerQuery();
        if (! $query) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]], 200);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSort = ['name', 'email', 'phone', 'created_at'];
        if (in_array($sortBy, $allowedSort)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginated = $query->paginate($perPage);

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
     * Customer summary (top summary cards).
     *
     * @OA\Get(
     *     path="/customers/summary",
     *     tags={"Customers"},
     *     summary="Customer Summary",
     *     description="Get customer summary statistics. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="total_customers", type="integer"),
     *         @OA\Property(property="new_this_month", type="integer"),
     *         @OA\Property(property="active_customers", type="integer")
     *     )),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function summary(): JsonResponse
    {
        $query = $this->customerQuery();
        if (! $query) {
            return response()->json([
                'total_customers' => 0,
                'new_this_month' => 0,
                'active_customers' => 0,
            ]);
        }

        $total = $query->count();
        $newThisMonth = (clone $query)->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $active = (clone $query)->where('status', 'active')->count();

        return response()->json([
            'total_customers' => $total,
            'new_this_month' => $newThisMonth,
            'active_customers' => $active,
        ]);
    }

    /**
     * Get single customer details.
     *
     * @OA\Get(
     *     path="/customers/{id}",
     *     tags={"Customers"},
     *     summary="Get Customer",
     *     description="Get customer basic details. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $customer): JsonResponse
    {
        $model = $this->customerQuery()?->find($customer);
        if (! $model) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json(['data' => $model]);
    }

    /**
     * Add a new customer.
     *
     * @OA\Post(
     *     path="/customers",
     *     tags={"Customers"},
     *     summary="Add Customer",
     *     description="Create a new customer. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="company_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="object"),
     *             @OA\Property(property="billing_address", type="object"),
     *             @OA\Property(property="gstin", type="string"),
     *             @OA\Property(property="payment_terms", type="integer", example=30),
     *             @OA\Property(property="notes", type="string")
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
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'billing_address' => ['nullable', 'array'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['organization_id'] = $orgId;
        $validated['payment_terms'] = $validated['payment_terms'] ?? 30;
        $validated['status'] = 'active';

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => $customer,
        ], 201);
    }

    /**
     * Update customer details.
     *
     * @OA\Put(
     *     path="/customers/{id}",
     *     tags={"Customers"},
     *     summary="Update Customer",
     *     description="Update customer details. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="company_name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="object"),
     *             @OA\Property(property="billing_address", type="object"),
     *             @OA\Property(property="gstin", type="string"),
     *             @OA\Property(property="payment_terms", type="integer"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $customer): JsonResponse
    {
        $model = $this->customerQuery()?->find($customer);
        if (! $model) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'billing_address' => ['nullable', 'array'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $model->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $model->fresh(),
        ]);
    }

    /**
     * Delete customer.
     *
     * @OA\Delete(
     *     path="/customers/{id}",
     *     tags={"Customers"},
     *     summary="Delete Customer",
     *     description="Delete a customer. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $customer): JsonResponse
    {
        $model = $this->customerQuery()?->find($customer);
        if (! $model) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $model->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    /**
     * Customer invoices (transaction timeline).
     *
     * @OA\Get(
     *     path="/customers/{id}/invoices",
     *     tags={"Customers"},
     *     summary="Customer Invoices",
     *     description="Get invoices for a customer (transaction timeline). Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="string", enum={"draft","sent","paid","partial","unpaid","cancelled"})),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function invoices(Request $request, int $id): JsonResponse
    {
        $customer = $this->customerQuery()?->find($id);
        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $query = Invoice::where('customer_id', $customer->id)
            ->where('organization_id', $this->getOrgId());

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $query->orderBy('issue_date', 'desc');

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginated = $query->with(['lineItems', 'payments.paymentMethod'])->paginate($perPage);

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
}
