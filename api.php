<?php
session_start();

// Configuration des headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no');

// Configuration PHP
set_time_limit(600);
ini_set('memory_limit', '-1');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialisation de la session si nécessaire
if (!isset($_SESSION['conversation'])) {
    $_SESSION['conversation'] = [];
}

if (!isset($_SESSION['selected_model'])) {
    $_SESSION['selected_model'] = 'tinyllama'; // Modèle par défaut
}

function handleError($message) {
    echo "data: " . json_encode(['error' => $message]) . "\n\n";
    ob_flush();
    flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $message = $data['message'] ?? '';
    $model = $_SESSION['selected_model'] ?? 'tinyllama';

    if (empty($message)) {
        handleError('Message vide reçu');
    }

    // Ajouter le message utilisateur à la conversation
    $_SESSION['conversation'][] = [
        'role' => 'user',
        'content' => $message,
        'timestamp' => time()
    ];

    // Configuration de la requête Ollama
    $url = 'http://localhost:11434/api/chat';
    $payload = json_encode([
        'model' => $model,
        'messages' => array_map(function($msg) {
            return ['role' => $msg['role'], 'content' => $msg['content']];
        }, $_SESSION['conversation']),
        'stream' => true
    ]);

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/json\r\n",
            'content' => $payload,
            'timeout' => 300,
            'ignore_errors' => true
        ],
    ];

    $context = stream_context_create($options);

    // Tentative de connexion
    $stream = @fopen($url, 'r', false, $context);
    if (!$stream) {
        $error = error_get_last();
        handleError('Impossible de se connecter au modèle : ' . ($error['message'] ?? 'Erreur inconnue'));
    }

    // Lecture du flux
    $timeout = 300;
    $startTime = time();
    $fullResponse = '';

    try {
        while (!feof($stream)) {
            if (time() - $startTime > $timeout) {
                throw new Exception('Timeout de connexion');
            }

            $chunk = fgets($stream);
            if ($chunk === false) {
                throw new Exception('Erreur de lecture du flux');
            }

            if (!empty(trim($chunk))) {
                $data = json_decode($chunk, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Erreur de décodage JSON');
                }

                if (isset($data['message']['content'])) {
                    $fullResponse .= $data['message']['content'];
                    echo "data: " . json_encode(['response' => $data['message']['content']]) . "\n\n";
                    ob_flush();
                    flush();
                    $startTime = time();
                }
            }

            usleep(10000); // 10ms
        }

        // Ajouter la réponse du modèle à la conversation
        $_SESSION['conversation'][] = [
            'role' => 'assistant',
            'content' => $fullResponse,
            'timestamp' => time()
        ];

    } catch (Exception $e) {
        handleError($e->getMessage());
    } finally {
        if (is_resource($stream)) {
            fclose($stream);
        }
    }
}

// Nettoyage de la session après 1 heure d'inactivité
$lastActivity = $_SESSION['last_activity'] ?? 0;
if (time() - $lastActivity > 3600) {
    session_unset();
    session_destroy();
} else {
    $_SESSION['last_activity'] = time();
}
if (count($_SESSION['conversation']) > 50) {
    array_shift($_SESSION['conversation']);
}
// TODO ajouter supprimer tous les messages
if (isset($_POST['clear_conversation'])) {
    $_SESSION['conversation'] = [];
}
exit;