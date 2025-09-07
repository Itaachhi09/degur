<?php
declare(strict_types=1);
require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/helpers.php';

class PayrollController
{
    public static function listRuns()
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT PayrollRunID, StartDate, EndDate, PaymentDate, Status FROM PayrollRuns ORDER BY PaymentDate DESC LIMIT 100');
        respond_json($stmt->fetchAll());
    }

    public static function getRun($id)
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM PayrollRuns WHERE PayrollRunID = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) respond_error('Payroll run not found', 404);
        respond_json($row);
    }

    public static function createRun()
    {
        $p = get_json_input();
        if (!$p) respond_error('Invalid JSON', 400);
        $start = sanitize_string($p['start_date'] ?? '');
        $end = sanitize_string($p['end_date'] ?? '');
        $payment = sanitize_string($p['payment_date'] ?? '');
        if (!$start || !$end || !$payment) respond_error('start_date, end_date, payment_date required', 400);
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO PayrollRuns (StartDate, EndDate, PaymentDate, Status, CreatedAt) VALUES (:s, :e, :p, :status, NOW())');
        $stmt->execute([':s'=>$start, ':e'=>$end, ':p'=>$payment, ':status'=>'Pending']);
        respond_json(['PayrollRunID' => (int)$pdo->lastInsertId()], 201);
    }

    public static function updateRun($id)
    {
        $p = get_json_input();
        if (!$p) respond_error('Invalid JSON', 400);
        $fields = [];
        $params = [':id'=>$id];
        if (isset($p['Status'])) { $fields[] = 'Status = :status'; $params[':status'] = sanitize_string($p['Status']); }
        if (empty($fields)) respond_error('No fields to update', 400);
        $sql = 'UPDATE PayrollRuns SET ' . implode(', ', $fields) . ' WHERE PayrollRunID = :id';
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond_json(['updated' => true]);
    }

    public static function deleteRun($id)
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM PayrollRuns WHERE PayrollRunID = :id');
        $stmt->execute([':id'=>$id]);
        respond_json(['deleted' => true]);
    }
}
