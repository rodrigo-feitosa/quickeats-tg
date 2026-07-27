<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Importando Authenticatable para autenticação
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Customer extends Authenticatable
{
    use HasFactory, HasApiTokens;

    // Define a tabela associada
    protected $table = 'customers';

    // Define os campos que podem ser preenchidos em massa
    protected $fillable = [
        'name',
        'cpf',
        'date_of_birth',
        'phone',
        'user_id',
    ];

    // Desativa os timestamps automáticos
    public $timestamps = false;
    

    // Adiciona a função getAuthPassword para autenticação
    public function getAuthPassword()
    {
        return $this->senha;
    }

    // Método para cadastrar um novo cliente com senha criptografada
    public static function cadastrarCliente($data)
    {
        return self::create([
            'name' => $data['nomeSignup'],
            'cpf' => $data['cpfSignup'],
            'date_of_birth' => $data['dataNascSignup'],
            'phone' => $data['telefoneSignup'],
            'email' => $data['emailSignup'],
            'password' => Hash::make($data['senhaSignup'])
        ]);
    }

    // Método para atualizar o cliente usando stored procedure
    public static function atualizarCliente($id_cliente, $telefone, $email)
    {
        return DB::statement('CALL atualizar_cliente(?, ?, ?)', [$id_cliente, $telefone, $email]);
    }

    public function enderecoCliente()
    {
        return $this->hasMany(EnderecoCliente::class, 'id_cliente', 'id_cliente');
    }

    public function historicoCliente()
    {
        return $this->hasMany(HistoricoCliente::class, 'id_cliente', 'id_cliente');
    }

    public function pedido()
    {
        return $this->hasMany(Pedido::class, 'id_cliente', 'id_cliente');
    }

    public function produtoFavorito()
    {
        return $this->hasMany(ProdutoFavorito::class, 'id_cliente', 'id_cliente');
    }
}
