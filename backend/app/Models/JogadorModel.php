<?php

namespace App\Models;

use CodeIgniter\Model;

class JogadorModel extends Model
{
    protected $table = 'jogadores';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'nome',
        'idade',
        'posicao',
        'numero_camisa',
        'imagem'
    ];
}
