<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Banners', description: 'Browse and manage homepage banners')]
class BannerController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/banners',
        tags: ['Banners'],
        summary: 'List all banners',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banners retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Banner')
                            ),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $banners = Banner::query()
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            BannerResource::collection($banners),
            'Banners retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/banners/{id}',
        tags: ['Banners'],
        summary: 'Get a single banner',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banner retrieved successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Banner', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Banner not found'),
        ]
    )]
    public function show(Banner $banner): JsonResponse
    {
        return $this->successResponse(
            BannerResource::make($banner),
            'Banner retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/banners',
        tags: ['Banners'],
        summary: 'Create a new banner (Admin only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image'],
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Image file (max 5MB)'),
                        new OA\Property(property: 'alt', type: 'string', nullable: true, example: 'Summer sale banner'),
                        new OA\Property(property: 'url', type: 'string', nullable: true, example: '/products/wireless-mouse'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Banner created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Banner', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreBannerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['image'] = Storage::disk('public')->url(
            $request->file('image')->store('banners', 'public')
        );

        $banner = Banner::create($validated);

        return $this->successResponse(
            BannerResource::make($banner),
            'Banner created successfully',
            201
        );
    }

    #[OA\Put(
        path: '/api/banners/{id}',
        tags: ['Banners'],
        summary: 'Update a banner (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Laravel method spoofing (required since file uploads need POST)'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Leave empty to keep the current image'),
                        new OA\Property(property: 'alt', type: 'string', nullable: true, example: 'Summer sale banner'),
                        new OA\Property(property: 'url', type: 'string', nullable: true, example: '/products/wireless-mouse'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banner updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Banner', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Banner not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImageFile($banner->image);
            $validated['image'] = Storage::disk('public')->url(
                $request->file('image')->store('banners', 'public')
            );
        }

        $banner->update($validated);

        return $this->successResponse(
            BannerResource::make($banner),
            'Banner updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/banners/{id}',
        tags: ['Banners'],
        summary: 'Delete a banner (Admin only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banner deleted successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an Admin'),
            new OA\Response(response: 404, description: 'Banner not found'),
        ]
    )]
    public function destroy(Banner $banner): JsonResponse
    {
        $this->deleteImageFile($banner->image);
        $banner->delete();

        return $this->successResponse(
            null,
            'Banner deleted successfully'
        );
    }

    private function deleteImageFile(string $imagePath): void
    {
        $baseUrl = Storage::disk('public')->url('');

        if (str_starts_with($imagePath, $baseUrl)) {
            Storage::disk('public')->delete(Str::after($imagePath, $baseUrl));
        }
    }
}
