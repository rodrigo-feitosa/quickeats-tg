<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Endereco extends Model
{
    use HasFactory;

    // Defina a chave primária, se não for 'id'
    protected $primaryKey = 'id_endereco';

    // Define a tabela associada
    protected $table = 'enderecos';

    // Define os campos que podem ser preenchidos em massa
    protected $fillable = [
        'id_endereco',
        'logradouro',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep'
    ];

    // Desativa os timestamps automáticos
    public $timestamps = false;

    public static function cadastrar($id_cliente, $logradouro, $numero, $bairro, $cidade, $estado, $cep)
    {
        return DB::select('CALL cadastrar_endereco(?, ?, ?, ?, ?, ?, ?)', [
            $id_cliente,
            $logradouro,
            $numero,
            $bairro,
            $cidade,
            $estado,
            $cep
        ]);
    }

    public function enderecoCliente()
    {
        return $this->hasMany(enderecoCliente::class, 'id_endereco', 'id_endereco');
    }

    public function pedido()
    {
        return $this->hasMany(Pedido::class, 'id_endereco', 'id_endereco');
    }
}
