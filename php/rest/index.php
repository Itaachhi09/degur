<?php
// REST front controller
declare(strict_types=1);
// Allow CORS for local development (restrict in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/JWT.php';
require_once __DIR__ . '/EmployeesController.php';
require_once __DIR__ . '/ClaimsController.php';
require_once __DIR__ . '/PayrollController.php';
require_once __DIR__ . '/DocumentsController.php';
require_once __DIR__ . '/HmoBenefitsController.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// basic routing: accept ?r=controller/action or path /rest/controller/action
$route = '/';
if (isset($_GET['r'])) {
    $route = trim($_GET['r'], '/');
} else {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $base = dirname($scriptName);
    $route = trim(str_replace($base, '', $uri), '/');
    // remove query string
    $route = explode('?', $route)[0];
}

$parts = array_values(array_filter(explode('/', $route)));
$controller = $parts[0] ?? 'status';
$action = $parts[1] ?? null;

try {
    switch ($controller) {
        case 'auth':
            // auth/login, auth/register
            if ($action === 'login' && $method === 'POST') {
                AuthController::login();
            } elseif ($action === 'register' && $method === 'POST') {
                AuthController::register();
            } else {
                respond_error('Unknown auth action', 404);
            }
            break;
        case 'status':
            respond_json(['status' => 'ok', 'time' => date('c')]);
            break;
        case 'employees':
            // employees, employees/{id}
            if ($action === null) {
                if ($method === 'GET') {
                    EmployeesController::list();
                } elseif ($method === 'POST') {
                    // require auth
                    require_auth();
                    EmployeesController::create();
                } else {
                    respond_error('Method not allowed', 405);
                }
            } else {
                $id = $action;
                if ($method === 'GET') {
                    EmployeesController::get($id);
                } elseif ($method === 'PUT') {
                    require_auth();
                    EmployeesController::update($id);
                } elseif ($method === 'DELETE') {
                    require_auth();
                    EmployeesController::delete($id);
                } else {
                    respond_error('Method not allowed', 405);
                }
            }
            break;
        case 'claims':
            if ($action === null) {
                if ($method === 'GET') ClaimsController::list();
                elseif ($method === 'POST') { require_auth(); ClaimsController::create(); }
                else respond_error('Method not allowed', 405);
            } else {
                $id = $action;
                if ($method === 'GET') ClaimsController::get($id);
                elseif ($method === 'PUT') { require_auth(); ClaimsController::update($id); }
                elseif ($method === 'DELETE') { require_auth(); ClaimsController::delete($id); }
                else respond_error('Method not allowed', 405);
            }
            break;
        case 'payroll':
            if ($action === null) {
                if ($method === 'GET') PayrollController::listRuns();
                elseif ($method === 'POST') { require_auth(); PayrollController::createRun(); }
                else respond_error('Method not allowed', 405);
            } else {
                $id = $action;
                if ($method === 'GET') PayrollController::getRun($id);
                elseif ($method === 'PUT') { require_auth(); PayrollController::updateRun($id); }
                elseif ($method === 'DELETE') { require_auth(); PayrollController::deleteRun($id); }
                else respond_error('Method not allowed', 405);
            }
            break;
        case 'documents':
            if ($action === null) {
                if ($method === 'GET') DocumentsController::list();
                elseif ($method === 'POST') { require_auth(); DocumentsController::create(); }
                else respond_error('Method not allowed', 405);
            } else {
                $id = $action;
                if ($method === 'GET') DocumentsController::get($id);
                elseif ($method === 'DELETE') { require_auth(); DocumentsController::delete($id); }
                else respond_error('Method not allowed', 405);
            }
            break;
        case 'hmo':
            if ($action === null) {
                if ($method === 'GET') HmoBenefitsController::list();
                elseif ($method === 'POST') { require_auth(); HmoBenefitsController::create(); }
                else respond_error('Method not allowed', 405);
            } else {
                $id = $action;
                if ($method === 'GET') HmoBenefitsController::get($id);
                elseif ($method === 'PUT') { require_auth(); HmoBenefitsController::update($id); }
                elseif ($method === 'DELETE') { require_auth(); HmoBenefitsController::delete($id); }
                else respond_error('Method not allowed', 405);
            }
            break;
        default:
            respond_error('Unknown endpoint', 404);
    }
} catch (Throwable $e) {
    error_log('REST ERROR: ' . $e->getMessage());
    respond_error('Server error', 500);
}

// EOF
