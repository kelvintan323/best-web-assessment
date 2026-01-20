<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        $user->bearerToken = explode('Bearer ', $request->header('Authorization'))[1];

        return $this->response(['user' => $user]);
    }

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

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        return $this->response(['message' => 'Logged out success']);
    }
}
