<?php
declare(strict_types=1);
require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/JWT.php';

class AuthController
{
    private static function getSecret(): string
    {
        return getenv('JWT_SECRET') ?: 'dev_secret_change_me';
    }

    public static function register(): void
    {
        $input = get_json_input();
        if (!$input) respond_error('Invalid JSON', 400);
        $username = sanitize_string($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if (!$username || !$password) {
            respond_error('username and password are required', 400);
        }

        $pdo = DB::getConnection();
        // Check existing
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) respond_error('User already exists', 409);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (:username, :hash, NOW())');
        $ins->execute([':username' => $username, ':hash' => $hash]);
        $userId = (int)$pdo->lastInsertId();

        $token = JWT::sign(['user_id' => $userId, 'username' => $username], self::getSecret(), 60*60*24);
        respond_json(['token' => $token, 'user' => ['id' => $userId, 'username' => $username]], 201);
    }

    public static function login(): void
    {
        $input = get_json_input();
        if (!$input) respond_error('Invalid JSON', 400);
        $username = sanitize_string($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if (!$username || !$password) respond_error('username and password are required', 400);

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        if (!$row) respond_error('Invalid credentials', 401);
        if (!password_verify($password, $row['password_hash'])) respond_error('Invalid credentials', 401);

        $token = JWT::sign(['user_id' => (int)$row['id'], 'username' => $username], self::getSecret(), 60*60*24);
        respond_json(['token' => $token, 'user' => ['id' => (int)$row['id'], 'username' => $username]]);
    }
}
