<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistoricoCliente extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_alteracao';
    protected $table = 'historico_clientes';

    protected $fillable = [
        'id_cliente',
        'camp_alterado',
        'valor_antigo',
        'valor_novo',
        'data_alteracao'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
