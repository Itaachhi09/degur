<?php
/**
 * POST /auth/logout
 * User logout endpoint
 */

require_once '../utils/response.php';
require_once '../utils/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendMethodNotAllowedResponse();
}

// For JWT-based auth, logout is handled client-side by removing the token
// For session-based auth, we would destroy the session here

sendSuccessResponse(null, 'Logged out successfully');
?>
