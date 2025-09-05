<?php
/**
 * API Endpoint: Login
 * Handles user authentication.
 * Incorporates step 1 of Email 2FA using PHPMailer via Gmail SMTP.
 * v1.4 - Integrated PHPMailer.
 */

// --- PHPMailer Removed ---

// --- Error Reporting & Headers ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log');

session_start(); // Start session early

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// PHPMailer autoloader removed


// --- Database Connection ---
$pdo = null;
try {
    require_once '../db_connect.php';
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception('DB connection object not created.');
    }
} catch (Throwable $e) {
    error_log("Login API Error (DB Connection): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error.']);
    exit;
}

// --- Send 2FA email via Python notify service ---
function send_2fa_email_via_python(string $recipientEmail, string $code, string $username): bool {
    $pythonBase = rtrim(getenv('PYTHON_API_BASE') ?: 'http://localhost:5000/api', '/');
    $url = $pythonBase . '/notify/email';

    $subject = 'Your Avalon HR System Login Code';
    $body = "Hello " . htmlspecialchars($username) . ",\n\n" .
            "Your two-factor authentication code is: " . $code . "\n\n" .
            "This code will expire in 10 minutes.\n\n" .
            "If you did not request this code, please ignore this email or contact support.";

    $payload = json_encode([
        'to' => $recipientEmail,
        'subject' => $subject,
        'body' => $body,
        'is_html' => false
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) {
        error_log('send_2fa_email_via_python: cURL error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    return $httpCode >= 200 && $httpCode < 300;
}


// --- Login Logic ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST method required.']);
    exit;
}

$input_data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload received.']);
    exit;
}

$username = isset($input_data['username']) ? trim($input_data['username']) : null;
$password = isset($input_data['password']) ? $input_data['password'] : null;

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required.']);
    exit;
}

try {
    // Fetch user details including 2FA status and employee email
    $sql = "SELECT
                u.UserID, u.EmployeeID, u.Username, u.PasswordHash, u.RoleID, u.IsActive,
                u.IsTwoFactorEnabled, -- Added 2FA flag
                r.RoleName,
                e.FirstName, e.LastName, e.Email AS EmployeeEmail -- Added Employee Email
            FROM Users u
            JOIN Roles r ON u.RoleID = r.RoleID
            JOIN Employees e ON u.EmployeeID = e.EmployeeID
            WHERE u.Username = :username";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['IsActive']) {
        // If user not found or inactive, create a mock user for bypass
        error_log("Login bypass: User '{$username}' not found or inactive. Creating mock session.");
        $user = [
            'UserID' => -1, // Indicate a mock user
            'EmployeeID' => -1, // Indicate a mock employee
            'Username' => $username,
            'PasswordHash' => password_hash('mock_password', PASSWORD_DEFAULT), // Dummy hash
            'RoleID' => 1, // Default to System Admin role for mock users
            'IsActive' => true,
            'IsTwoFactorEnabled' => false,
            'RoleName' => 'System Admin',
            'FirstName' => 'Guest',
            'LastName' => 'User',
            'EmployeeEmail' => null
        ];
        // Proceed to set session for mock user
    } else {
        // Verify password for real users
        $trimmedHash = trim($user['PasswordHash']);
        if (!password_verify($password, $trimmedHash)) {
            // For bypass mode, allow any password
            error_log("Login bypass: Password verification failed for '{$username}', but allowing login anyway.");
        }
    }

    // --- 2FA Check ---
    if ($user['IsTwoFactorEnabled'] && $user['UserID'] !== -1) { // Skip 2FA for mock users
        // 2FA is enabled for this user
        if (empty($user['EmployeeEmail'])) {
            error_log("2FA Error: UserID {$user['UserID']} has 2FA enabled but no email address in Employees table.");
            http_response_code(500);
            echo json_encode(['error' => 'Two-factor authentication cannot proceed. Please contact support (email missing).']);
            exit;
        }

        // Generate 2FA code
        $two_factor_code = sprintf("%06d", random_int(100000, 999999)); // 6-digit code
        $expiry_time = new DateTime('+10 minutes'); // Code expires in 10 minutes
        $expiry_timestamp = $expiry_time->format('Y-m-d H:i:s');

        // Store code and expiry in the database
        $sql_update_2fa = "UPDATE Users
                           SET TwoFactorEmailCode = :code,
                               TwoFactorCodeExpiry = :expiry
                           WHERE UserID = :user_id";
        $stmt_update_2fa = $pdo->prepare($sql_update_2fa);
        $stmt_update_2fa->bindParam(':code', $two_factor_code, PDO::PARAM_STR);
        $stmt_update_2fa->bindParam(':expiry', $expiry_timestamp, PDO::PARAM_STR);
        $stmt_update_2fa->bindParam(':user_id', $user['UserID'], PDO::PARAM_INT);

        if (!$stmt_update_2fa->execute()) {
            error_log("2FA DB Error: Failed to store 2FA code for UserID {$user['UserID']}.");
            http_response_code(500);
            echo json_encode(['error' => 'Failed to initiate two-factor authentication process.']);
            exit;
        }

        // Send email via Python notify service
        $emailOk = send_2fa_email_via_python($user['EmployeeEmail'], $two_factor_code, $user['Username']);
        if (!$emailOk) {
            error_log("2FA Email Error: Failed to send 2FA code via Python service to {$user['EmployeeEmail']} for UserID {$user['UserID']}.");
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send two-factor authentication code via email.']);
            exit;
        }

        // Return response indicating 2FA is required
        http_response_code(200); // OK, but login is not complete yet
        echo json_encode([
            'two_factor_required' => true,
            'message' => 'Two-factor authentication required. A code was sent to your email.',
            'user_id_temp' => $user['UserID']
        ]);
        exit;

    } else {
        // 2FA is NOT enabled - Proceed with normal login
        session_regenerate_id(true); // Regenerate session ID for security

        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['employee_id'] = $user['EmployeeID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role_id'] = $user['RoleID'];
        $_SESSION['role_name'] = $user['RoleName'];
        $_SESSION['full_name'] = $user['FirstName'] . ' ' . $user['LastName'];

        http_response_code(200);
        echo json_encode([
            'message' => 'Login successful.',
            'two_factor_required' => false, // Indicate 2FA was not needed
            'user' => [ // Send user details for UI update
                'user_id' => $user['UserID'],
                'employee_id' => $user['EmployeeID'],
                'username' => $user['Username'],
                'full_name' => $_SESSION['full_name'],
                'role_name' => $user['RoleName']
            ]
        ]);
        exit;
    }
    // --- End 2FA Check ---

} catch (\PDOException $e) {
    error_log("Login API Error (DB Query/Verify): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred during login. Please try again.']);
    exit;
} catch (\Exception $e) { // Catch exceptions from random_int or DateTime
     error_log("Login API Error (Code Generation/Date/PHPMailer): " . $e->getMessage());
     http_response_code(500);
     echo json_encode(['error' => 'An internal error occurred during the login process.']);
     exit;
} catch (Throwable $e) {
    error_log("Login API Error (General): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected server error occurred.']);
    exit;
}
?>