<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    // Defina a chave primária, se não for 'id'
    protected $primaryKey = 'id_produto';

    // Define a tabela associada
    protected $table = 'produtos';

    // Define os campos que podem ser preenchidos em massa
    protected $fillable = [
        'id_produto',
        'nome',
        'descricao',
        'valor',
        'id_categoria',
        'id_estab',
        'qtd_estoque',
        'imagem_produto',
    ];

    // Desativa os timestamps automáticos
    public $timestamps = false;

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
