<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaProduto extends Model
{
    use HasFactory;

    protected $table = 'categorias_produtos';
    protected $primaryKey = 'id_categoria';

    public function produto()
    {
        return $this->hasMany(Produto::class, 'id_categoria', 'id_categoria');
    }
}
