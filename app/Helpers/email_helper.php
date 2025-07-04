<?php

if (!function_exists('send_email')) {
    function send_email($to, $subject, $message)
    {
        $emailService = \Config\Services::email();
        $emailConfig = [
            'protocol' => 'smtp',
            'SMTPHost' => env('email.SMTPHost'),
            'SMTPUser' => env('email.SMTPUser'),
            'SMTPPass' => env('email.SMTPPass'),
            'SMTPPort' => (int) env('email.SMTPPort'),
            'SMTPCrypto' => env('email.SMTPCrypto'),
            'mailType' => 'html',
            'charset' => 'utf-8',
            'wordWrap' => true
        ];

        $emailService->initialize($emailConfig);

        $emailService->setFrom(env('email.fromEmail'), env('email.fromName'));
        $emailService->setTo($to);
        $emailService->setSubject($subject);
        $emailService->setMessage($message);

        if (!$emailService->send(false)) {
            log_message('error', 'Email sending failed: ' . $emailService->printDebugger(['headers']));
            return false;
        }
        return true;
    }
}