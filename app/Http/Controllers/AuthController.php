<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function store(Request $request): Response
    {
        try {

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'terms' => 'required|string',
                'doc_number' => 'nullable|numeric',
                'date_birth' => 'nullable|date',
                'gender' => 'nullable|in:f,m,o',
                'receive_email_notifications' => 'nullable|boolean',
                'date_agree_terms' => 'nullable|date',
            ]);

            $randomCode = rand(100000000000, 999999999999);
            $password = $this->decodeBase64($validatedData['password']);

            $user = User::create([
                'code' =>  $randomCode,
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($password),
                'doc_number' => $validatedData['doc_number'] ?? null,
                'date_birth' => $validatedData['date_birth'] ?? null,
                'gender' => $validatedData['gender'] ?? null,
                'receive_email_notifications' => $validatedData['receive_email_notifications'] ?? true,
                'date_agree_terms' => $validatedData['date_agree_terms'] ?? now(),
                'terms' => $validatedData['terms'],
                'enabled' => true,
                'role' => 'user',
            ]);

            $credentials = $this->validateCredentials($request);

            if (!Auth::validate($credentials)) {
                return response()->json(['error' => 'Invalid credentials. Please check your email and password.'], Response::HTTP_UNAUTHORIZED);
            }

            if (!$token = auth('api')->attempt($credentials)) {
                return response()->json(['error' => 'Failed to generate token.'], Response::HTTP_BAD_REQUEST);
            }

            $additionalToken = [
                'user' => $user->only(['id', 'name', 'enabled', 'role']),
            ];

            $token = auth('api')->claims($additionalToken)->attempt($credentials);

            return $this->response_token($token, $user);

            // return response()->json([
            //     'message' => 'User created successfully.',
            //     'user' => $user,
            // ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get token for user authentication
     */
    public function getToken(Request $request): Response
    {
        return $this->getTokenByRole($request, 'user');
    }

    /**
     * Get token for admin or team role authentication
     */
    public function getTokenAdmin(Request $request): Response
    {
        return $this->getTokenByRole($request, ['super', 'team']);
    }

    /**
     * Function to generate token based on the role of the user
     */
    private function getTokenByRole(Request $request, $role): Response
    {
        try {
            $credentials = $this->validateCredentials($request);

            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                return response()->json(['error' => 'Invalid credentials. Please check your email and password.'], Response::HTTP_NOT_FOUND);
            }

            if (!$user->enabled) {
                return response()->json(['error' => 'Your account is disabled. Please contact support.'], Response::HTTP_UNAUTHORIZED);
            }

            if (is_array($role) && !in_array($user->role, $role)) {
                return response()->json(['error' => 'You do not have the required permissions.'], Response::HTTP_UNAUTHORIZED);
            }

            if (!Auth::validate($credentials)) {
                return response()->json(['error' => 'Invalid credentials. Please check your email and password.'], Response::HTTP_UNAUTHORIZED);
            }

            if (!$token = auth('api')->attempt($credentials)) {
                return response()->json(['error' => 'Failed to generate token.'], Response::HTTP_BAD_REQUEST);
            }

            $additionalToken = [
                'user' => $user->only(['id', 'name', 'enabled', 'role']),
            ];

            $token = auth('api')->claims($additionalToken)->attempt($credentials);

            return $this->response_token($token, $user);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Internal server error. Please try again later.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify and authenticate token for user
     */
    public function verifyToken(Request $request): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->enabled) {
                return response()->json(['error' => 'Your account is disabled.'], Response::HTTP_UNAUTHORIZED);
            }

            return response()->json($user, Response::HTTP_ACCEPTED);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Failed to authenticate token.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify and authenticate token for admin or team role
     */
    public function verifyTokenAdmin(Request $request): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->enabled) {
                return response()->json(['error' => 'Your account is disabled.'], Response::HTTP_UNAUTHORIZED);
            }

            if (!in_array($user->role, ['super', 'team'])) {
                return response()->json(['error' => 'You do not have the required permissions.'], Response::HTTP_UNAUTHORIZED);
            }

            return response()->json($user, Response::HTTP_ACCEPTED);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Failed to authenticate token.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get authenticated user details
     */
    public function getAuth(Request $request): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'User not found.'], Response::HTTP_BAD_REQUEST);
            }

            return response()->json($user, Response::HTTP_ACCEPTED);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Internal server error. Please try again later.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper function to validate user credentials
     */
    private function validateCredentials(Request $request): array
    {
        $email = strtolower($request->email);
        $password = $this->decodeBase64($request->password);

        return [
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * Helper function to generate response token
     */
    protected function response_token($token, $user = null): Response
    {
        $data = [
            'token' => $token,
            'type' => 'Bearer'
        ];

        if ($user !== null) {
            $data['user'] = $user;
        }

        return response()->json($data, Response::HTTP_ACCEPTED);
    }

    /**
     * Verifica se o texto está codificado em base64 e decodifica.
     *
     * @param string $text
     * @return string
     */
    function decodeBase64($text): string
    {
        if (base64_encode(base64_decode($text, true)) === $text) {
            return base64_decode($text);
        }

        return $text;
    }
}
