<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserVerification;
use App\Models\AuthUser;
use \Firebase\JWT\JWT;
use DateTimeZone;
use DateTime;

class AuthenticationController extends ResourceController
{
    public function __construct()
    {
        helper('email');
    }

    public function logout()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (!$authHeader) {
            return $this->failUnauthorized('No token provided.');
        }

        $token = str_replace('Bearer ', '', $authHeader);
        return $this->respond(['message' => 'Successfully logged out.'], 200);
    }

    public function login()
    {
        $user = new AuthUser();
        $verificationModel = new UserVerification();
        $identifier = $this->request->getVar('email_username');

        if (!$auth_user = $user->where('username', $identifier)->orWhere('email', $identifier)->first()) {
            return $this->failUnauthorized('No account found with the provided username / email');
        }

        $verificationData = $verificationModel->where('user_id', $auth_user['id'])->first();
        if (!$verificationData || $verificationData['verified'] != 1) {
            return $this->failForbidden('Please verify your email address before logging in.');
        }

        if (!password_verify($this->request->getVar('password'), $auth_user['password'])) {
            return $this->failUnauthorized('Invalid credentials');
        }

        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $auth_user['username'],
            'email' => $auth_user['email']
        ];

        $jwt = JWT::encode($payload, getenv('JWT_SECRET'), 'HS256', null, ['typ' => 'JWT', 'alg' => 'HS256']);
        return $this->respond(["token" => $jwt], 200);
    }

    public function register()
    {
        $verificationModel = new UserVerification();
        $userModel = new AuthUser();
        $db = \Config\Database::connect();

        $username = $this->request->getVar('username');
        $email = $this->request->getVar('email');
        $password = password_hash($this->request->getVar('password'), PASSWORD_BCRYPT);

        if ($userModel->where('username', $username)->orWhere('email', $email)->first()) {
            return $this->failResourceExists('Username or email already taken.');
        }

        $userId = $userModel->insert([
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);

        $verificationToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $verificationModel->insert([
            'user_id' => $userId,
            'verification_token' => $verificationToken,
            'expires_at' => $expiresAt,
            'verified' => 0
        ]);

        $tableName = "anime_user_{$userId}";
        $createTableSQL = "
            CREATE TABLE `$tableName` (
                `id` INT(255) NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `type` ENUM('Movie','Series') NOT NULL DEFAULT 'Series',
                `image` VARCHAR(255) DEFAULT NULL,
                `seasons` INT(255) NOT NULL,
                `episodes` INT(255) NOT NULL,
                `watched` INT(255) NOT NULL,
                `status` ENUM('Planned to Watch','Not Started','Completed','On Going','Watching','Dropped','On Hold','TBD') NOT NULL DEFAULT 'TBD',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $db->query($createTableSQL);

        $verificationLink = getenv('app.baseURL') . "api/auth/verify?token=$verificationToken";

        if (!send_email($email, 'Account Verification', "Click the link to verify: $verificationLink")) {
            return $this->failServerError('Registration successful, but failed to send verification email.');
        }

        return $this->respondCreated(['message' => 'Registration successful. Check your email for verification.']);
    }

    public function verify()
    {
        $verificationModel = new UserVerification();
        $token = urldecode($this->request->getGet('token'));

        if (empty($token)) {
            return $this->failValidationErrors('Token parameter missing');
        }

        $verificationData = $verificationModel->select('*')->where('verification_token', $token)->where('verified', 0)->first();

        if (!$verificationData) {
            return $this->failNotFound('Invalid or expired verification token.');
        }

        if ($verificationData['verified'] == 1) {
            return $this->failResourceExists('Account already verified.');
        }

        $serverTime = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
        $expiryTime = new DateTime($verificationData['expires_at']);

        if ($expiryTime < $serverTime) {
            return $this->failResourceGone('Token expired');
        }

        $verificationModel->where('user_id', $verificationData['user_id'])->set(['verified' => 1, 'verification_token' => null, 'expires_at' => null])->update();
        return $this->respond(['message' => 'Account successfully verified.'], 200);
    }

    public function forgotPassword()
    {
        $authUserModel = new AuthUser();
        $verificationModel = new UserVerification();

        $email = $this->request->getVar('email');
        $user = $authUserModel->where('email', $email)->first();

        if (!$user) {
            return $this->failNotFound('If an account exists with this email, a reset link has been sent.');
        }

        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        if (!$verificationModel->where('user_id', $user['id'])->first()) {
            $verificationModel->insert([
                'user_id' => $user['id'],
                'verification_token' => $resetToken,
                'expires_at' => $expiresAt,
                'verified' => 0
            ]);
        } else {
            $verificationModel->where('user_id', $user['id'])->set([
                'verification_token' => $resetToken,
                'expires_at' => $expiresAt,
                'verified' => 0
            ])->update();
        }

        $resetLink = base_url("api/auth/resetPassword?token=" . urlencode($resetToken));

        try {
            if (!send_email($email, 'Password Reset Request', "Click the link to reset your password: $resetLink")) {
                throw new \RuntimeException('Failed to send email');
            }
            
            return $this->respond(['message' => 'Password reset email sent. Check your inbox.'], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to send password reset email.');
        }
    }

    public function resetPassword()
    {
        $authUserModel = new AuthUser();
        $verificationModel = new UserVerification();

        $token = urldecode($this->request->getGet('token'));
        $newPassword = $this->request->getVar('password');

        if (empty($token)) {
            return $this->failValidationErrors('Token parameter missing');
        }

        if (empty($newPassword)) {
            return $this->failValidationErrors('New password is required');
        }

        $verificationData = $verificationModel->where('verification_token', $token)->where('verified', 0)->first();

        if (!$verificationData) {
            return $this->failNotFound('Invalid or expired reset token.');
        }

        $serverTime = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
        $expiryTime = new DateTime($verificationData['expires_at']);

        if ($expiryTime < $serverTime) {
            return $this->failResourceGone('Reset token has expired.');
        }

        $verificationModel->db->transStart();

        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $authUserModel->update($verificationData['user_id'], [
                'password' => $hashedPassword
            ]);

            $verificationModel->where('user_id', $verificationData['user_id'])
                            ->set([
                                'verified' => 1,
                                'verification_token' => null,
                                'expires_at' => null
                            ])->update();

            $verificationModel->db->transComplete();

            return $this->respond(['message' => 'Password successfully reset. You can now login with your new password.'], 200);
        } catch (\Exception $e) {
            $verificationModel->db->transRollback();
            return $this->failServerError('Failed to reset password. Please try again.');
        }
    }

    public function resendEmail()
    {
        $authUserModel = new AuthUser();
        $verificationModel = new UserVerification();

        $email = $this->request->getVar('email');
        $user = $authUserModel->where('email', $email)->first();

        if (!$user) {
            return $this->failNotFound('If an account exists with this email, a verification link has been sent.');
        }

        $verificationData = $verificationModel->where('user_id', $user['id'])->first();
        if ($verificationData && $verificationData['verified'] == 1) {
            return $this->failResourceExists('Account is already verified.');
        }

        $verificationToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        if (!$verificationData) {
            $verificationModel->insert([
                'user_id' => $user['id'],
                'verification_token' => $verificationToken,
                'expires_at' => $expiresAt,
                'verified' => 0
            ]);
        } else {
            $verificationModel->where('user_id', $user['id'])->set([
                'verification_token' => $verificationToken,
                'expires_at' => $expiresAt
            ])->update();
        }

        $verificationLink = getenv('app.baseURL') . "api/auth/verify?token=$verificationToken";

        try {
            if (!send_email($email, 'Account Verification', "Click the link to verify your account: $verificationLink")) {
                throw new \RuntimeException('Failed to send email');
            }
            
            return $this->respond(['message' => 'Verification email resent. Check your inbox.'], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Failed to send verification email.');
        }
    }
}