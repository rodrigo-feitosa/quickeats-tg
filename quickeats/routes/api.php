<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\EstabelecimentoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// grupos de rotas de uso comum
Route::post('usuario/login', [AuthController::class, 'realizarLogin'])->name('login_usuario');

// grupo de rotas dos clientes
Route::post('cliente/cadastrar', [ClienteController::class, 'realizarCadastro'])->name('cadastro_cliente');

// grupo de rotas dos estabelecimentos
Route::post('estabelecimento/cadastrar', [EstabelecimentoController::class, 'realizarCadastro'])->name('cadastro_estabelecimento');
