<?php
declare(strict_types=1);
function respond_json($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function respond_error(string $message, int $status = 400, $details = null): void
{
    $payload = ['error' => $message];
    if ($details) $payload['details'] = $details;
    respond_json($payload, $status);
}

function get_json_input()
{
    $raw = file_get_contents('php://input');
    if (!$raw) return null;
    $data = json_decode($raw, true);
    return $data;
}

function sanitize_string($s)
{
    if ($s === null) return null;
    return trim(htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}

/**
 * Extract Bearer token from Authorization header
 */
function get_bearer_token(): ?string
{
    $headers = null;
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }
    $auth = null;
    if (isset($headers['Authorization'])) $auth = $headers['Authorization'];
    if (isset($headers['authorization'])) $auth = $headers['authorization'];
    if (!$auth && isset($_SERVER['HTTP_AUTHORIZATION'])) $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (!$auth) return null;
    if (stripos($auth, 'Bearer ') === 0) return trim(substr($auth, 7));
    return null;
}

function require_auth()
{
    $token = get_bearer_token();
    if (!$token) respond_error('Authorization token required', 401);
    $secret = getenv('JWT_SECRET') ?: 'dev_secret_change_me';
    $payload = JWT::verify($token, $secret);
    if (!$payload) respond_error('Invalid or expired token', 401);
    return $payload;
}
