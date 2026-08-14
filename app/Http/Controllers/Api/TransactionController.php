<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Transactions', description: 'Create and view transactions')]
class TransactionController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/transactions',
        tags: ['Transactions'],
        summary: 'List all transactions (Admin only)',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transactions retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Transaction')
                            ),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
        ]
    )]
    public function index(): JsonResponse
    {
        $transactions = Transaction::query()
            ->with(['user', 'address', 'transactionProducts.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            TransactionResource::collection($transactions),
            'Transactions retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/transactions/mine',
        tags: ['Transactions'],
        summary: "List the authenticated user's own transactions",
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transactions retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Transaction')
                            ),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function mine(Request $request): JsonResponse
    {
        $transactions = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->with(['user', 'address', 'transactionProducts.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            TransactionResource::collection($transactions),
            'Transactions retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/transactions/{id}',
        tags: ['Transactions'],
        summary: 'Get a single transaction (owner or Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transaction retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Transaction', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not the owner or an Admin'),
            new OA\Response(response: 404, description: 'Transaction not found'),
        ]
    )]
    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        if (! $this->canAccess($request, $transaction)) {
            return $this->errorResponse('You do not have permission to access this transaction.', 403);
        }

        $transaction->load(['user', 'address', 'transactionProducts.product']);

        return $this->successResponse(
            TransactionResource::make($transaction),
            'Transaction retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/transactions',
        tags: ['Transactions'],
        summary: 'Create a transaction (checkout) for the authenticated user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['address_id', 'products'],
                properties: [
                    new OA\Property(property: 'address_id', type: 'string', format: 'uuid', description: 'Must belong to the authenticated user'),
                    new OA\Property(
                        property: 'products',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'product_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'qty', type: 'integer', example: 2),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Transaction created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Transaction', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error (e.g. product does not exist, address does not belong to the user, or insufficient stock)'),
        ]
    )]
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $transaction = DB::transaction(function () use ($validated, $request) {
            $transaction = Transaction::create([
                'user_id' => $request->user()->id,
                'address_id' => $validated['address_id'],
            ]);

            foreach ($validated['products'] as $index => $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

                if ($item['qty'] > $product->stock) {
                    throw ValidationException::withMessages([
                        "products.{$index}.qty" => "Insufficient stock for \"{$product->name}\". Available: {$product->stock}.",
                    ]);
                }

                $product->decrement('stock', $item['qty']);

                $transaction->transactionProducts()->create([
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                ]);
            }

            return $transaction;
        });

        $transaction->load(['user', 'address', 'transactionProducts.product']);

        return $this->successResponse(
            TransactionResource::make($transaction),
            'Transaction created successfully',
            201
        );
    }

    protected function canAccess(Request $request, Transaction $transaction): bool
    {
        return $request->user()->isAdmin() || $transaction->user_id === $request->user()->id;
    }
}
