<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Pedido extends Model
{
    use HasFactory;

    // Defina a chave primária, se não for 'id'
    protected $primaryKey = 'id_pedido';

    // Define a tabela associada
    protected $table = 'pedidos';

    // Define os campos que podem ser preenchidos em massa
    protected $fillable = [
        'id_pedido',
        'id_cliente',
        'valor_total',
        'forma_pagamento',
        'data_compra',
        'status_entrega',
        'endereco',
        'payment_intent_id',
    ];

    // Desativa os timestamps automáticos
    public $timestamps = false;

    public static function realizarPedido($id_cliente, $id_endereco, $id_pagamento, $payment_intent_id)
    {
        try {
            return DB::select('CALL realizar_pedido(?, ?, ?, ?)', [
                $id_cliente,
                $id_endereco,
                $id_pagamento,
                $payment_intent_id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro na procedure realizar_pedido: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            throw $e; // Repassa a exceção para a controller capturar
        }
    }

    public function statusPedido() {
        return $this->belongsTo(StatusPedido::class, 'status_entrega', 'id_status');
    }

    public function formasPagamento() {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento', 'id_formapag');
    }

    public function endereco() {
        return $this->belongsTo(Endereco::class, 'endereco', 'id_endereco');
    }

    public function cliente() {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function itensPedido() {
        return $this->belongsTo(ItensPedido::class, 'id_pedido', 'id_pedido');
    }

    public function avaliacao() {
        return $this->belongsTo(Avaliacao::class, 'id_pedido', 'id_pedido');
    }
}
