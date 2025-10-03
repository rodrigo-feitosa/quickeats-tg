<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EnderecoCliente extends Model
{
    use HasFactory;

    protected $table = 'enderecos_clientes';

    public function cliente() {
        return $this->belongsTo(Pedido::class, 'id_cliente', 'id_cliente');
    }

    public function endereco() {
        return $this->belongsTo(Endereco::class, 'id_endereco', 'id_endereco');
    }
}
