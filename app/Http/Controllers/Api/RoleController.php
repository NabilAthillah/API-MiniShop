<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Roles', description: 'Manage user roles (Admin only)')]
class RoleController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/roles',
        tags: ['Roles'],
        summary: 'List all roles (Admin only)',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Roles retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Role')
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
        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            RoleResource::collection($roles),
            'Roles retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/roles/{id}',
        tags: ['Roles'],
        summary: 'Get a single role (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Role retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Role', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Role not found'),
        ]
    )]
    public function show(Role $role): JsonResponse
    {
        return $this->successResponse(
            RoleResource::make($role),
            'Role retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/roles',
        tags: ['Roles'],
        summary: 'Create a new role (Admin only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Warehouse Staff'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Role created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Role', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create($request->validated());

        return $this->successResponse(
            RoleResource::make($role),
            'Role created successfully',
            201
        );
    }

    #[OA\Put(
        path: '/api/roles/{id}',
        tags: ['Roles'],
        summary: 'Update a role (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Warehouse Staff'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Role updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Role', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Role not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role->update($request->validated());

        return $this->successResponse(
            RoleResource::make($role),
            'Role updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/roles/{id}',
        tags: ['Roles'],
        summary: 'Delete a role (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Role deleted successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Role not found'),
            new OA\Response(response: 409, description: 'Role still has users assigned'),
        ]
    )]
    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->exists()) {
            return $this->errorResponse('Cannot delete a role that still has users assigned.', 409);
        }

        $role->delete();

        return $this->successResponse(
            null,
            'Role deleted successfully'
        );
    }
}
