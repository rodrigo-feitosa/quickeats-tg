<?php

namespace App\Http\Controllers\Api;

use App\Models\Produto;
use App\Http\Controllers\Controller;
use App\Models\ProdutoFavorito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdutoController extends Controller
{
    public function listarProdutosPorEstab(Request $request)
    {
        $request->validate(['idEstab' => 'required|integer']);

        $produtos = Produto::where('id_estab', $request->idEstab)->get();

        return response()->json([
            'success' => true,
            'data' => $produtos
        ], 200);
    }

    public function listarProdutosPorCat(Request $request)
    {
        $request->validate(['idCategoria' => 'required|integer']);
        $produtos = Produto::where('id_categoria', $request->idCategoria)->get();

        return response()->json([
            'success' => true,
            'data' => $produtos
        ], 200);
    }

    public function listarProdutosDisponiveis()
    {
        $produtos = Produto::with(['categoria', 'estabelecimento.gradesHorario'])
            ->where('qtd_estoque', '>', 0)
            ->whereHas('estabelecimento', function ($query) {
                $query->where('email_verificado', 1)
                      ->where('perfil_ativo', 1);
            })
            ->get()
            ->filter(function ($produto) {
                return $produto->estabelecimento && $produto->estabelecimento->abertoAgora();
            })
            ->map->toApiArray()
            ->values();

        return response()->json([
            'success' => true,
            'produtos' => $produtos
        ], 200);
    }

    public function listarProdutosPopulares()
    {
        $prodPopulares = DB::select("SELECT * FROM produtos_populares");

        return response()->json([
            'success' => true,
            'produtos' => $prodPopulares,
        ], 200);
    }
}
