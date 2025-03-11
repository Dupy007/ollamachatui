<?php
header('Content-Type: application/json');

try {
    $models = json_decode(file_get_contents('http://localhost:11434/api/tags'), true)['models'];
    $models = array_map(function($model) {
        return ['name' => $model['name']];
    }, $models);
    
    echo json_encode($models);
} catch (Exception $e) {
    echo json_encode([]);
}