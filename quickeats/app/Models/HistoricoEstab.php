<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistoricoEstab extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_alteracao';
    protected $table = 'historico_estabelecimentos';

    protected $fillable = [
        'id_estab',
        'camp_alterado',
        'valor_antigo',
        'valor_novo',
        'data_alteracao'
    ];

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class, 'id_estab', 'id_estab');
    }
}
