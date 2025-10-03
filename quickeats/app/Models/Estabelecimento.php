<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Importando Authenticatable para autenticação
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Estabelecimento extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $primaryKey = 'id_estab';
    protected $table = 'estabelecimentos';

    protected $fillable = [
        'nome_fantasia',
        'cnpj',
        'telefone',
        'logradouro',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'inicio_expediente',
        'termino_expediente',
        'email',
        'senha',
        'email_verificado',
        'perfil_ativo',
        'imagem_perfil'
    ];

    public $timestamps = false;

    protected $hidden = [
        'senha',
    ];

    public function getAuthPassword()
    {
        return $this->senha;
    }

    public static function cadastrarEstabelecimento($data)
    {
        return self::create([
            'nome_fantasia' => $data['nomeFantasiaSignup'],
            'cnpj' => $data['cnpjSignup'],
            'telefone' => $data['telefoneSignup'],
            'logradouro' => $data['logradouroSignup'],
            'numero' => $data['numeroSignup'],
            'bairro' => $data['bairroSignup'],
            'cidade' => $data['cidadeSignup'],
            'estado' => $data['estadoSignup'],
            'cep' => $data['cepSignup'],
            'email' => $data['emailSignup'],
            'senha' => Hash::make($data['senhaSignup']),
            'email_verificado' => 0,
            'perfil_ativo' => 1
        ]);
    }

    // Método para atualizar o cliente usando stored procedure
    public static function atualizarEstabelecimento($id_res, $telefone, $email)
    {
        return DB::statement('CALL atualizar_estabelecimento(?, ?, ?)', [$id_res, $telefone, $email]);
    }

    public function abertoAgora()
    {
        $horaAtual = now()->format('H:i:s');
        $diaSemana = now()->dayOfWeekIso;

        $horario = $this->gradesHorario()
            ->where('dia_semana', $diaSemana)
            ->first();

        if (!$horario || !$horario->inicio_expediente || !$horario->termino_expediente) {
            return false;
        }

        return $horaAtual >= $horario->inicio_expediente && $horaAtual <= $horario->termino_expediente;
    }

    public function gradesHorario()
    {
        return $this->hasMany(GradeHorario::class, 'id_estab', 'id_estab');
    }

    public function historicoEstab()
    {
        return $this->hasMany(HistoricoEstab::class, 'id_estab', 'id_estab');
    }

    public function planosEstab()
    {
        return $this->hasMany(PlanoEstab::class, 'id_estab', 'id_estab');
    }

    public function produto()
    {
        return $this->hasMany(Produto::class, 'id_estab', 'id_estab');
    }
}
