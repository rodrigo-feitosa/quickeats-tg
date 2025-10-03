<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormaPagamento extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_formapag';
    protected $table = 'formas_pagamentos';

    protected $fillable = ['descricao'];

    public function pedido()
    {
        return $this->hasMany(Pedido::class, 'forma_pagamento', 'id_formapag');
    }
}
