<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItensPedido extends Model
{
    use HasFactory;

    protected $table = 'itens_pedidos';

    protected $fillable = [
        'id_pedido',
        'id_produto',
        'qtd_produto'
    ];

    public function pedido() {
        return $this->belongsTo(Pedido::class, 'id_pedido', 'id_pedido');
    }

    public function produto() {
        return $this->belongsTo(Produto::class, 'id_produto', 'id_produto');
    }
}
