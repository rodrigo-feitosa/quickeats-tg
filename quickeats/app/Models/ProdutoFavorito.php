<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class ProdutoFavorito extends Model
{
    use HasFactory;

    // Define a tabela associada
    protected $table = 'produtos_favoritos';

    // Define os campos que podem ser preenchidos em massa
    protected $fillable = [
        'id_produto',
        'id_cliente',
    ];

    // Desativa os timestamps automáticos
    public $timestamps = false;

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'id_produto', 'id_produto');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
