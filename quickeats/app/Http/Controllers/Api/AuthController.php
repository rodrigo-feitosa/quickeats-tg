<?php

namespace App\Http\Controllers\Api;

use App\Models\Cliente;
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

        $emailVerificado = Cliente::where('email', $validatedData['emailLogin'])->where('email_verificado', 1)->first();
        $perfilAtivo = Cliente::where('email', $validatedData['emailLogin'])->where('perfil_ativo', 1)->first();

        if (Auth::guard('cliente')->attempt(['email' => $validatedData['emailLogin'], 'password' => $validatedData['senhaLogin']])) {
            if ($perfilAtivo) {
                if ($emailVerificado) {
                    $cliente = Auth::guard('cliente')->user();

                    return response()->json([
                        'success' => true,
                        'message' => 'Login realizado com sucesso!',
                        'cliente' => $cliente,
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email não verificado!',
                    ], 403);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Seu perfil está desativado',
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Email ou senha inválidos',
            ], 401);
        }
    }
}
