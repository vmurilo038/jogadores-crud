<?php

namespace App\Models;

use CodeIgniter\Model;

class TokenRevogadoModel extends Model
{
    protected $table = 'tokens_revogados';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'jti',
        'expira_em'
    ];
}