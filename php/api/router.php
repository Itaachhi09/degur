<?php
/**
 * REST API Router for HR Management System
 * Centralized routing system for all API endpoints
 * Version: 1.0
 */

// Error Reporting & Headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once '../db_connect.php';

// Include utility functions
require_once 'utils/response.php';
require_once 'utils/validation.php';
require_once 'utils/auth.php';

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/php/api/', '', $uri);

// Remove query parameters
$path = strtok($path, '?');

// Route definitions
$routes = [
    // Authentication routes
    'POST /auth/login' => 'auth/login.php',
    'POST /auth/logout' => 'auth/logout.php',
    'POST /auth/verify-2fa' => 'auth/verify_2fa.php',
    'POST /auth/refresh' => 'auth/refresh.php',
    
    // Employee routes
    'GET /employees' => 'employees/index.php',
    'POST /employees' => 'employees/create.php',
    'GET /employees/{id}' => 'employees/show.php',
    'PUT /employees/{id}' => 'employees/update.php',
    'DELETE /employees/{id}' => 'employees/delete.php',
    
    // User management routes
    'GET /users' => 'users/index.php',
    'POST /users' => 'users/create.php',
    'GET /users/{id}' => 'users/show.php',
    'PUT /users/{id}' => 'users/update.php',
    'DELETE /users/{id}' => 'users/delete.php',
    
    // Department routes
    'GET /departments' => 'departments/index.php',
    'POST /departments' => 'departments/create.php',
    'GET /departments/{id}' => 'departments/show.php',
    'PUT /departments/{id}' => 'departments/update.php',
    'DELETE /departments/{id}' => 'departments/delete.php',
    
    // Payroll routes
    'GET /payroll/runs' => 'payroll/runs.php',
    'POST /payroll/runs' => 'payroll/create_run.php',
    'GET /payroll/salaries' => 'payroll/salaries.php',
    'POST /payroll/salaries' => 'payroll/create_salary.php',
    'GET /payroll/payslips' => 'payroll/payslips.php',
    'GET /payroll/payslips/{id}' => 'payroll/payslip_detail.php',
    
    // Claims routes
    'GET /claims' => 'claims/index.php',
    'POST /claims' => 'claims/create.php',
    'GET /claims/{id}' => 'claims/show.php',
    'PUT /claims/{id}' => 'claims/update.php',
    'DELETE /claims/{id}' => 'claims/delete.php',
    
    // Analytics routes
    'GET /analytics/dashboard' => 'analytics/dashboard.php',
    'GET /analytics/reports' => 'analytics/reports.php',
    'GET /analytics/metrics' => 'analytics/metrics.php',
    
    // Document routes
    'GET /documents' => 'documents/index.php',
    'POST /documents' => 'documents/upload.php',
    'GET /documents/{id}' => 'documents/download.php',
    'DELETE /documents/{id}' => 'documents/delete.php',
    
    // Notification routes
    'GET /notifications' => 'notifications/index.php',
    'PUT /notifications/{id}/read' => 'notifications/mark_read.php',
    'DELETE /notifications/{id}' => 'notifications/delete.php',
];

// Find matching route
$routeFound = false;
foreach ($routes as $route => $file) {
    list($routeMethod, $routePath) = explode(' ', $route, 2);
    
    // Convert route pattern to regex
    $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
    $pattern = '#^' . $pattern . '$#';
    
    if ($method === $routeMethod && preg_match($pattern, $path, $matches)) {
        $routeFound = true;
        
        // Extract path parameters
        $pathParams = array_slice($matches, 1);
        
        // Set path parameters in $_GET for backward compatibility
        if (!empty($pathParams)) {
            $paramNames = [];
            preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
            foreach ($paramNames[1] as $index => $paramName) {
                if (isset($pathParams[$index])) {
                    $_GET[$paramName] = $pathParams[$index];
                }
            }
        }
        
        // Include the route handler
        $filePath = __DIR__ . '/' . $file;
        if (file_exists($filePath)) {
            require $filePath;
        } else {
            sendErrorResponse(404, 'Route handler not found');
        }
        break;
    }
}

// No route found
if (!$routeFound) {
    sendErrorResponse(404, 'Route not found');
}
?>
