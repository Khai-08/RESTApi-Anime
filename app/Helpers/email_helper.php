<?php

if (!function_exists('send_email')) {
    function send_email($to, $subject, $message)
    {
        $emailService = \Config\Services::email();

        $emailService->setFrom(getenv('EMAIL_FROM_ADDRESS'), getenv('EMAIL_FROM_NAME'));
        $emailService->setTo($to);
        $emailService->setSubject($subject);
        $emailService->setMessage($message);
        $emailService->SMTPPort = (int) getenv('SMTP_PORT');

        if (!$emailService->send()) {
            log_message('error', 'Email sending failed: ' . print_r($emailService->printDebugger(['headers']), true));
            return false;
        }
        return true;
    }
}