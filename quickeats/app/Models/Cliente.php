<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Importando Authenticatable para autenticação
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Cliente extends Authenticatable
{
    use HasFactory, HasApiTokens;

    // Defina a chave primária, se não for 'id'
    protected $primaryKey = 'id_cliente';

    // Define a tabela associada
    protected $table = 'clientes';

    // Define os campos que podem ser preenchidos em massa
    protected $fillable = [
        'nome',
        'cpf',
        'data_nasc',
        'telefone',
        'email',
        'senha',
        'email_verificado',
        'perfil_ativo'
    ];

    // Desativa os timestamps automáticos
    public $timestamps = false;

    // Ocultar a senha ao recuperar os dados
    protected $hidden = [
        'senha',
    ];

    // Adiciona a função getAuthPassword para autenticação
    public function getAuthPassword()
    {
        return $this->senha;
    }

    // Método para cadastrar um novo cliente com senha criptografada
    public static function cadastrarCliente($data)
    {
        return self::create([
            'nome' => $data['nomeSignup'],
            'cpf' => $data['cpfSignup'],
            'data_nasc' => $data['dataNascSignup'],
            'telefone' => $data['telefoneSignup'],
            'email' => $data['emailSignup'],
            'senha' => Hash::make($data['senhaSignup']),
            'email_verificado' => 0,
            'perfil_ativo' => 1,
        ]);
    }

    // Método para atualizar o cliente usando stored procedure
    public static function atualizarCliente($id_cliente, $telefone, $email)
    {
        return DB::statement('CALL atualizar_cliente(?, ?, ?)', [$id_cliente, $telefone, $email]);
    }
}
