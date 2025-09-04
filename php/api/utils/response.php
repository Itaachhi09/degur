<?php
/**
 * API Response Utilities
 * Standardized response functions for REST API
 */

/**
 * Send a successful JSON response
 */
function sendSuccessResponse($data = null, $message = 'Success', $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => true,
        'message' => $message,
        'timestamp' => date('c'),
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Send an error JSON response
 */
function sendErrorResponse($statusCode = 400, $message = 'Error', $details = null) {
    http_response_code($statusCode);
    $response = [
        'success' => false,
        'error' => [
            'message' => $message,
            'code' => $statusCode,
            'timestamp' => date('c'),
        ]
    ];
    
    if ($details !== null) {
        $response['error']['details'] = $details;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Send validation error response
 */
function sendValidationErrorResponse($errors) {
    sendErrorResponse(422, 'Validation failed', $errors);
}

/**
 * Send unauthorized response
 */
function sendUnauthorizedResponse($message = 'Unauthorized') {
    sendErrorResponse(401, $message);
}

/**
 * Send forbidden response
 */
function sendForbiddenResponse($message = 'Forbidden') {
    sendErrorResponse(403, $message);
}

/**
 * Send not found response
 */
function sendNotFoundResponse($message = 'Resource not found') {
    sendErrorResponse(404, $message);
}

/**
 * Send method not allowed response
 */
function sendMethodNotAllowedResponse($message = 'Method not allowed') {
    sendErrorResponse(405, $message);
}

/**
 * Send internal server error response
 */
function sendInternalErrorResponse($message = 'Internal server error') {
    sendErrorResponse(500, $message);
}
?>
