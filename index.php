<?php
session_start();
require_once __DIR__ . '/src/GameState.php';

$action = $_GET['action'] ?? 'home';

switch ($action) {
    case 'host':
        // ... existing host logic ...

    case 'join':
        // ... existing join logic ...

    case 'lobby':
        // ... existing lobby logic ...

    case 'start_game':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_SESSION['gameCode'] ?? '';
            $gameData = GameState::getGame($gameCode);
            
            if ($gameData && $_SESSION['playerId'] === $gameData['hostPlayerId']) {
                // Update game phase to night
                $gameData['phase'] = 'night';
                GameState::saveGame($gameCode, $gameData);
                header("Location: ?action=game&code=$gameCode");
                exit;
            }
        }
        header("Location: ?action=home");
        exit;

    case 'game':
        $gameCode = $_GET['code'] ?? '';
        $gameData = GameState::getGame($gameCode);
        if (!$gameData) {
            header("Location: ?action=home");
            exit;
        }
        include 'templates/game.php';
        break;

    default:
        include 'templates/home.php';
}
?>