<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfirmacaoEmail extends Model
{
    use HasFactory;

    protected $table = 'confirmacoes_emails';

    protected $fillable = [
        'id_usuario',
        'tipo_usuario',
        'email',
        'criado_em',
        'token'
    ];

    // Desativa os timestamps automáticos
    public $timestamps = false;
}
