<?php

class GameState {
    /**
     * Get the full path to a game state file
     * 
     * @param string $gameCode The unique game code
     * @return string The file path
     */
    private function getGameFilePath($gameCode) {
        // Basic sanitization to prevent directory traversal
        $gameCode = preg_replace('/[^a-zA-Z0-9]/', '', $gameCode);
        
        return __DIR__ . '/../data/games/' . $gameCode . '.php';
    }
    
    /**
     * Get a game's state data
     * 
     * @param string $gameCode The unique game code
     * @return array|bool The game data or false if not found
     */
    public function getGame($gameCode) {
        $filePath = $this->getGameFilePath($gameCode);
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Include the file which returns the game data array
        $gameData = include $filePath;
        
        return $gameData;
    }
    
    /**
     * Save a game's state data
     * 
     * @param string $gameCode The unique game code
     * @param array $gameData The game data to save
     * @return bool Whether the save was successful
     */
    public function saveGame($gameCode, $gameData) {
        $filePath = $this->getGameFilePath($gameCode);
        
        // Open file with 'c' mode - create if not exists, don't truncate
        $handle = fopen($filePath, 'c');
        
        if (!$handle) {
            return false;
        }
        
        // Acquire an exclusive lock
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return false;
        }
        
        // Format the data as a PHP string
        $phpFileContent = "<?php\n";
        $phpFileContent .= "// Prevent direct script execution\n";
        $phpFileContent .= "if (basename(__FILE__) == basename(\$_SERVER[\"SCRIPT_FILENAME\"])) { http_response_code(403); die(\"Forbidden\"); }\n\n";
        $phpFileContent .= "// Return the game state array when included\n";
        $phpFileContent .= "return " . var_export($gameData, true) . ";\n";
        $phpFileContent .= "?>";
        
        // Clear the file and write the new content
        ftruncate($handle, 0);
        rewind($handle);
        $writeSuccess = fwrite($handle, $phpFileContent);
        
        // Flush, release lock, and close
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        
        return $writeSuccess !== false;
    }
    
    /**
     * Generate a unique game code
     * 
     * @return string A 5-character alphanumeric code
     */
    private function generateGameCode() {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed potentially confusing chars like O, 0, 1, I
        $gameCode = '';
        
        for ($i = 0; $i < 5; $i++) {
            $gameCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if the code already exists, regenerate if it does
        $filePath = $this->getGameFilePath($gameCode);
        if (file_exists($filePath)) {
            return $this->generateGameCode(); // Recursively try again
        }
        
        return $gameCode;
    }
    
    /**
     * Generate a unique player ID
     * 
     * @return string A unique player identifier
     */
    private function generatePlayerId() {
        return uniqid('player_');
    }
    
    /**
     * Create a new game
     * 
     * @param string $hostName The name of the host player
     * @param string|null $photoPath The path to the host's profile photo (optional)
     * @return string The game code
     */
    public function createGame($hostName, $photoPath = null) {
        $gameCode = $this->generateGameCode();
        $hostId = $this->generatePlayerId();
        
        $gameData = [
            'gameCode' => $gameCode,
            'hostPlayerId' => $hostId,
            'players' => [
                $hostId => [
                    'name' => $hostName,
                    'role' => null,
                    'status' => 'alive',
                    'votedFor' => null,
                    'nightActionTarget' => null,
                    'photoPath' => $photoPath
                ]
            ],
            'settings' => [
                'storytellerMode' => false,
                'sheriffMode' => false,
                'mafiaCount' => 1
            ],
            'phase' => 'lobby',
            'currentNight' => 0,
            'actionsNeeded' => [],
            'nightResults' => [],
            'voteResults' => [],
            'winner' => null
        ];
        
        $this->saveGame($gameCode, $gameData);
        
        return $gameCode;
    }
    
    /**
     * Get the host player ID for a game
     * 
     * @param string $gameCode The unique game code
     * @return string|bool The host player ID or false if not found
     */
    public function getHostPlayerId($gameCode) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return false;
        }
        
        return $game['hostPlayerId'];
    }
    
    /**
     * Add a new player to a game
     * 
     * @param string $gameCode The unique game code
     * @param string $playerName The name of the new player
     * @param string|null $photoPath The path to the player's profile photo (optional)
     * @return string|bool The player ID or false if failed
     */
    public function addPlayer($gameCode, $playerName, $photoPath = null) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return false;
        }
        
        // Check if the game is in lobby phase
        if ($game['phase'] !== 'lobby') {
            return false;
        }
        
        // Check for duplicate player names
        foreach ($game['players'] as $player) {
            if (strtolower($player['name']) === strtolower($playerName)) {
                return false;
            }
        }
        
        // Generate a new player ID
        $playerId = $this->generatePlayerId();
        
        // Add the player to the game
        $game['players'][$playerId] = [
            'name' => $playerName,
            'role' => null,
            'status' => 'alive',
            'votedFor' => null,
            'nightActionTarget' => null,
            'photoPath' => $photoPath
        ];
        
        // Save the updated game state
        $this->saveGame($gameCode, $game);
        
        return $playerId;
    }
    
    /**
     * Update game settings
     * 
     * @param string $gameCode The unique game code
     * @param array $settings The settings to update
     * @return bool Whether the update was successful
     */
    public function updateSettings($gameCode, $settings) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return false;
        }
        
        // Check if the game is in lobby phase
        if ($game['phase'] !== 'lobby') {
            return false;
        }
        
        // Update settings
        foreach ($settings as $key => $value) {
            if (isset($game['settings'][$key])) {
                $game['settings'][$key] = $value;
            }
        }
        
        // Save the updated game state
        return $this->saveGame($gameCode, $game);
    }
    
    /**
     * Start the game by assigning roles
     * 
     * @param string $gameCode The unique game code
     * @return bool Whether the game was started successfully
     */
    public function startGame($gameCode) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return false;
        }
        
        // Check if the game is in lobby phase
        if ($game['phase'] !== 'lobby') {
            return false;
        }
        
        // Check if there are enough players
        $playerCount = count($game['players']);
        $mafiaCount = $game['settings']['mafiaCount'];
        
        // Minimum required: mafia + doctor + detective + at least one villager
        if ($playerCount < $mafiaCount + 3) {
            return false;
        }
        
        // Prepare roles
        $roles = array_merge(
            array_fill(0, $mafiaCount, 'mafia'),
            ['doctor', 'detective'],
            array_fill(0, $playerCount - $mafiaCount - 2, 'villager')
        );
        
        // Shuffle roles
        shuffle($roles);
        
        // Assign roles to players
        $playerIds = array_keys($game['players']);
        foreach ($playerIds as $index => $playerId) {
            $game['players'][$playerId]['role'] = $roles[$index];
        }
        
        // Initialize night phase
        $game['phase'] = 'night';
        $game['currentNight'] = 1;
        $game['actionsNeeded'] = [];
        
        // Determine which players need to take night actions
        foreach ($game['players'] as $playerId => $player) {
            if ($player['status'] === 'alive' && in_array($player['role'], ['mafia', 'doctor', 'detective'])) {
                $game['actionsNeeded'][] = $playerId;
            }
        }
        
        // Initialize sheriff if sheriff mode is enabled
        if ($game['settings']['sheriffMode']) {
            $villagerPlayerIds = array_keys(array_filter($game['players'], function($player) {
                return $player['role'] === 'villager';
            }));
            
            if (!empty($villagerPlayerIds)) {
                // Randomly select a villager to be sheriff
                $sheriffIndex = array_rand($villagerPlayerIds);
                $sheriffId = $villagerPlayerIds[$sheriffIndex];
                $game['sheriff'] = $sheriffId;
            }
        }
        
        // Save the updated game state
        return $this->saveGame($gameCode, $game);
    }
    
    /**
     * Submit a night action
     * 
     * @param string $gameCode The unique game code
     * @param string $playerId The ID of the player taking the action
     * @param string $targetId The ID of the target player
     * @return array Result of the action
     */
    public function submitNightAction($gameCode, $playerId, $targetId) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return ['success' => false, 'error' => 'Game not found.'];
        }
        
        // Check if the game is in night phase
        if ($game['phase'] !== 'night') {
            return ['success' => false, 'error' => 'Not night phase.'];
        }
        
        // Check if the player is alive
        if (!isset($game['players'][$playerId]) || $game['players'][$playerId]['status'] !== 'alive') {
            return ['success' => false, 'error' => 'Player not alive.'];
        }
        
        // Check if the player needs to take action
        if (!in_array($playerId, $game['actionsNeeded'])) {
            return ['success' => false, 'error' => 'Action not needed from this player.'];
        }
        
        // Check if the target player exists
        if (!isset($game['players'][$targetId])) {
            return ['success' => false, 'error' => 'Target player not found.'];
        }
        
        // Record the night action
        $game['players'][$playerId]['nightActionTarget'] = $targetId;
        
        // Remove this player from actionsNeeded
        $game['actionsNeeded'] = array_values(array_diff($game['actionsNeeded'], [$playerId]));
        
        // If all needed actions are taken, process the night
        if (empty($game['actionsNeeded'])) {
            $this->processNightActions($game);
            $gameOver = $this->checkWinConditions($game);
            
            // Only change phase if game is not over
            if (!$gameOver) {
                $game['phase'] = 'day_results';
            }
        }
        
        // Save the updated game state
        $this->saveGame($gameCode, $game);
        
        return ['success' => true];
    }
    
    /**
     * Process all night actions
     * 
     * @param array &$game Reference to the game data
     */
    private function processNightActions(&$game) {
        // Collect all mafia targets
        $mafiaTargets = [];
        foreach ($game['players'] as $playerId => $player) {
            if ($player['status'] === 'alive' && $player['role'] === 'mafia' && $player['nightActionTarget'] !== null) {
                $mafiaTargets[] = $player['nightActionTarget'];
            }
        }
        
        // Determine the mafia kill (most voted target)
        $mafiaKill = null;
        if (!empty($mafiaTargets)) {
            $targetCounts = array_count_values($mafiaTargets);
            arsort($targetCounts);
            // In case of tie, take the first one (random among tied targets)
            $mafiaKill = key($targetCounts);
        }
        
        // Get doctor save
        $doctorSave = null;
        foreach ($game['players'] as $playerId => $player) {
            if ($player['status'] === 'alive' && $player['role'] === 'doctor' && $player['nightActionTarget'] !== null) {
                $doctorSave = $player['nightActionTarget'];
                break; // Only one doctor
            }
        }
        
        // Get detective investigation
        $detectiveTarget = null;
        $detectiveResult = null;
        $detectiveId = null;
        foreach ($game['players'] as $playerId => $player) {
            if ($player['status'] === 'alive' && $player['role'] === 'detective' && $player['nightActionTarget'] !== null) {
                $detectiveId = $playerId;
                $detectiveTarget = $player['nightActionTarget'];
                // Make sure the target exists
                if (isset($game['players'][$detectiveTarget])) {
                    $targetRole = $game['players'][$detectiveTarget]['role'];
                    $detectiveResult = ($targetRole === 'mafia') ? 'mafia' : 'not_mafia';
                }
                break; // Only one detective
            }
        }
        
        // Process kills
        $killed = null;
        if ($mafiaKill !== null && $mafiaKill !== $doctorSave) {
            // Make sure the target exists and is alive
            if (isset($game['players'][$mafiaKill]) && $game['players'][$mafiaKill]['status'] === 'alive') {
                $killed = $mafiaKill;
                $game['players'][$killed]['status'] = 'dead';
            }
        }
        
        // Record the night results
        $nightResults = [
            'night' => $game['currentNight'],
            'killed' => $killed,
            'investigation' => [
                'detectiveId' => $detectiveId,
                'playerId' => $detectiveTarget,
                'result' => $detectiveResult
            ]
        ];
        
        $game['nightResults'][$game['currentNight']] = $nightResults;
        
        // Reset night action targets
        foreach ($game['players'] as &$player) {
            $player['nightActionTarget'] = null;
        }
        
        // Increment night counter (will be used if game continues)
        $game['currentNight']++;
    }
    
    /**
     * Check win conditions
     * 
     * @param array &$game Reference to the game data
     * @return bool Whether the game is over
     */
    private function checkWinConditions(&$game) {
        $alivePlayers = array_filter($game['players'], function($player) {
            return $player['status'] === 'alive';
        });
        
        $aliveMafia = array_filter($alivePlayers, function($player) {
            return $player['role'] === 'mafia';
        });
        
        $aliveVillagers = array_filter($alivePlayers, function($player) {
            return $player['role'] !== 'mafia';
        });
        
        // Mafia wins if they equal or outnumber the villagers
        if (count($aliveMafia) >= count($aliveVillagers)) {
            $game['phase'] = 'end';
            $game['winner'] = 'mafia';
            return true;
        }
        // Villagers win if all mafia are dead
        elseif (count($aliveMafia) === 0) {
            $game['phase'] = 'end';
            $game['winner'] = 'villagers';
            return true;
        }
        
        // Game continues
        return false;
    }
    
    /**
     * Move to the next day phase (after seeing night results)
     * 
     * @param string $gameCode The unique game code
     * @return bool Whether the operation was successful
     */
    public function nextDayPhase($gameCode) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return false;
        }
        
        // Check if the game is in the day_results phase
        if ($game['phase'] !== 'day_results') {
            return false;
        }
        
        // Set phase to day_discussion
        $game['phase'] = 'day_discussion';
        
        // Clear any previous voting
        foreach ($game['players'] as &$player) {
            $player['votedFor'] = null;
        }
        
        // Save the updated game state
        return $this->saveGame($gameCode, $game);
    }
    
    /**
     * Move to the voting phase
     * 
     * @param string $gameCode The unique game code
     * @return bool Whether the operation was successful
     */
    public function startVoting($gameCode) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return false;
        }
        
        // Check if the game is in the day_discussion phase
        if ($game['phase'] !== 'day_discussion') {
            return false;
        }
        
        // Set phase to day_voting
        $game['phase'] = 'day_voting';
        
        // Save the updated game state
        return $this->saveGame($gameCode, $game);
    }
    
    /**
     * Cast a vote during the day voting phase
     * 
     * @param string $gameCode The unique game code
     * @param string $playerId The ID of the player voting
     * @param string $targetId The ID of the player being voted for
     * @return array Result of the vote
     */
    public function submitVote($gameCode, $playerId, $targetId) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return ['success' => false, 'error' => 'Game not found.'];
        }
        
        // Check if the game is in day_voting phase
        if ($game['phase'] !== 'day_voting') {
            return ['success' => false, 'error' => 'Not voting phase.'];
        }
        
        // Check if the player is alive
        if (!isset($game['players'][$playerId]) || $game['players'][$playerId]['status'] !== 'alive') {
            return ['success' => false, 'error' => 'Player not alive.'];
        }
        
        // Check if the target player exists and is alive
        if (!isset($game['players'][$targetId]) || $game['players'][$targetId]['status'] !== 'alive') {
            return ['success' => false, 'error' => 'Target player not alive.'];
        }
        
        // Record the vote
        $game['players'][$playerId]['votedFor'] = $targetId;
        
        // Check if all alive players have voted
        $allVoted = true;
        foreach ($game['players'] as $pid => $player) {
            if ($player['status'] === 'alive' && $player['votedFor'] === null) {
                $allVoted = false;
                break;
            }
        }
        
        // If all have voted, process the votes
        if ($allVoted) {
            $this->processVotes($game);
            $gameOver = $this->checkWinConditions($game);
            
            // Only change phase if game is not over
            if (!$gameOver) {
                // Set up night phase
                $game['phase'] = 'night';
                $game['actionsNeeded'] = [];
                
                // Determine which players need to take night actions
                foreach ($game['players'] as $pid => $player) {
                    if ($player['status'] === 'alive' && in_array($player['role'], ['mafia', 'doctor', 'detective'])) {
                        $game['actionsNeeded'][] = $pid;
                    }
                }
            }
        }
        
        // Save the updated game state
        $this->saveGame($gameCode, $game);
        
        return ['success' => true, 'allVoted' => $allVoted];
    }
    
    /**
     * Process all votes during the day voting phase
     * 
     * @param array &$game Reference to the game data
     */
    private function processVotes(&$game) {
        // Collect all votes
        $votes = [];
        foreach ($game['players'] as $playerId => $player) {
            if ($player['status'] === 'alive' && $player['votedFor'] !== null) {
                // Check if sheriff mode is enabled and this player is sheriff
                if (isset($game['settings']['sheriffMode']) && 
                    $game['settings']['sheriffMode'] && 
                    isset($game['sheriff']) && 
                    $playerId === $game['sheriff']) {
                    // Sheriff's vote counts twice
                    $votes[] = $player['votedFor'];
                    $votes[] = $player['votedFor'];
                } else {
                    $votes[] = $player['votedFor'];
                }
            }
        }
        
        // Count votes for each player
        $voteCounts = array_count_values($votes);
        arsort($voteCounts);
        
        // Get the player(s) with the most votes
        $maxVotes = reset($voteCounts);
        $mostVoted = [];
        foreach ($voteCounts as $pid => $count) {
            if ($count === $maxVotes) {
                $mostVoted[] = $pid;
            } else {
                break; // We've gone past the max count
            }
        }
        
        // Handle the vote result
        $eliminated = null;
        if (count($mostVoted) === 1) {
            // Clear majority - player is eliminated
            $eliminated = $mostVoted[0];
            $game['players'][$eliminated]['status'] = 'dead';
        }
        // If there's a tie, no one is eliminated
        
        // Record the vote results
        $voteResults = [
            'day' => $game['currentNight'] - 1, // The day number corresponds to the night before
            'votes' => $voteCounts,
            'eliminated' => $eliminated
        ];
        
        $game['voteResults'][$game['currentNight'] - 1] = $voteResults;
        
        // Reset all votes
        foreach ($game['players'] as &$player) {
            $player['votedFor'] = null;
        }
    }
    
    /**
     * Reset the game to lobby phase
     * 
     * @param string $gameCode The unique game code
     * @param string $playerId The ID of the player resetting
     * @return array Result of the action
     */
    public function resetGame($gameCode, $playerId) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return ['success' => false, 'error' => 'Game not found.'];
        }
        
        // Check if the player is the host
        if ($game['hostPlayerId'] !== $playerId) {
            return ['success' => false, 'error' => 'Only the host can reset the game.'];
        }
        
        // Check if the game is in end phase
        if ($game['phase'] !== 'end') {
            return ['success' => false, 'error' => 'Game is not over yet.'];
        }
        
        // Reset player roles and status
        foreach ($game['players'] as &$player) {
            $player['role'] = null;
            $player['status'] = 'alive';
            $player['votedFor'] = null;
            $player['nightActionTarget'] = null;
        }
        
        // Reset game state
        $game['phase'] = 'lobby';
        $game['currentNight'] = 0;
        $game['actionsNeeded'] = [];
        $game['nightResults'] = [];
        $game['voteResults'] = [];
        $game['winner'] = null;
        if (isset($game['sheriff'])) {
            unset($game['sheriff']);
        }
        
        // Save the updated game state
        $this->saveGame($gameCode, $game);
        
        return ['success' => true];
    }
    
    /**
     * Delete a game
     * 
     * @param string $gameCode The unique game code
     * @param string $playerId The ID of the player deleting
     * @return array Result of the action
     */
    public function deleteGame($gameCode, $playerId) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return ['success' => false, 'error' => 'Game not found.'];
        }
        
        // Check if the player is the host
        if ($game['hostPlayerId'] !== $playerId) {
            return ['success' => false, 'error' => 'Only the host can delete the game.'];
        }
        
        // Delete the game file
        $filePath = $this->getGameFilePath($gameCode);
        if (file_exists($filePath)) {
            unlink($filePath);
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to delete game file.'];
    }
    
    /**
     * Remove a player from a game
     * 
     * @param string $gameCode The unique game code
     * @param string $hostId The ID of the host player initiating the removal
     * @param string $playerId The ID of the player to remove
     * @return array Result of the action
     */
    public function removePlayer($gameCode, $hostId, $playerId) {
        $game = $this->getGame($gameCode);
        
        if (!$game) {
            return ['success' => false, 'error' => 'Game not found.'];
        }
        
        // Check if the requester is the host
        if ($game['hostPlayerId'] !== $hostId) {
            return ['success' => false, 'error' => 'Only the host can remove players.'];
        }
        
        // Check if the player exists
        if (!isset($game['players'][$playerId])) {
            return ['success' => false, 'error' => 'Player not found.'];
        }
        
        // Don't allow host to remove themselves
        if ($playerId === $hostId) {
            return ['success' => false, 'error' => 'Host cannot remove themselves.'];
        }
        
        // Get player name for return value
        $playerName = $game['players'][$playerId]['name'];
        
        // Remove the player
        unset($game['players'][$playerId]);
        
        // If we're in the night phase and this player was pending an action, remove them from actionsNeeded
        if ($game['phase'] === 'night' && in_array($playerId, $game['actionsNeeded'])) {
            $game['actionsNeeded'] = array_values(array_diff($game['actionsNeeded'], [$playerId]));
            
            // If all needed actions are now taken, process the night
            if (empty($game['actionsNeeded'])) {
                $this->processNightActions($game);
                $this->checkWinConditions($game);
                $game['phase'] = 'day_results';
            }
        }
        
        // If we're in the day_vote phase, check if all remaining players have voted
        if ($game['phase'] === 'day_vote') {
            $allVoted = true;
            foreach ($game['players'] as $pId => $player) {
                if ($player['status'] === 'alive' && $player['votedFor'] === null) {
                    $allVoted = false;
                    break;
                }
            }
            
            // If all have voted now, tally the votes
            if ($allVoted) {
                $this->tallyVotes($game);
                $this->checkWinConditions($game);
                $game['phase'] = 'end_day_results';
            }
        }
        
        // Save the updated game state
        $this->saveGame($gameCode, $game);
        
        return ['success' => true, 'playerName' => $playerName];
    }
}
?>