<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Get(
        path: '/me',
        summary: 'Get current authenticated user',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current user details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'user', properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Admin'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                                new OA\Property(property: 'bearerToken', type: 'string', example: '1|abc123...')
                            ], type: 'object')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function me(Request $request)
    {
        $user = $request->user();
        $user->bearerToken = explode('Bearer ', $request->header('Authorization'))[1];

        return $this->response(['user' => $user]);
    }

    #[OA\Post(
        path: '/login',
        summary: 'Admin login',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@admin.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'user', properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Admin'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@admin.com'),
                                new OA\Property(property: 'bearerToken', type: 'string', example: '1|abc123...')
                            ], type: 'object')
                        ], type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid credentials')
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        if (!$user = Admin::where('email', $request->email)->first()) {
            return $this->response(
                message: 'Email not found. Please try again.',
                statusCode: 400
            );
        }

        $user->bearerToken = $user->createToken($user->email)->plainTextToken;

        if (!Hash::check($request->password, $user->password)) {
            return $this->response(
                message: 'Invalid email or password.',
                statusCode: 400
            );
        }

        return $this->response([
            'user' => $user,
        ]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Logout and invalidate token',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'Logged out success')
                        ], type: 'object')
                    ]
                )
            )
        ]
    )]
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        return $this->response(['message' => 'Logged out success']);
    }
}
