<?php
/**
 * HMO & Benefits API
 * Supports CRUD operations for HMO and employee benefits
 * Endpoint: /api/hmo_benefits.php
 */
require_once '../db_connect.php';
require_once '../utils/response.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Fetch all HMO & Benefits records
        $sql = "SELECT * FROM hmo_benefits";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendSuccessResponse(['HMO_Benefits' => $data]);
        break;
    case 'POST':
        // Add new HMO/Benefit
        $input = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO hmo_benefits (employee_id, hmo_provider, benefit_type, benefit_details) VALUES (:employee_id, :hmo_provider, :benefit_type, :benefit_details)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':employee_id' => $input['employee_id'],
            ':hmo_provider' => $input['hmo_provider'],
            ':benefit_type' => $input['benefit_type'],
            ':benefit_details' => $input['benefit_details']
        ]);
        sendSuccessResponse(['message' => 'HMO/Benefit added successfully']);
        break;
    case 'PUT':
        // Update HMO/Benefit
        $input = json_decode(file_get_contents('php://input'), true);
        $sql = "UPDATE hmo_benefits SET hmo_provider = :hmo_provider, benefit_type = :benefit_type, benefit_details = :benefit_details WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $input['id'],
            ':hmo_provider' => $input['hmo_provider'],
            ':benefit_type' => $input['benefit_type'],
            ':benefit_details' => $input['benefit_details']
        ]);
        sendSuccessResponse(['message' => 'HMO/Benefit updated successfully']);
        break;
    case 'DELETE':
        // Delete HMO/Benefit
        $input = json_decode(file_get_contents('php://input'), true);
        $sql = "DELETE FROM hmo_benefits WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $input['id']]);
        sendSuccessResponse(['message' => 'HMO/Benefit deleted successfully']);
        break;
    default:
        sendMethodNotAllowedResponse();
}
?>
