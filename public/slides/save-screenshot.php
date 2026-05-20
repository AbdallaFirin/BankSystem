<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

$name = preg_replace('/[^a-z0-9_-]/', '', $_POST['name'] ?? 'shot');
$data = $_POST['data'] ?? '';

if (!$data || !$name) {
    echo json_encode(['ok' => false, 'msg' => 'Missing data']);
    exit;
}

$data = preg_replace('#^data:image/\w+;base64,#i', '', $data);
$bytes = base64_decode($data);
$dir = __DIR__ . '/screenshots';
if (!is_dir($dir)) mkdir($dir, 0755, true);
file_put_contents($dir . '/' . $name . '.png', $bytes);
echo json_encode(['ok' => true, 'file' => 'screenshots/' . $name . '.png']);
