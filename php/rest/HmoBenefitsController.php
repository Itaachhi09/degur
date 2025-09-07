<?php
declare(strict_types=1);
require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/helpers.php';

class HmoBenefitsController {
    public static function list() {
        $db = DB::getConnection();
        $stmt = $db->prepare('SELECT * FROM hmo_benefits ORDER BY id DESC');
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond_json($rows);
    }

    public static function get($id) {
        $db = DB::getConnection();
        $stmt = $db->prepare('SELECT * FROM hmo_benefits WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) respond_error('Not found', 404);
        respond_json($row);
    }

    public static function create() {
        $input = get_json_input();
        $name = sanitize_string($input['name'] ?? '');
        $description = sanitize_string($input['description'] ?? '');
        $coverage = sanitize_string($input['coverage'] ?? '');

        if ($name === '') respond_error('Name required', 400);

        $db = DB::getConnection();
        $stmt = $db->prepare('INSERT INTO hmo_benefits (name, description, coverage, created_at) VALUES (:name, :description, :coverage, NOW())');
        $stmt->execute([':name'=>$name, ':description'=>$description, ':coverage'=>$coverage]);
        $id = (int)$db->lastInsertId();
        respond_json(['id'=>$id]);
    }

    public static function update($id) {
        $input = get_json_input();
        $name = sanitize_string($input['name'] ?? null);
        $description = sanitize_string($input['description'] ?? null);
        $coverage = sanitize_string($input['coverage'] ?? null);

        $db = DB::getConnection();
        $fields = [];
        $params = [':id'=>$id];
        if ($name !== null) { $fields[] = 'name = :name'; $params[':name'] = $name; }
        if ($description !== null) { $fields[] = 'description = :description'; $params[':description'] = $description; }
        if ($coverage !== null) { $fields[] = 'coverage = :coverage'; $params[':coverage'] = $coverage; }

        if (empty($fields)) respond_error('No fields to update', 400);

        $sql = 'UPDATE hmo_benefits SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        respond_json(['updated' => true]);
    }

    public static function delete($id) {
        $db = DB::getConnection();
        $stmt = $db->prepare('DELETE FROM hmo_benefits WHERE id = :id');
        $stmt->execute([':id' => $id]);
        respond_json(['deleted' => true]);
    }
}
