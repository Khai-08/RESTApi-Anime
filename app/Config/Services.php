<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use CodeIgniter\Email\Email;

class Services extends BaseService
{
    public static function email($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('email');
        }

        $email = new Email();
        $email->initialize([
            'protocol' => 'smtp',
            'SMTPHost' => getenv('SMTP_HOST'),
            'SMTPUser' => getenv('SMTP_USERNAME'),
            'SMTPPass' => getenv('SMTP_PASSWORD'),
            'SMTPPort' => (int) getenv('SMTP_PORT'),
            'SMTPCrypto' => getenv('SMTP_SECURE'),
            'mailType' => 'html',
            'charset' => 'utf-8'
        ]);

        return $email;
    }
}
