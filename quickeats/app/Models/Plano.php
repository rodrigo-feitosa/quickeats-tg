<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plano extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_plano';
    protected $table = 'planos';

    protected $fillable = [
        'nome',
        'valor',
        'beneficios'
    ];

    public function planosEstab()
    {
        return $this->hasMany(PlanoEstab::class, 'id_plano', 'id_plano');
    }
}
