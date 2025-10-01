<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\EstabelecimentoController;
use App\Http\Controllers\Api\ProdutoController;
use Illuminate\Support\Facades\Route;

// rota de login comum
Route::get('/login', function () {
    return view('welcome');
})->name('login');

// grupo de rotas de uso comum
Route::post('usuario/login', [AuthController::class, 'realizarLogin'])->name('login_usuario');
// rotas protegidas com token Bearer
Route::middleware('auth:sanctum', 'ability:cliente,estabelecimento')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/lista-produtos-por-estab', [ProdutoController::class, 'listarProdutosPorEstab'])->name('listar_produtos_por_estab');
    Route::get('/lista-produtos-por-cat', [ProdutoController::class, 'listarProdutosPorCat'])->name('listar_produtos_por_cat');
});

// grupo de rotas dos clientes
Route::post('cliente/cadastrar', [ClienteController::class, 'realizarCadastro'])->name('cadastro_cliente');
// rotas protegidas com token Bearer
Route::middleware('auth:sanctum', 'ability:cliente')->group(function () {
    Route::get('/home-cliente', [ClienteController::class, 'exibirPaginaInicial'])->name('home_cliente');
    Route::get('/lista-produtos-disponiveis', [ProdutoController::class, 'listarProdutosDisponiveis'])->name('listar_produtos_disponiveis');
});

// grupo de rotas dos estabelecimentos
Route::post('estabelecimento/cadastrar', [EstabelecimentoController::class, 'realizarCadastro'])->name('cadastro_estabelecimento');
// rotas protegidas com token Bearer
Route::middleware('auth:sanctum', 'ability:estabelecimento')->group(function () {
    Route::get('/home-estabelecimento', [EstabelecimentoController::class, 'exibirPaginaInicial'])->name('home_estab');
});
