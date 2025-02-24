<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct()
    {
        // Definindo middleware para controlar as permissões
        $this->middleware(['auth.jwt:super'])->only(['store', 'update', 'destroy']);
        $this->middleware(['auth.jwt:super,team'])->only(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        try {
            // Buscar todos os usuários ou aplicar filtros
            $users = User::all();
            return response()->json($users, Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Failed to retrieve users'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        try {
            // Validação dos dados recebidos
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'terms' => 'required|string',
                'doc_number' => 'nullable|numeric',  // Doc number é opcional
                'date_birth' => 'nullable|date',
                'gender' => 'nullable|in:f,m,o',
                'receive_email_notifications' => 'nullable|boolean',
                'date_agree_terms' => 'nullable|date',
            ]);

            // Gerar um código aleatório de 12 dígitos
            $randomCode = rand(100000000000, 999999999999);
            $password = $this->decodeBase64($validatedData['password']);

            // Criar o usuário
            $user = User::create([
                'code' => $randomCode,
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

            // Retornar resposta de sucesso
            return response()->json([
                'message' => 'User created successfully.',
                'user' => $user,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Failed to create user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        try {
            return response()->json($user, Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): Response
    {
        try {
            // Validação dos dados recebidos
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6',
                'doc_number' => 'nullable|numeric',  // Doc number é opcional
                'date_birth' => 'nullable|date',
                'gender' => 'nullable|in:f,m,o',
                'receive_email_notifications' => 'nullable|boolean',
                'date_agree_terms' => 'nullable|date',
            ]);

            // Atualizar os dados do usuário
            $user->update($validatedData);

            // Se a senha foi fornecida, atualizá-la
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
                $user->save();
            }

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $user,
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Failed to update user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): Response
    {
        try {
            // Excluir o usuário
            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully.',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Failed to delete user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
