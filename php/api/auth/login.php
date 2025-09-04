<?php
/**
 * POST /auth/login
 * User authentication endpoint
 */

require_once '../utils/response.php';
require_once '../utils/validation.php';
require_once '../utils/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendMethodNotAllowedResponse();
}

// Get and validate input
$input = validateJsonInput();
$errors = validateRequired($input, ['username', 'password']);

if (!empty($errors)) {
    sendValidationErrorResponse($errors);
}

$username = sanitizeString($input['username']);
$password = $input['password'];

try {
    // Fetch user details
    $sql = "SELECT
                u.UserID, u.EmployeeID, u.Username, u.PasswordHash, u.RoleID, u.IsActive,
                u.IsTwoFactorEnabled,
                r.RoleName,
                e.FirstName, e.LastName, e.Email AS EmployeeEmail
            FROM Users u
            JOIN Roles r ON u.RoleID = r.RoleID
            JOIN Employees e ON u.EmployeeID = e.EmployeeID
            WHERE u.Username = :username";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['IsActive']) {
        sendErrorResponse(401, 'Invalid credentials');
    }

    // Verify password
    if (!password_verify($password, $user['PasswordHash'])) {
        sendErrorResponse(401, 'Invalid credentials');
    }

    // Handle 2FA if enabled
    if ($user['IsTwoFactorEnabled']) {
        if (empty($user['EmployeeEmail'])) {
            sendErrorResponse(500, 'Two-factor authentication cannot proceed. Please contact support.');
        }

        // Generate 2FA code
        $two_factor_code = sprintf("%06d", random_int(100000, 999999));
        $expiry_time = new DateTime('+10 minutes');
        $expiry_timestamp = $expiry_time->format('Y-m-d H:i:s');

        // Store code and expiry
        $sql_update_2fa = "UPDATE Users
                           SET TwoFactorEmailCode = :code,
                               TwoFactorCodeExpiry = :expiry
                           WHERE UserID = :user_id";
        $stmt_update_2fa = $pdo->prepare($sql_update_2fa);
        $stmt_update_2fa->bindParam(':code', $two_factor_code, PDO::PARAM_STR);
        $stmt_update_2fa->bindParam(':expiry', $expiry_timestamp, PDO::PARAM_STR);
        $stmt_update_2fa->bindParam(':user_id', $user['UserID'], PDO::PARAM_INT);
        $stmt_update_2fa->execute();

        // Send 2FA code via email (implement your email service)
        // For now, we'll just return the code in development
        if (getenv('APP_ENV') === 'development') {
            sendSuccessResponse([
                'two_factor_required' => true,
                'message' => 'Two-factor authentication required',
                'code' => $two_factor_code, // Only in development
                'user_id_temp' => $user['UserID']
            ]);
        } else {
            sendSuccessResponse([
                'two_factor_required' => true,
                'message' => 'Two-factor authentication code sent to your email',
                'user_id_temp' => $user['UserID']
            ]);
        }
    }

    // Generate JWT token
    $payload = [
        'user_id' => $user['UserID'],
        'employee_id' => $user['EmployeeID'],
        'username' => $user['Username'],
        'role' => $user['RoleName'],
        'full_name' => $user['FirstName'] . ' ' . $user['LastName'],
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ];

    $token = generateJWT($payload);

    sendSuccessResponse([
        'token' => $token,
        'user' => [
            'user_id' => $user['UserID'],
            'employee_id' => $user['EmployeeID'],
            'username' => $user['Username'],
            'full_name' => $user['FirstName'] . ' ' . $user['LastName'],
            'role' => $user['RoleName']
        ],
        'expires_in' => 24 * 60 * 60
    ]);

} catch (PDOException $e) {
    error_log("Login API Error: " . $e->getMessage());
    sendInternalErrorResponse('An error occurred during login');
} catch (Exception $e) {
    error_log("Login API Error: " . $e->getMessage());
    sendInternalErrorResponse('An internal error occurred');
}
?>
