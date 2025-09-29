<?php

namespace App\Http\Controllers\Api;

use App\Models\Estabelecimento;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Rules\validaCelular;
use App\Rules\validaCNPJ;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\ConfirmacaoEmail;
use App\Mail\ConfirmaEmail;

class EstabelecimentoController extends Controller
{
    public function realizarCadastro(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nomeFantasiaSignup' => 'required|string|max:55',
                'cnpjSignup' => ['required', new validaCNPJ, 'unique:estabelecimentos,cnpj'],
                'telefoneSignup' => ['required', new validaCelular],
                'logradouroSignup' => 'required|string|max:100',
                'numeroSignup' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
                'bairroSignup' => 'required|string|max:100',
                'cidadeSignup' => 'required|string|max:100',
                'estadoSignup' => 'required|string|max:2',
                'cepSignup' => 'required|string|min:9|max:9',
                'emailSignup' => 'required|string|email|max:255|unique:estabelecimentos,email',
                'senhaSignup' => 'required|string|min:8',
            ], [
                'cnpjSignup.unique' => 'Este CNPJ já está cadastrado.',
                'emailSignup.unique' => 'Este e-mail já está cadastrado.',
                'cepSignup.min' => 'CEP deve ter no mínimo 8 caracteres',
                'senhaSignup.min' => 'A senha deve ter no mínimo 8 caracteres',
                'numeroSignup.regex' => 'O número deve conter apenas letras e números, sem caracteres especiais ou números negativos.',
            ]);

            $estabelecimento = Estabelecimento::cadastrarEstabelecimento($validatedData);

            if (!$estabelecimento) {
                return response()->json([
                    'error' => 'Erro ao cadastrar estabelecimento. Tente novamente.'
                ], 500);
            }

            $token = Str::random(60);

            $confirmacao = ConfirmacaoEmail::create([
                'email' => $estabelecimento->email,
                'token' => $token,
                'criado_em' => now(),
                'id_usuario' => $estabelecimento->id_estab,
                'tipo_usuario' => 'estabelecimento',
            ]);

            if (!$confirmacao) {
                return response()->json([
                    'error' => 'Erro ao cadastrar estabelecimento. Tente novamente.'
                ], 500);
            }


            // try {
            //     Mail::to($estabelecimento->email)->send(new ConfirmaEmail($token, $estabelecimento->email, 'estabelecimento'));
            // } catch (\Exception $e) {
            //     return response()->json([
            //         'error' => 'Erro ao enviar e-mail de confirmação.'
            //     ], 500);
            // }


            return response()->json([
                'success' => 'Estabelecimento cadastrado com sucesso.'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(), // mensagem específica do erro
                'trace' => $e->getTrace()    // opcional: mostra o trace completo
            ], 500);
        }
    }
}
