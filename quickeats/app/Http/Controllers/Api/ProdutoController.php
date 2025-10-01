<?php

namespace App\Http\Controllers\Api;

use App\Models\Produto;
use App\Http\Controllers\Controller;
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
        $produtos = DB::select('CALL listar_produtos()');

        $horaAtual = now()->format('H:i:s');
        $diaSemana = now()->dayOfWeekIso;

        $produtos = collect($produtos)->map(function ($produto) use ($horaAtual, $diaSemana) {
            $horario = DB::table('grades_horario')
                ->where('id_estab', $produto->id_estab)
                ->where('dia_semana', $diaSemana)
                ->first();

            $produto->estab_fechado = true;

            if ($horario && $horario->inicio_expediente && $horario->termino_expediente) {
                if ($horaAtual >= $horario->inicio_expediente && $horaAtual <= $horario->termino_expediente) {
                    $produto->estab_fechado = false;
                }
            }

            return $produto;
        });

        $produtosDisponiveis = $produtos->filter(function ($produto) {
            return !$produto->estab_fechado;
        })->values();

        // Retorna JSON
        return response()->json([
            'success' => true,
            'produtos' => $produtosDisponiveis
        ], 200);
    }
}
