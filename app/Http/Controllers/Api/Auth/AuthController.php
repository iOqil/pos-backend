<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(path: "/api/auth/login", summary: "Login", tags: ["Auth"])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                new OA\Property(property: "password", type: "string", format: "password", example: "password")
            ]
        )
    )]
    #[OA\Response(response: 200, description: "Successful login")]
    #[OA\Response(response: 401, description: "Invalid credentials")]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is inactive'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    #[OA\Post(path: "/api/auth/logout", summary: "Logout", security: [["sanctum" => []]], tags: ["Auth"])]
    #[OA\Response(response: 200, description: "Successfully logged out")]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    #[OA\Get(path: "/api/auth/me", summary: "Get current user", security: [["sanctum" => []]], tags: ["Auth"])]
    #[OA\Response(response: 200, description: "User details")]
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        if (Hash::check($request->password, $request->user()->password)) {
            return response()->json(['message' => 'Password verified'], 200);
        }

        return response()->json(['message' => 'Invalid password'], 400);
    }
}
