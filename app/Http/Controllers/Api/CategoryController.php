<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Categories', description: 'Browse and manage product categories')]
class CategoryController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/categories',
        tags: ['Categories'],
        summary: 'List all categories',
        parameters: [
            new OA\Parameter(
                name: 'with',
                in: 'query',
                required: false,
                description: 'Pass "products" to include each category\'s products',
                schema: new OA\Schema(type: 'string', enum: ['products'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Category')
                            ),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->when($request->query('with') === 'products', function ($query) {
                $query->with(['products' => fn ($q) => $q->with(['images', 'category'])->orderBy('created_at', 'desc')]);
            })
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            CategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/categories/{slug}',
        tags: ['Categories'],
        summary: 'Get a single category by slug',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'electronics'),
            new OA\Parameter(
                name: 'with',
                in: 'query',
                required: false,
                description: 'Pass "products" to include this category\'s products',
                schema: new OA\Schema(type: 'string', enum: ['products'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Category', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function show(Request $request, Category $category): JsonResponse
    {
        if ($request->query('with') === 'products') {
            $category->load(['products' => fn ($q) => $q->orderBy('created_at', 'desc')]);
        }

        return $this->successResponse(
            CategoryResource::make($category),
            'Category retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/categories',
        tags: ['Categories'],
        summary: 'Create a new category (Admin only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Toys'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Category created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Category', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->successResponse(
            CategoryResource::make($category),
            'Category created successfully',
            201
        );
    }

    #[OA\Put(
        path: '/api/categories/{slug}',
        tags: ['Categories'],
        summary: 'Update a category (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'toys'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Toys & Games'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Category', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['name'] !== $category->name) {
            $validated['slug'] = Category::uniqueSlug($validated['name'], $category->id);
        }

        $category->update($validated);

        return $this->successResponse(
            CategoryResource::make($category),
            'Category updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/categories/{slug}',
        tags: ['Categories'],
        summary: 'Delete a category (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'toys'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category deleted successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 409, description: 'Category still has products'),
        ]
    )]
    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return $this->errorResponse('Cannot delete a category that still has products.', 409);
        }

        $category->delete();

        return $this->successResponse(
            null,
            'Category deleted successfully'
        );
    }
}
