<?php
declare(strict_types=1);
require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/helpers.php';

class DocumentsController
{
    public static function list()
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT DocumentID, EmployeeID, FileName, MimeType, CreatedAt FROM Documents ORDER BY CreatedAt DESC LIMIT 200');
        respond_json($stmt->fetchAll());
    }

    public static function get($id)
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM Documents WHERE DocumentID = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) respond_error('Document not found', 404);
        respond_json($row);
    }

    public static function create()
    {
        // For now accept JSON metadata for an uploaded file; actual multipart upload handled elsewhere
        $p = get_json_input();
        if (!$p) respond_error('Invalid JSON', 400);
        $employee = (int)($p['EmployeeID'] ?? 0);
        $fileName = sanitize_string($p['FileName'] ?? '');
        $mime = sanitize_string($p['MimeType'] ?? '');
        if (!$employee || !$fileName) respond_error('EmployeeID and FileName are required', 400);
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO Documents (EmployeeID, FileName, MimeType, CreatedAt) VALUES (:emp, :fn, :mt, NOW())');
        $stmt->execute([':emp'=>$employee, ':fn'=>$fileName, ':mt'=>$mime]);
        respond_json(['DocumentID' => (int)$pdo->lastInsertId()], 201);
    }

    public static function delete($id)
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM Documents WHERE DocumentID = :id');
        $stmt->execute([':id' => $id]);
        respond_json(['deleted' => true]);
    }
}
