<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Define a tabela associada
    protected $table = 'products';

    // Define os campos que podem ser preenchidos em massa
    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'establishment_id',
        'quantity',
        'image',
    ];

    // Desativa os timestamps automáticos
    public $timestamps = true;

    public function toApiArray()
    {
        return [
            'id_produto'   => $this->id_produto,
            'nome_produto' => $this->nome,
            'descricao'    => $this->descricao,
            'valor'        => $this->valor,
            'id_categoria' => $this->id_categoria,
            'categoria'    => $this->categoria->descricao ?? null,
            'id_estab'     => $this->id_estab,
            'qtd_estoque'  => $this->qtd_estoque,
            'estab'        => $this->estabelecimento->nome_fantasia ?? null,
            'imagem'       => $this->imagem_produto,
        ];
    }

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class, 'id_estab', 'id_estab');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProduto::class, 'id_categoria', 'id_categoria');
    }

    public function itensPedido()
    {
        return $this->hasMany(ItensPedido::class, 'id_produto', 'id_produto');
    }

    public function produtoFavorito()
    {
        return $this->hasMany(ProdutoFavorito::class, 'id_produto', 'id_produto');
    }
}
