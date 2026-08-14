<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Registration, login, and profile')]
class AuthenticationController extends Controller
{
    use ApiResponse;

    #[OA\Post(
        path: '/api/auth/register',
        tags: ['Auth'],
        summary: 'Register a new customer account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'phone', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'Password123!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registration successful',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/User', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $customerRole = Role::where('name', 'Customer')->firstOrFail();

        $user = User::create([
            ...$request->safe()->except('password_confirmation'),
            'role_id' => $customerRole->id,
        ]);

        $user->setRelation('role', $customerRole);

        return $this->successResponse(
            UserResource::make($user),
            'Registration successful, please login',
            201
        );
    }

    #[OA\Post(
        path: '/api/auth/login',
        tags: ['Auth'],
        summary: 'Login and obtain a Sanctum API token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessResponse'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', properties: [
                                new OA\Property(property: 'user', ref: '#/components/schemas/User', type: 'object'),
                                new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456'),
                            ], type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('These credentials do not match our records.', 401);
        }

        $user = Auth::user()->load('role');
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->successResponse([
            'user' => UserResource::make($user),
            'token' => $token,
        ], 'Login successful');
    }

    #[OA\Get(
        path: '/api/auth/profile',
        tags: ['Auth'],
        summary: 'Get the authenticated user\'s profile',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile retrieved successfully',
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
        ]
    )]
    public function profile(Request $request): JsonResponse
    {
        return $this->successResponse(
            UserResource::make($request->user()->load('role')),
            'Profile retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/auth/profile',
        tags: ['Auth'],
        summary: 'Update the authenticated user\'s profile',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '081234567890'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
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
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->successResponse(
            UserResource::make($user->load('role')),
            'Profile updated successfully'
        );
    }

    #[OA\Put(
        path: '/api/auth/password',
        tags: ['Auth'],
        summary: 'Change the authenticated user\'s password',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewPassword123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewPassword123!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error, or current password incorrect'),
        ]
    )]
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            return $this->errorResponse('The current password is incorrect.', 422);
        }

        $user->update(['password' => $request->validated('password')]);

        return $this->successResponse(null, 'Password updated successfully');
    }
}
