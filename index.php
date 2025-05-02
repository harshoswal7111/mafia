<?php
// Start session for user tracking
session_start();

// Include core functionality
require_once 'src/GameState.php';

// Determine action based on URL parameters
$action = $_GET['action'] ?? 'home';

// Initialize GameState
$gameState = new GameState();

// Route to appropriate handler based on action
switch ($action) {
    case 'home':
        require 'templates/home.php';
        break;
    case 'host':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hostName = $_POST['hostName'] ?? '';
            
            if (empty($hostName)) {
                $_SESSION['error'] = "Host name is required.";
                header('Location: index.php');
                exit;
            }
            
            $gameCode = $gameState->createGame($hostName);
            
            // Store game code and player ID in session
            $_SESSION['gameCode'] = $gameCode;
            $_SESSION['playerId'] = $gameState->getHostPlayerId($gameCode);
            
            header("Location: index.php?action=lobby&code=$gameCode");
            exit;
        } else {
            // If not a POST request, redirect to home
            header('Location: index.php');
            exit;
        }
        break;
    case 'join':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerName = $_POST['playerName'] ?? '';
            
            if (empty($gameCode) || empty($playerName)) {
                $_SESSION['error'] = "Game code and player name are required.";
                header('Location: index.php');
                exit;
            }
            
            $game = $gameState->getGame($gameCode);
            if (!$game) {
                $_SESSION['error'] = "Invalid game code.";
                header('Location: index.php');
                exit;
            }
            
            // Add player to the game
            $playerId = $gameState->addPlayer($gameCode, $playerName);
            
            if (!$playerId) {
                $_SESSION['error'] = "Failed to join the game.";
                header('Location: index.php');
                exit;
            }
            
            // Store game code and player ID in session
            $_SESSION['gameCode'] = $gameCode;
            $_SESSION['playerId'] = $playerId;
            
            header("Location: index.php?action=lobby&code=$gameCode");
            exit;
        } else {
            // If not a POST request, redirect to home
            header('Location: index.php');
            exit;
        }
        break;
    case 'lobby':
        $gameCode = $_GET['code'] ?? '';
        if (empty($gameCode)) {
            header('Location: index.php');
            exit;
        }
        
        $game = $gameState->getGame($gameCode);
        if (!$game) {
            $_SESSION['error'] = "Invalid game code.";
            header('Location: index.php');
            exit;
        }
        
        require 'templates/lobby.php';
        break;
    case 'lobby_status':
        $gameCode = $_GET['code'] ?? '';
        if (empty($gameCode)) {
            http_response_code(400);
            echo json_encode(['error' => 'Game code is required.']);
            exit;
        }
        
        $game = $gameState->getGame($gameCode);
        if (!$game) {
            http_response_code(404);
            echo json_encode(['error' => 'Game not found.']);
            exit;
        }
        
        // Return minimal needed lobby data for polling
        $lobbyData = [
            'players' => $game['players'],
            'settings' => $game['settings'],
            'phase' => $game['phase']
        ];
        
        header('Content-Type: application/json');
        echo json_encode($lobbyData);
        break;
    case 'update_settings':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerId = $_SESSION['playerId'] ?? '';
            
            if (empty($gameCode) || empty($playerId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Game code and player ID are required.']);
                exit;
            }
            
            $game = $gameState->getGame($gameCode);
            if (!$game || $game['hostPlayerId'] !== $playerId) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized.']);
                exit;
            }
            
            // Update settings
            $storytellerMode = isset($_POST['storytellerMode']) ? (bool)$_POST['storytellerMode'] : false;
            $sheriffMode = isset($_POST['sheriffMode']) ? (bool)$_POST['sheriffMode'] : false;
            $mafiaCount = (int)($_POST['mafiaCount'] ?? 1);
            
            // Ensure mafiaCount is at least 1
            $mafiaCount = max(1, $mafiaCount);
            
            $gameState->updateSettings($gameCode, [
                'storytellerMode' => $storytellerMode,
                'sheriffMode' => $sheriffMode,
                'mafiaCount' => $mafiaCount
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            break;
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        break;
    case 'start_game':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerId = $_SESSION['playerId'] ?? '';
            
            if (empty($gameCode) || empty($playerId)) {
                $_SESSION['error'] = "Invalid request.";
                header('Location: index.php');
                exit;
            }
            
            $game = $gameState->getGame($gameCode);
            if (!$game || $game['hostPlayerId'] !== $playerId) {
                $_SESSION['error'] = "Unauthorized.";
                header('Location: index.php');
                exit;
            }
            
            // Check minimum player count
            $playerCount = count($game['players']);
            $mafiaCount = $game['settings']['mafiaCount'];
            
            // Minimum required: mafia + doctor + detective + at least one villager
            if ($playerCount < $mafiaCount + 3) {
                $_SESSION['error'] = "Not enough players. Need at least " . ($mafiaCount + 3) . " players.";
                header("Location: index.php?action=lobby&code=$gameCode");
                exit;
            }
            
            // Start the game
            $gameState->startGame($gameCode);
            
            header("Location: index.php?action=game&code=$gameCode");
            exit;
        } else {
            header('Location: index.php');
            exit;
        }
        break;
    case 'game':
        $gameCode = $_GET['code'] ?? '';
        if (empty($gameCode)) {
            header('Location: index.php');
            exit;
        }
        
        $game = $gameState->getGame($gameCode);
        if (!$game) {
            $_SESSION['error'] = "Invalid game code.";
            header('Location: index.php');
            exit;
        }
        
        require 'templates/game.php';
        break;
    case 'submit_action':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerId = $_SESSION['playerId'] ?? '';
            $targetId = $_POST['targetId'] ?? '';
            
            if (empty($gameCode) || empty($playerId) || empty($targetId)) {
                echo json_encode(['error' => 'Missing required parameters.']);
                exit;
            }
            
            $result = $gameState->submitNightAction($gameCode, $playerId, $targetId);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        break;
    case 'start_vote':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerId = $_SESSION['playerId'] ?? '';
            
            if (empty($gameCode) || empty($playerId)) {
                echo json_encode(['error' => 'Missing required parameters.']);
                exit;
            }
            
            $result = $gameState->startVote($gameCode, $playerId);
            
            if ($result['success']) {
                header("Location: index.php?action=game&code=$gameCode");
            } else {
                echo json_encode($result);
            }
            exit;
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        break;
    case 'submit_vote':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerId = $_SESSION['playerId'] ?? '';
            $targetId = $_POST['targetId'] ?? '';
            
            if (empty($gameCode) || empty($playerId) || empty($targetId)) {
                echo json_encode(['error' => 'Missing required parameters.']);
                exit;
            }
            
            $result = $gameState->submitVote($gameCode, $playerId, $targetId);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        break;
    case 'proceed_to_night':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerId = $_SESSION['playerId'] ?? '';
            
            if (empty($gameCode) || empty($playerId)) {
                echo json_encode(['error' => 'Missing required parameters.']);
                exit;
            }
            
            $result = $gameState->proceedToNight($gameCode, $playerId);
            
            if ($result['success']) {
                header("Location: index.php?action=game&code=$gameCode");
            } else {
                echo json_encode($result);
            }
            exit;
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        break;
    case 'reset_game':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerId = $_SESSION['playerId'] ?? '';
            
            if (empty($gameCode) || empty($playerId)) {
                echo json_encode(['error' => 'Missing required parameters.']);
                exit;
            }
            
            $result = $gameState->resetGame($gameCode, $playerId);
            
            if ($result['success']) {
                header("Location: index.php?action=lobby&code=$gameCode");
            } else {
                echo json_encode($result);
            }
            exit;
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        break;
    case 'new_game':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $playerId = $_SESSION['playerId'] ?? '';
            
            if (empty($gameCode) || empty($playerId)) {
                echo json_encode(['error' => 'Missing required parameters.']);
                exit;
            }
            
            $result = $gameState->deleteGame($gameCode, $playerId);
            
            if ($result['success']) {
                // Clear session
                unset($_SESSION['gameCode']);
                unset($_SESSION['playerId']);
                
                header("Location: index.php");
            } else {
                echo json_encode($result);
            }
            exit;
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        break;
    case 'leave_game':
        // Clear session
        unset($_SESSION['gameCode']);
        unset($_SESSION['playerId']);
        
        header("Location: index.php");
        exit;
        break;
    default:
        // Default to home
        require 'templates/home.php';
        break;
}
?>