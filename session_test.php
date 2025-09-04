<?php
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\n";

// Test database connection
try {
    require_once 'php/db_connect.php';
    echo "Database connection: OK\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees");
    $result = $stmt->fetch();
    echo "Employee count: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
