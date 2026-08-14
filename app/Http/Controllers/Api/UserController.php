<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Users', description: 'Manage user accounts (Admin only)')]
class UserController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/users',
        tags: ['Users'],
        summary: 'List all users (Admin only)',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/User')
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
        $users = User::query()
            ->with('role')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            UserResource::collection($users),
            'Users retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/users/{id}',
        tags: ['Users'],
        summary: 'Get a single user (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/User', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function show(User $user): JsonResponse
    {
        return $this->successResponse(
            UserResource::make($user->load('role')),
            'User retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/users',
        tags: ['Users'],
        summary: 'Create a new user (Admin only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'phone', 'password', 'password_confirmation', 'role_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'Password123!'),
                    new OA\Property(property: 'role_id', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/User', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return $this->successResponse(
            UserResource::make($user->load('role')),
            'User created successfully',
            201
        );
    }

    #[OA\Put(
        path: '/api/users/{id}',
        tags: ['Users'],
        summary: 'Update a user (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'phone', 'role_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true, description: 'Leave empty to keep the current password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', nullable: true),
                    new OA\Property(property: 'role_id', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/User', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return $this->successResponse(
            UserResource::make($user->load('role')),
            'User updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/users/{id}',
        tags: ['Users'],
        summary: 'Delete a user (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 409, description: 'User cannot be deleted (self, or has existing transactions)'),
        ]
    )]
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return $this->errorResponse('You cannot delete your own account.', 409);
        }

        if ($user->transactions()->exists()) {
            return $this->errorResponse('Cannot delete a user that has existing transactions.', 409);
        }

        $user->delete();

        return $this->successResponse(
            null,
            'User deleted successfully'
        );
    }
}
