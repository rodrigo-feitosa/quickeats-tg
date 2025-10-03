<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanoEstab extends Model
{
    use HasFactory;

    protected $table = 'planos_estabelecimentos';

    protected $fillable = [
        'id_estab',
        'id_plano',
        'ativo'
    ];

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'id_plano', 'id_plano');
    }

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class, 'id_estab', 'id_estab');
    }
}
