<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\EstabelecimentoController;
use Illuminate\Support\Facades\Route;

// rota de login comum
Route::get('/login', function () {
    return view('welcome');
})->name('login');

// grupo de rotas de uso comum
Route::post('usuario/login', [AuthController::class, 'realizarLogin'])->name('login_usuario');

// grupo de rotas dos clientes
Route::post('cliente/cadastrar', [ClienteController::class, 'realizarCadastro'])->name('cadastro_cliente');

// rota protegida com token Bearer
Route::middleware('auth:api')->group(function () {
    Route::get('/home-cliente', [ClienteController::class, 'exibirPaginaInicial'])->name('home_cliente');
    Route::get('/logout-cliente', [AuthController::class, 'logout'])->name('logout_cliente');
});

// grupo de rotas dos estabelecimentos
Route::post('estabelecimento/cadastrar', [EstabelecimentoController::class, 'realizarCadastro'])->name('cadastro_estabelecimento');
