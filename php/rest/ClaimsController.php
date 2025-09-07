<?php
declare(strict_types=1);
require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/helpers.php';

class ClaimsController
{
    public static function list()
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT ClaimID, EmployeeID, ClaimType, Amount, Status, CreatedAt FROM Claims ORDER BY CreatedAt DESC LIMIT 200');
        respond_json($stmt->fetchAll());
    }

    public static function get($id)
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM Claims WHERE ClaimID = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) respond_error('Claim not found', 404);
        respond_json($row);
    }

    public static function create()
    {
        $payload = get_json_input();
        if (!$payload) respond_error('Invalid JSON', 400);
        $employee = (int)($payload['EmployeeID'] ?? 0);
        $type = sanitize_string($payload['ClaimType'] ?? '');
        $amount = floatval($payload['Amount'] ?? 0);
        if (!$employee || !$type || $amount <= 0) respond_error('EmployeeID, ClaimType and Amount are required', 400);
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO Claims (EmployeeID, ClaimType, Amount, Status, CreatedAt) VALUES (:emp, :type, :amt, :status, NOW())');
        $stmt->execute([':emp' => $employee, ':type' => $type, ':amt' => $amount, ':status' => 'Submitted']);
        respond_json(['ClaimID' => (int)$pdo->lastInsertId()], 201);
    }

    public static function update($id)
    {
        $payload = get_json_input();
        if (!$payload) respond_error('Invalid JSON', 400);
        $fields = [];
        $params = [':id' => $id];
        if (isset($payload['Status'])) { $fields[] = 'Status = :status'; $params[':status'] = sanitize_string($payload['Status']); }
        if (isset($payload['Amount'])) { $fields[] = 'Amount = :amt'; $params[':amt'] = floatval($payload['Amount']); }
        if (empty($fields)) respond_error('No fields to update', 400);
        $sql = 'UPDATE Claims SET ' . implode(', ', $fields) . ' WHERE ClaimID = :id';
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond_json(['updated' => true]);
    }

    public static function delete($id)
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM Claims WHERE ClaimID = :id');
        $stmt->execute([':id' => $id]);
        respond_json(['deleted' => true]);
    }
}
