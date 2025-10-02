<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusPedido extends Model
{
    protected $table = 'status_pedidos';
    protected $primaryKey = 'id_status';

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'status_entrega', 'id_status');
    }
}
