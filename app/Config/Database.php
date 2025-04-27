<?php

namespace Config;

use CodeIgniter\Database\Config;

class Database extends Config
{
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;
    public string $defaultGroup = 'default';

    public array $default;
    public array $auth;

    public function __construct()
    {
        parent::__construct();

        $this->default = [
            'DSN'          => '',
            'hostname'     => env('database.default.hostname'),
            'username'     => env('database.default.username'),
            'password'     => env('database.default.password'),
            'database'     => env('database.default.database'),
            'DBDriver'     => env('database.default.DBDriver'),
            'DBPrefix'     => '',
            'pConnect'     => false,
            'DBDebug'      => true,
            'charset'      => 'utf8mb4',
            'DBCollat'     => 'utf8mb4_general_ci',
            'swapPre'      => '',
            'encrypt'      => false,
            'compress'     => false,
            'strictOn'     => false,
            'failover'     => [],
            'port'         => 3306,
        ];

        $this->auth = [
            'DSN'          => '',
            'hostname'     => env('database.auth.hostname'),
            'username'     => env('database.auth.username'),
            'password'     => env('database.auth.password'),
            'database'     => env('database.auth.database'),
            'DBDriver'     => env('database.auth.DBDriver'),
            'DBPrefix'     => '',
            'pConnect'     => false,
            'DBDebug'      => true,
            'charset'      => 'utf8mb4',
            'DBCollat'     => 'utf8mb4_general_ci',
            'swapPre'      => '',
            'encrypt'      => false,
            'compress'     => false,
            'strictOn'     => false,
            'failover'     => [],
            'port'         => 3306,
        ];
    }
}
