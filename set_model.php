<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $model = $data['model'] ?? 'tinyllama';
    
    if (!empty($model)) {
        $_SESSION['selected_model'] = $model;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Modèle invalide']);
    }
}