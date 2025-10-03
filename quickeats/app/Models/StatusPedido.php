<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StatusPedido extends Model
{
    use HasFactory;

    protected $table = 'status_pedidos';
    protected $primaryKey = 'id_status';

    public function pedido()
    {
        return $this->hasMany(Pedido::class, 'status_entrega', 'id_status');
    }
}
