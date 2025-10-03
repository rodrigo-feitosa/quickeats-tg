<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Avaliacao extends Model
{
    use HasFactory;

    // Defina a chave primária, se não for 'id'
    protected $primaryKey = 'id_avaliacao';

    // Define a tabela associada
    protected $table = 'avaliacoes';

    // Define os campos que podem ser preenchidos em massa
    protected $fillable = [
        'id_avaliacao',
        'id_pedido',
        'nota'
    ];

    // Desativa os timestamps automáticos
    public $timestamps = false;

    public static function avaliarPedido($id_cliente, $pedido, $nota)
    {
        return DB::select('CALL avaliar_pedido(?, ?, ?)', [
            $id_cliente,
            $pedido,
            $nota,
        ]);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'id_pedido', 'id_pedido');
    }
}
