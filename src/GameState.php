<?php
class GameState {
    public static function getGameFilePath($gameCode) {
        $sanitizedCode = preg_replace('/[^A-Za-z0-9]/', '', $gameCode);
        return __DIR__ . '/../data/games/' . $sanitizedCode . '.php';
    }

    public static function getGame($gameCode) {
        $filePath = self::getGameFilePath($gameCode);
        if (!file_exists($filePath)) {
            return false;
        }
        return include $filePath;
    }

    public static function saveGame($gameCode, $gameData) {
        $filePath = self::getGameFilePath($gameCode);
        $handle = fopen($filePath, 'c');
        
        if (flock($handle, LOCK_EX)) {
            $phpFileContent = "<?php\n";
            $phpFileContent .= "// Prevent direct script execution\n";
            $phpFileContent .= "if (basename(__FILE__) == basename(\$_SERVER[\"SCRIPT_FILENAME\"])) { http_response_code(403); die(\"Forbidden\"); }\n\n";
            $phpFileContent .= "return " . var_export($gameData, true) . ";\n";
            $phpFileContent .= "?>";
            
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $phpFileContent);
            fflush($handle);
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }

    public static function createGame($hostName) {
        $gameCode = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
        $initialData = [
            'gameCode' => $gameCode,
            'hostPlayerId' => uniqid(),
            'players' => [],
            'settings' => [
                'storytellerMode' => false,
                'sheriffMode' => false,
                'mafiaCount' => 1
            ],
            'phase' => 'lobby',
            'winner' => null
        ];
        self::saveGame($gameCode, $initialData);
        return $gameCode;
    }

    public static function addPlayer($gameCode, $playerName) {
        $gameData = self::getGame($gameCode);
        if (!$gameData) return false;
        
        $playerId = uniqid();
        $gameData['players'][$playerId] = [
            'name' => $playerName,
            'role' => null,
            'status' => 'alive',
            'votedFor' => null,
            'nightActionTarget' => null
        ];
        
        self::saveGame($gameCode, $gameData);
        return $playerId;
    }
}
?>