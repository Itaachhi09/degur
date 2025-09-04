<?php
/**
 * API Validation Utilities
 * Input validation functions for REST API
 */

/**
 * Validate required fields
 */
function validateRequired($data, $requiredFields) {
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[$field] = ucfirst($field) . ' is required';
        }
    }
    
    return $errors;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return $errors;
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return preg_match('/^\+?[1-9]\d{1,14}$/', $phone);
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate numeric range
 */
function validateNumericRange($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $value = (float) $value;
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return true;
}

/**
 * Sanitize string input
 */
function sanitizeString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize array of strings
 */
function sanitizeArray($array) {
    return array_map('sanitizeString', $array);
}

/**
 * Validate JSON input
 */
function validateJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse(400, 'Invalid JSON input');
    }
    
    return $data;
}

/**
 * Validate pagination parameters
 */
function validatePagination($page, $limit) {
    $errors = [];
    
    if (!is_numeric($page) || $page < 1) {
        $errors['page'] = 'Page must be a positive integer';
    }
    
    if (!is_numeric($limit) || $limit < 1 || $limit > 100) {
        $errors['limit'] = 'Limit must be between 1 and 100';
    }
    
    return $errors;
}
?>
