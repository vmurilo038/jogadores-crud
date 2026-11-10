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

    public function tokenFoiRevogado(string $jti): bool
    {
        return $this->where('jti', $jti)->countAllResults() > 0;
    }

    public function revogarToken(string $jti, int $expiracao): bool
    {
        return $this->insert([
            'jti' => $jti,
            'expira_em' => date('Y-m-d H:i:s', $expiracao)
        ]) !== false;
    }
}