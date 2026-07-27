<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\EstabelecimentoController;
use App\Http\Controllers\Api\ProdutoController;
use Illuminate\Support\Facades\Route;


Route::get('/lista-produtos-populares', [ProdutoController::class, 'listarProdutosPopulares'])->name('listar_produtos_populares');
Route::get('/lista-estab-populares', [EstabelecimentoController::class, 'listarEstabPopulares'])->name('listar_estab_populares');

// rota de login comum
Route::get('/login', function () {
    // Chama o método errorResponse
    return response()->json(['error' => 'Unauthorized'], 401)->header('Content-Type', 'application/json');
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
Route::post('customer/register', [CustomerController::class, 'store']);
// rotas protegidas com token Bearer
Route::middleware('auth:sanctum', 'ability:cliente')->group(function () {
    Route::get('/lista-produtos-disponiveis', [ProdutoController::class, 'listarProdutosDisponiveis'])->name('listar_produtos_disponiveis');
});

// grupo de rotas dos estabelecimentos
Route::post('estabelecimento/cadastrar', [EstabelecimentoController::class, 'realizarCadastro'])->name('cadastro_estabelecimento');
// rotas protegidas com token Bearer
Route::middleware('auth:sanctum', 'ability:estabelecimento')->group(function () {
});
