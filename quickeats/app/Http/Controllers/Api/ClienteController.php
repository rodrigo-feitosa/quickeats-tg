<?php

namespace App\Http\Controllers\Api;

use App\Models\Cliente;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Rules\validaData;
use App\Rules\validaCelular;
use App\Rules\validaCPF;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\ConfirmacaoEmail;
use App\Mail\ConfirmaEmail;

class ClienteController extends Controller
{
    public function realizarCadastro(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nomeSignup' => 'required|string|max:50',
                'cpfSignup' => ['required', new validaCPF, 'unique:clientes,cpf'],
                'dataNascSignup' => ['required', new validaData],
                'telefoneSignup' => ['required', new validaCelular],
                'emailSignup' => 'required|string|email|max:100|unique:clientes,email',
                'senhaSignup' => 'required|string|min:8',
            ], [
                'cpfSignup.unique' => 'Este CPF já está cadastrado.',
                'emailSignup.unique' => 'Este e-mail já está cadastrado.',
                'senhaSignup.min' => 'A senha deve ter no mínimo 8 caracteres',
            ]);

            $cliente = Cliente::cadastrarCliente($validatedData);

            if (!$cliente) {
                return response()->json([
                    'error' => 'Erro ao cadastrar cliente. Tente novamente.'
                ], 500);
            }

            $token = Str::random(60);

            $confirmacao = ConfirmacaoEmail::create([
                'email' => $cliente->email,
                'token' => $token,
                'criado_em' => now(),
                'id_usuario' => $cliente->id_cliente,
                'tipo_usuario' => 'cliente',
            ]);

            if (!$confirmacao) {
                return response()->json([
                    'error' => 'Erro ao cadastrar cliente. Tente novamente.'
                ], 500);
            }


            try {
                Mail::to($cliente->email)->send(new ConfirmaEmail($token, $cliente->email, 'cliente'));
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Erro ao enviar e-mail de confirmação.'
                ], 500);
            }


            return response()->json([
                'success' => 'Cliente cadastrado com sucesso. (Envio de e-mail desativado nesta versão)'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocorreu um erro inesperado. Tente novamente.'
            ], 500);
        }
    }
}
