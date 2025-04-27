<?php

namespace App\Models;

use CodeIgniter\Model;

class UserVerification extends Model
{
    protected $DBGroup = 'auth';
    protected $table = 'user_verification';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'verification_token',
        'expires_at',
        'verified'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}