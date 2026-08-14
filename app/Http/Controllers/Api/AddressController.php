<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Addresses', description: 'Manage shipping addresses')]
class AddressController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/addresses',
        tags: ['Addresses'],
        summary: 'List all addresses of all users (Admin only)',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Addresses retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Address')
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
        $addresses = Address::query()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            AddressResource::collection($addresses),
            'Addresses retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/addresses/me',
        tags: ['Addresses'],
        summary: 'List the authenticated user\'s own addresses',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Addresses retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Address')
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
        $addresses = $request->user()
            ->addresses()
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            AddressResource::collection($addresses),
            'Addresses retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/addresses',
        tags: ['Addresses'],
        summary: 'Create an address for the authenticated user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label', 'full_address', 'recipient_name', 'recipient_phone_number'],
                properties: [
                    new OA\Property(property: 'label', type: 'string', example: 'Home'),
                    new OA\Property(property: 'full_address', type: 'string', example: 'Jl. Merdeka No. 1, Jakarta'),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Near the red gate'),
                    new OA\Property(property: 'recipient_name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'recipient_phone_number', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'is_primary', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Address created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Address', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $request->user()->addresses()->create($request->validated());

        return $this->successResponse(
            AddressResource::make($address),
            'Address created successfully',
            201
        );
    }

    #[OA\Get(
        path: '/api/addresses/{id}',
        tags: ['Addresses'],
        summary: 'Get a single address (owner or Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Address', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not the owner or an Admin'),
            new OA\Response(response: 404, description: 'Address not found'),
        ]
    )]
    public function show(Request $request, Address $address): JsonResponse
    {
        if (! $this->canAccess($request, $address)) {
            return $this->errorResponse('You do not have permission to access this address.', 403);
        }

        return $this->successResponse(
            AddressResource::make($address),
            'Address retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/addresses/{id}',
        tags: ['Addresses'],
        summary: 'Update an address (owner or Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label', 'full_address', 'recipient_name', 'recipient_phone_number'],
                properties: [
                    new OA\Property(property: 'label', type: 'string', example: 'Home'),
                    new OA\Property(property: 'full_address', type: 'string', example: 'Jl. Merdeka No. 1, Jakarta'),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Near the red gate'),
                    new OA\Property(property: 'recipient_name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'recipient_phone_number', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'is_primary', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Address', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not the owner or an Admin'),
            new OA\Response(response: 404, description: 'Address not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        if (! $this->canAccess($request, $address)) {
            return $this->errorResponse('You do not have permission to access this address.', 403);
        }

        $address->update($request->validated());

        return $this->successResponse(
            AddressResource::make($address),
            'Address updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/addresses/{id}',
        tags: ['Addresses'],
        summary: 'Delete an address (owner or Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address deleted successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not the owner or an Admin'),
            new OA\Response(response: 404, description: 'Address not found'),
        ]
    )]
    public function destroy(Request $request, Address $address): JsonResponse
    {
        if (! $this->canAccess($request, $address)) {
            return $this->errorResponse('You do not have permission to access this address.', 403);
        }

        $address->delete();

        return $this->successResponse(
            null,
            'Address deleted successfully'
        );
    }

    protected function canAccess(Request $request, Address $address): bool
    {
        return $request->user()->isAdmin() || $address->user_id === $request->user()->id;
    }
}
