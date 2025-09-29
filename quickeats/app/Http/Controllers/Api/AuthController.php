<?php

namespace App\Http\Controllers\Api;

use App\Models\Cliente;
use App\Models\Estabelecimento;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function realizarLogin(Request $request)
    {
        $validatedData = $request->validate([
            'emailLogin' => 'required|string|email|max:255',
            'senhaLogin' => 'required|string|min:8',
        ]);

        $email = $validatedData['emailLogin'];
        $senha = $validatedData['senhaLogin'];

        $ativoVerificado = function ($model, $email) {
            return $model::where('email', $email)
            ->where('email_verificado', 1)
            ->where('perfil_ativo', 1)
            ->first();
        };

        $cliente = $ativoVerificado(Cliente::class, $email);
        if ($cliente && Auth::guard('cliente')->attempt(['email' => $email, 'password' => $senha]))
        {
            return response()->json([
                'success' => true,
                'message' => 'Login do cliente realizado com sucesso!',
                'cliente' => $cliente,
            ]);
        }

        $estabelecimento = $ativoVerificado(Estabelecimento::class, $email);
        if (Auth::guard('estabelecimento')->attempt(['email' => $validatedData['emailLogin'], 'password' => $validatedData['senhaLogin']]))
        {
            if ($estabelecimento && Auth::guard('estabelecimento')->attempt(['email' => $email, 'password' => $senha]))
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Login do estabelecimento realizado com sucesso!',
                    'cliente' => $estabelecimento,
                ]);
            }
        }

        // Verifica motivo do erro
        if (!$cliente && !$estabelecimento) {
            return response()->json([
                'success' => false,
                'message' => 'Email não encontrado ou não verificado.',
            ], 403);
        }

        if (($cliente && !$cliente->email_verificado) || ($estabelecimento && !$estabelecimento->email_verificado)) {
            return response()->json([
                'success' => false,
                'message' => 'Email não verificado!',
            ], 403);
        }

        if (($cliente && !$cliente->perfil_ativo) || ($estabelecimento && !$estabelecimento->perfil_ativo)) {
            return response()->json([
                'success' => false,
                'message' => 'Seu perfil está desativado.',
            ], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email ou senha inválidos.',
        ], 401);
    }
}
