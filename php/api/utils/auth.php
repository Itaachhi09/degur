<?php
/**
 * Authentication Utilities
 * JWT and session management for REST API
 */

require_once 'response.php';

/**
 * Generate JWT token
 */
function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode($payload);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, getenv('JWT_SECRET') ?: 'default_secret', true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

/**
 * Verify JWT token
 */
function verifyJWT($token) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($base64Header, $base64Payload, $base64Signature) = $parts;
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, getenv('JWT_SECRET') ?: 'default_secret', true);
    $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if (!hash_equals($expectedSignature, $base64Signature)) {
        return false;
    }
    
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
    
    // Check token expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * Get current user from JWT token
 */
function getCurrentUser() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    $payload = verifyJWT($token);
    
    return $payload ? $payload : null;
}

/**
 * Require authentication
 */
function requireAuth() {
    $user = getCurrentUser();
    
    if (!$user) {
        sendUnauthorizedResponse('Authentication required');
    }
    
    return $user;
}

/**
 * Require specific role
 */
function requireRole($requiredRole) {
    $user = requireAuth();
    
    if ($user['role'] !== $requiredRole) {
        sendForbiddenResponse('Insufficient permissions');
    }
    
    return $user;
}

/**
 * Require any of the specified roles
 */
function requireAnyRole($roles) {
    $user = requireAuth();
    
    if (!in_array($user['role'], $roles)) {
        sendForbiddenResponse('Insufficient permissions');
    }
    
    return $user;
}

/**
 * Check if user has permission
 */
function hasPermission($permission) {
    $user = getCurrentUser();
    
    if (!$user) {
        return false;
    }
    
    // Define role permissions
    $permissions = [
        'System Admin' => ['*'], // All permissions
        'HR Admin' => [
            'employees.read', 'employees.write', 'employees.delete',
            'users.read', 'users.write', 'users.delete',
            'payroll.read', 'payroll.write',
            'reports.read'
        ],
        'Manager' => [
            'employees.read', 'employees.write',
            'payroll.read',
            'reports.read'
        ],
        'Employee' => [
            'profile.read', 'profile.write',
            'payroll.read'
        ]
    ];
    
    $userPermissions = $permissions[$user['role']] ?? [];
    
    return in_array('*', $userPermissions) || in_array($permission, $userPermissions);
}

/**
 * Require specific permission
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        sendForbiddenResponse('Insufficient permissions');
    }
}
?>
