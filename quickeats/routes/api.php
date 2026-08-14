<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\EstablishmentController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// rota de login comum
Route::get('/login', function () {
    // Chama o método errorResponse
    return response()->json(['error' => 'Unauthorized'], 401)->header('Content-Type', 'application/json');
})->name('login');

// grupo de rotas de uso comum
Route::post('user/login', [AuthController::class, 'login']);

// grupo de rotas dos estabelecimentos
Route::post('establishment/register', [EstablishmentController::class, 'store']);
// rotas protegidas com token Bearer
Route::middleware('auth:sanctum', 'ability:cliente,estabelecimento')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});

// grupo de rotas dos clientes
Route::post('customer/register', [CustomerController::class, 'store']);
// rotas protegidas com token Bearer
Route::middleware('auth:sanctum', 'ability:cliente')->group(function () {
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });
});

// grupo de rotas dos estabelecimentos
Route::post('estabelecimento/cadastrar', [EstablishmentController::class, 'realizarCadastro'])->name('cadastro_estabelecimento');
// rotas protegidas com token Bearer
Route::middleware('auth:sanctum', 'ability:estabelecimento')->group(function () {
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
});
