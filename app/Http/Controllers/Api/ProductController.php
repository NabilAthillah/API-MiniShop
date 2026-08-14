<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Products', description: 'Browse products')]
class ProductController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/products',
        tags: ['Products'],
        summary: 'List all products, ordered by newest first',
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Search by product name or description',
                schema: new OA\Schema(type: 'string'),
                example: 'wireless mouse'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Product')
                            ),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['category', 'images'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/products/{slug}',
        tags: ['Products'],
        summary: 'Get a single product by slug',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'wireless-mouse'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Product', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'images']);

        return $this->successResponse(
            ProductResource::make($product),
            'Product retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/products',
        tags: ['Products'],
        summary: 'Create a new product (Admin only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'price', 'description', 'category_id'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Wireless Mouse'),
                        new OA\Property(property: 'price', type: 'integer', example: 150000),
                        new OA\Property(property: 'description', type: 'string', example: 'A smooth wireless mouse.'),
                        new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'stock', type: 'integer', example: 25),
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'Image files (jpg/png/etc, max 5MB each)'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Product', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $product = Product::create($validated);
        $this->storeImages($product, $images);
        $product->load(['category', 'images']);

        return $this->successResponse(
            ProductResource::make($product),
            'Product created successfully',
            201
        );
    }

    #[OA\Put(
        path: '/api/products/{slug}',
        tags: ['Products'],
        summary: 'Update a product (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'wireless-mouse'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'price', 'description', 'category_id'],
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Laravel method spoofing (required since file uploads need POST)'),
                        new OA\Property(property: 'name', type: 'string', example: 'Wireless Mouse Pro'),
                        new OA\Property(property: 'price', type: 'integer', example: 175000),
                        new OA\Property(property: 'description', type: 'string', example: 'A smooth wireless mouse.'),
                        new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'stock', type: 'integer', example: 25),
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'New image files to add (jpg/png/etc, max 5MB each)'
                        ),
                        new OA\Property(
                            property: 'delete_image_ids',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'uuid'),
                            description: 'IDs of existing images to remove'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Product', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();
        $newImages = $validated['images'] ?? [];
        $deleteImageIds = $validated['delete_image_ids'] ?? [];
        unset($validated['images'], $validated['delete_image_ids']);

        if ($validated['name'] !== $product->name) {
            $validated['slug'] = Product::uniqueSlug($validated['name'], $product->id);
        }

        $product->update($validated);

        if (! empty($deleteImageIds)) {
            $this->deleteImages($product, $deleteImageIds);
        }

        $maxOrder = $product->images()->max('order');
        $this->storeImages($product, $newImages, is_null($maxOrder) ? 0 : $maxOrder + 1);
        $product->load(['category', 'images']);

        return $this->successResponse(
            ProductResource::make($product),
            'Product updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/products/{slug}',
        tags: ['Products'],
        summary: 'Delete a product (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'wireless-mouse'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product deleted successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 409, description: 'Product has existing transactions'),
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        if ($product->transactionProducts()->exists()) {
            return $this->errorResponse('Cannot delete a product that has existing transactions.', 409);
        }

        $this->deleteImageFiles($product->images);
        $product->delete();

        return $this->successResponse(
            null,
            'Product deleted successfully'
        );
    }

    /**
     * Store uploaded image files for a product and create their ProductImage records.
     *
     * @param  array<\Illuminate\Http\UploadedFile>  $files
     */
    private function storeImages(Product $product, array $files, int $startOrder = 0): void
    {
        foreach (array_values($files) as $index => $file) {
            $path = $file->store("products/{$product->id}", 'public');

            $product->images()->create([
                'path' => Storage::disk('public')->url($path),
                'alt' => $product->name,
                'order' => $startOrder + $index,
            ]);
        }
    }

    /**
     * Delete the given product images: their files from storage and their records.
     *
     * @param  array<string>  $imageIds
     */
    private function deleteImages(Product $product, array $imageIds): void
    {
        $images = $product->images()->whereIn('id', $imageIds)->get();

        $this->deleteImageFiles($images);

        $product->images()->whereIn('id', $imageIds)->delete();
    }

    /**
     * Remove the stored files backing the given product images, if they live on the public disk.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\ProductImage>  $images
     */
    private function deleteImageFiles($images): void
    {
        $baseUrl = Storage::disk('public')->url('');

        foreach ($images as $image) {
            if (str_starts_with($image->path, $baseUrl)) {
                Storage::disk('public')->delete(Str::after($image->path, $baseUrl));
            }
        }
    }
}
