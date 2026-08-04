<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where([
            ['email', '=', $request->email],
            ['active', '=', true]
        ])->first();

        // 2. Se o usuário não existir ou a senha estiver errada
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais inválidas ou conta não verificada.',
            ], 401);
        }

        /// 3. Define as habilidades (abilities) de acordo com o tipo de usuário
        $abilities = [$user->user_type];
        $tokenName = $user->user_type . '_token';

        // 4. Cria o token via Sanctum
        $token = $user->createToken($tokenName, $abilities)->plainTextToken;

        return response()->json([
            'success'    => true,
            'message'    => "Login de {$user->user_type} realizado com sucesso!",
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        try {
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário autenticado encontrado.',
                ], 401);
            }

            // Revoga o token que está sendo usado na requisição
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar logout: Falha ao revogar o token.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }
}
