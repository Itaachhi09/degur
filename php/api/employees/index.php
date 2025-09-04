<?php
/**
 * GET /employees
 * List all employees with pagination and filtering
 */

require_once '../utils/response.php';
require_once '../utils/validation.php';
require_once '../utils/auth.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendMethodNotAllowedResponse();
}

// Require authentication
$user = requireAuth();

// Check permissions
if (!hasPermission('employees.read')) {
    sendForbiddenResponse();
}

// Get query parameters
$page = (int) ($_GET['page'] ?? 1);
$limit = (int) ($_GET['limit'] ?? 20);
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';
$status = $_GET['status'] ?? '';

// Validate pagination
$paginationErrors = validatePagination($page, $limit);
if (!empty($paginationErrors)) {
    sendValidationErrorResponse($paginationErrors);
}

// Calculate offset
$offset = ($page - 1) * $limit;

try {
    // Build base query
    $sql = "SELECT
                e.EmployeeID,
                e.FirstName,
                e.MiddleName,
                e.LastName,
                e.Suffix,
                e.Email,
                e.PersonalEmail,
                e.PhoneNumber,
                e.DateOfBirth,
                e.Gender,
                e.MaritalStatus,
                e.Nationality,
                e.AddressLine1,
                e.AddressLine2,
                e.City,
                e.StateProvince,
                e.PostalCode,
                e.Country,
                e.EmergencyContactName,
                e.EmergencyContactRelationship,
                e.EmergencyContactPhone,
                e.HireDate,
                e.JobTitle,
                e.DepartmentID,
                d.DepartmentName,
                e.ManagerID,
                CONCAT(m.FirstName, ' ', m.LastName) AS ManagerName,
                e.IsActive,
                e.TerminationDate,
                e.TerminationReason,
                e.EmployeePhotoPath,
                u.UserID
            FROM Employees e
            LEFT JOIN OrganizationalStructure d ON e.DepartmentID = d.DepartmentID
            LEFT JOIN Employees m ON e.ManagerID = m.EmployeeID
            LEFT JOIN Users u ON e.EmployeeID = u.EmployeeID
            WHERE 1=1";

    $params = [];

    // Add search filter
    if (!empty($search)) {
        $sql .= " AND (e.FirstName LIKE :search OR e.LastName LIKE :search OR e.Email LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Add department filter
    if (!empty($department)) {
        $sql .= " AND e.DepartmentID = :department";
        $params[':department'] = $department;
    }

    // Add status filter
    if ($status === 'active') {
        $sql .= " AND e.IsActive = 1";
    } elseif ($status === 'inactive') {
        $sql .= " AND e.IsActive = 0";
    }

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM ($sql) as count_query";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Add pagination
    $sql .= " ORDER BY e.LastName, e.FirstName LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    // Execute query
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response data
    foreach ($employees as &$employee) {
        $employee['full_name'] = trim($employee['FirstName'] . ' ' . $employee['MiddleName'] . ' ' . $employee['LastName']);
        $employee['status'] = $employee['IsActive'] ? 'Active' : 'Inactive';
        
        // Format dates
        if ($employee['HireDate']) {
            $employee['hire_date_formatted'] = date('M d, Y', strtotime($employee['HireDate']));
        }
        if ($employee['DateOfBirth']) {
            $employee['date_of_birth_formatted'] = date('M d, Y', strtotime($employee['DateOfBirth']));
        }
        if ($employee['TerminationDate']) {
            $employee['termination_date_formatted'] = date('M d, Y', strtotime($employee['TerminationDate']));
        }
    }

    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;

    sendSuccessResponse([
        'employees' => $employees,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage
        ]
    ]);

} catch (PDOException $e) {
    error_log("Get Employees API Error: " . $e->getMessage());
    sendInternalErrorResponse('Failed to retrieve employees');
} catch (Exception $e) {
    error_log("Get Employees API Error: " . $e->getMessage());
    sendInternalErrorResponse('An unexpected error occurred');
}
?>
