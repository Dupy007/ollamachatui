<?php
session_start();

if (!isset($_SESSION['conversation'])) {
    $_SESSION['conversation'] = [];
    $_SESSION['selected_model'] = 'tinyllama';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat avec Ollama</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="chat-container">
        <div id="model-selector">
            <label for="model-select">Modèle :</label>
            <select id="model-select">
            </select>
        </div>
        <div id="chat-messages">
            <?php
            if (!empty($_SESSION['conversation'])) {
                foreach ($_SESSION['conversation'] as $msg) {
                    $class = $msg['role'] === 'user' ? 'user-message' : 'assistant-message';
                    echo "<div class='message $class'>";
                    echo htmlspecialchars($msg['content']);
                    echo "<div class='timestamp'>" . date('H:i:s', $msg['timestamp']) . "</div>";
                    echo "</div>";
                }
            }
            ?>
        </div>
        
        <div id="input-container">
            <input type="text" id="user-input" placeholder="Tapez votre message...">
            <button id="send-btn">Envoyer</button>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetch('get_models.php')
                .then(response => response.json())
                .then(models => {
                    const select = document.getElementById('model-select');
                    models.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.name;
                        option.textContent = model.name;
                        if (model.name === "<?= $_SESSION['selected_model'] ?>") {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                });
        });
    </script>
</body>
</html>