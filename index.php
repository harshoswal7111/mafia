<?php
session_start();
require_once __DIR__ . '/src/GameState.php';

$action = $_GET['action'] ?? 'home';

switch ($action) {
    case 'host':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = GameState::createGame($_POST['hostName']);
            $_SESSION['gameCode'] = $gameCode;
            $_SESSION['playerId'] = GameState::getGame($gameCode)['hostPlayerId'];
            header("Location: ?action=lobby&code=$gameCode");
            exit;
        }
        include 'templates/host.php';
        break;

    case 'join':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = strtoupper($_POST['gameCode']);
            if (GameState::getGame($gameCode)) {
                $playerId = GameState::addPlayer($gameCode, $_POST['playerName']);
                $_SESSION['gameCode'] = $gameCode;
                $_SESSION['playerId'] = $playerId;
                header("Location: ?action=lobby&code=$gameCode");
                exit;
            }
        }
        include 'templates/join.php';
        break;

    case 'lobby':
        $gameCode = $_GET['code'] ?? '';
        $gameData = GameState::getGame($gameCode);
        if (!$gameData) {
            header("Location: ?action=home");
            exit;
        }
        include 'templates/lobby.php';
        break;

    default:
        include 'templates/home.php';
        break;
}
?>