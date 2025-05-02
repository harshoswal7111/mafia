<?php
// Start session for user tracking
session_start();

// Include core functionality
require_once 'src/GameState.php';

// Determine action based on URL parameters
$action = $_GET['action'] ?? 'home';

// Initialize GameState
$gameState = new GameState();

// Start output buffering to capture the content
ob_start();

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
            
            // Handle photo upload
            $photoPath = null;
            if (isset($_FILES['hostPhoto']) && $_FILES['hostPhoto']['error'] === UPLOAD_ERR_OK) {
                $tempName = $_FILES['hostPhoto']['tmp_name'];
                $fileName = $_FILES['hostPhoto']['name'];
                $fileSize = $_FILES['hostPhoto']['size'];
                $fileType = $_FILES['hostPhoto']['type'];
                
                // Validate file type and size
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($fileType, $allowedTypes)) {
                    $_SESSION['error'] = "Only JPG, PNG, and GIF images are allowed.";
                    header('Location: index.php');
                    exit;
                }
                
                if ($fileSize > $maxSize) {
                    $_SESSION['error'] = "File size must be less than 2MB.";
                    header('Location: index.php');
                    exit;
                }
                
                // Generate unique file name
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid('player_') . '.' . $extension;
                $targetPath = __DIR__ . '/data/images/' . $newFileName;
                
                // Move uploaded file
                if (move_uploaded_file($tempName, $targetPath)) {
                    $photoPath = 'data/images/' . $newFileName;
                } else {
                    // Check for upload errors
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in the HTML form',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                    ];
                    
                    if (isset($uploadErrors[$_FILES['hostPhoto']['error']])) {
                        $_SESSION['error'] = "Upload error: " . $uploadErrors[$_FILES['hostPhoto']['error']];
                    } else {
                        $_SESSION['error'] = "Failed to save uploaded file. Check directory permissions.";
                    }
                    header('Location: index.php');
                    exit;
                }
            }
            
            try {
                // Check if data/games directory is writable
                $gamesDir = __DIR__ . '/data/games';
                if (!is_writable($gamesDir)) {
                    throw new Exception("Directory not writable: $gamesDir");
                }
                
                $gameCode = $gameState->createGame($hostName, $photoPath);
                
                if (empty($gameCode)) {
                    throw new Exception("Failed to create game. Game code is empty.");
                }
                
                // Store game code and player ID in session
                $_SESSION['gameCode'] = $gameCode;
                $_SESSION['playerId'] = $gameState->getHostPlayerId($gameCode);
                
                header("Location: index.php?action=lobby&code=$gameCode");
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = "Error creating game: " . $e->getMessage();
                header('Location: index.php');
                exit;
            }
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
            
            // Handle photo upload
            $photoPath = null;
            if (isset($_FILES['playerPhoto']) && $_FILES['playerPhoto']['error'] === UPLOAD_ERR_OK) {
                $tempName = $_FILES['playerPhoto']['tmp_name'];
                $fileName = $_FILES['playerPhoto']['name'];
                $fileSize = $_FILES['playerPhoto']['size'];
                $fileType = $_FILES['playerPhoto']['type'];
                
                // Validate file type and size
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($fileType, $allowedTypes)) {
                    $_SESSION['error'] = "Only JPG, PNG, and GIF images are allowed.";
                    header('Location: index.php');
                    exit;
                }
                
                if ($fileSize > $maxSize) {
                    $_SESSION['error'] = "File size must be less than 2MB.";
                    header('Location: index.php');
                    exit;
                }
                
                // Generate unique file name
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid('player_') . '.' . $extension;
                $targetPath = __DIR__ . '/data/images/' . $newFileName;
                
                // Move uploaded file
                if (move_uploaded_file($tempName, $targetPath)) {
                    $photoPath = 'data/images/' . $newFileName;
                }
            }
            
            // Add player to the game
            $playerId = $gameState->addPlayer($gameCode, $playerName, $photoPath);
            
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
    case 'remove_player':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gameCode = $_POST['gameCode'] ?? '';
            $hostId = $_SESSION['playerId'] ?? '';
            $playerToRemove = $_POST['playerId'] ?? '';
            
            if (empty($gameCode) || empty($hostId) || empty($playerToRemove)) {
                $_SESSION['error'] = "Missing required parameters.";
                header("Location: index.php?action=lobby&code=$gameCode");
                exit;
            }
            
            $result = $gameState->removePlayer($gameCode, $hostId, $playerToRemove);
            
            if ($result['success']) {
                $_SESSION['success'] = "Player '{$result['playerName']}' has been removed from the game.";
                header("Location: index.php?action=lobby&code=$gameCode");
                if ($game['phase'] !== 'lobby') {
                    header("Location: index.php?action=game&code=$gameCode");
                }
            } else {
                $_SESSION['error'] = $result['error'];
                header("Location: index.php?action=lobby&code=$gameCode");
            }
            exit;
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        break;
    default:
        // Default to home
        require 'templates/home.php';
        break;
}

// Capture the content and clean the buffer
$content = ob_get_clean();

// Include the layout template, which will use the captured content
require 'templates/layout.php';
?>