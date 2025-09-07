<?php
declare(strict_types=1);
require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/helpers.php';

class EmployeesController
{
    public static function list()
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT EmployeeID, FirstName, LastName, EmployeeEmail, DepartmentName FROM Employees LIMIT 100');
        $rows = $stmt->fetchAll();
        respond_json($rows);
    }

    public static function get($id)
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM Employees WHERE EmployeeID = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) respond_error('Employee not found', 404);
        respond_json($row);
    }

    public static function create()
    {
        $payload = get_json_input();
        if (!$payload) respond_error('Invalid JSON', 400);
        $first = sanitize_string($payload['FirstName'] ?? '');
        $last = sanitize_string($payload['LastName'] ?? '');
        $email = sanitize_string($payload['EmployeeEmail'] ?? '');
        if (!$first || !$last || !$email) respond_error('FirstName, LastName and EmployeeEmail are required', 400);
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO Employees (FirstName, LastName, EmployeeEmail, CreatedAt) VALUES (:first, :last, :email, NOW())');
        $stmt->execute([':first' => $first, ':last' => $last, ':email' => $email]);
        $id = (int)$pdo->lastInsertId();
        respond_json(['EmployeeID' => $id], 201);
    }

    public static function update($id)
    {
        $payload = get_json_input();
        if (!$payload) respond_error('Invalid JSON', 400);
        $first = sanitize_string($payload['FirstName'] ?? null);
        $last = sanitize_string($payload['LastName'] ?? null);
        $email = sanitize_string($payload['EmployeeEmail'] ?? null);
        $pdo = DB::getConnection();
        $fields = [];
        $params = [':id' => $id];
        if ($first !== null) { $fields[] = 'FirstName = :first'; $params[':first'] = $first; }
        if ($last !== null) { $fields[] = 'LastName = :last'; $params[':last'] = $last; }
        if ($email !== null) { $fields[] = 'EmployeeEmail = :email'; $params[':email'] = $email; }
        if (empty($fields)) respond_error('No fields to update', 400);
        $sql = 'UPDATE Employees SET ' . implode(', ', $fields) . ' WHERE EmployeeID = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond_json(['updated' => true]);
    }

    public static function delete($id)
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM Employees WHERE EmployeeID = :id');
        $stmt->execute([':id' => $id]);
        respond_json(['deleted' => true]);
    }
}
