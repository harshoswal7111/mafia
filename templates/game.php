<?php
// Get current player ID and game data
$currentPlayerId = $_SESSION['playerId'] ?? '';
$isHost = ($currentPlayerId === $game['hostPlayerId']);
$gameCode = $game['gameCode'];
$currentPlayer = $game['players'][$currentPlayerId] ?? null;
$phase = $game['phase'];
$isStoryteller = $isHost && $game['settings']['storytellerMode'];
$isSheriff = isset($game['sheriff']) && $game['sheriff'] === $currentPlayerId;

// Helper function to check if player is alive
function isAlive($player) {
    return $player['status'] === 'alive';
}

// Helper function to get player name from ID
function getPlayerName($players, $playerId) {
    return $players[$playerId]['name'] ?? 'Unknown Player';
}

// Helper to get role display name
function getRoleDisplayName($role) {
    switch ($role) {
        case 'mafia':
            return '<span class="role-mafia">Mafia</span>';
        case 'doctor':
            return '<span class="role-doctor">Doctor</span>';
        case 'detective':
            return '<span class="role-detective">Detective</span>';
        case 'villager':
            return '<span class="role-villager">Villager</span>';
        default:
            return 'Unknown';
    }
}

// Get alive players
$alivePlayers = array_filter($game['players'], function($player) {
    return $player['status'] === 'alive';
});

// Get all mafia players for mafia members to see
$mafiaPlayers = array_filter($game['players'], function($player) {
    return $player['role'] === 'mafia';
});
?>

<div class="bg-white p-4 sm:p-6 rounded-lg shadow-md mb-6">
    <!-- Game header with phase indicator -->
    <div class="flex flex-col items-center mb-5">
        <h2 class="text-2xl font-bold text-red-900 text-center mb-3">Mafia Game</h2>
        <div class="w-full px-4 py-3 bg-gray-200 rounded-md">
            <div class="flex flex-col items-center">
                <div class="font-bold text-sm">GAME CODE:</div>
                <div class="text-2xl tracking-widest font-bold mb-2"><?php echo $gameCode; ?></div>
                
                <div class="font-bold text-sm mt-1">CURRENT PHASE:</div>
                <div>
                <?php if ($phase === 'night'): ?>
                    <span class="text-xl text-indigo-700 font-bold">Night <?php echo $game['currentNight']; ?></span>
                <?php elseif ($phase === 'day_results'): ?>
                    <span class="text-xl text-yellow-600 font-bold">Day <?php echo $game['currentNight']-1; ?> Results</span>
                <?php elseif ($phase === 'day_vote'): ?>
                    <span class="text-xl text-yellow-600 font-bold">Day <?php echo $game['currentNight']-1; ?> Voting</span>
                <?php elseif ($phase === 'end_day_results'): ?>
                    <span class="text-xl text-yellow-600 font-bold">Day <?php echo $game['currentNight']-1; ?> End</span>
                <?php elseif ($phase === 'end'): ?>
                    <span class="text-xl text-red-600 font-bold">Game Over</span>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Player role and status card -->
    <div class="mb-6 p-4 <?php echo isAlive($currentPlayer) ? 'bg-green-100' : 'bg-red-100'; ?> rounded-lg">
        <h3 class="text-lg font-bold mb-3">Your Role</h3>
        <div class="flex flex-col">
            <div class="mb-3">
                <p class="text-xl font-bold">
                    <?php echo getRoleDisplayName($currentPlayer['role']); ?>
                    <?php if ($isSheriff): ?>
                        <span class="sheriff-badge text-xl" title="Sheriff">👮</span>
                    <?php endif; ?>
                </p>
                <p class="mt-2 text-base">Status: 
                    <span class="font-semibold <?php echo isAlive($currentPlayer) ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $currentPlayer['status'] === 'alive' ? 'Alive' : 'Dead'; ?>
                    </span>
                </p>
            </div>
            
            <?php if ($currentPlayer['role'] === 'mafia'): ?>
                <div class="bg-red-700 text-white px-4 py-2 rounded text-base font-bold mb-3">
                    Teammates:
                    <?php 
                    $teammates = [];
                    foreach ($mafiaPlayers as $playerId => $player) {
                        if ($playerId !== $currentPlayerId) {
                            $teammates[] = htmlspecialchars($player['name']);
                        }
                    }
                    echo !empty($teammates) ? implode(', ', $teammates) : 'None';
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-3 text-base">
            <p>
                <?php if ($currentPlayer['role'] === 'mafia'): ?>
                    Each night, you and the other mafia members choose a villager to eliminate.
                <?php elseif ($currentPlayer['role'] === 'doctor'): ?>
                    Each night, choose a player (including yourself) to protect from the mafia.
                <?php elseif ($currentPlayer['role'] === 'detective'): ?>
                    Each night, investigate one player to discover if they are mafia.
                <?php else: ?>
                    You are a villager. Use the day discussions to identify the mafia!
                <?php endif; ?>
                
                <?php if ($isSheriff): ?>
                    <span class="block mt-2 font-semibold">As the Sheriff, your vote counts twice during the day voting phase.</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- NIGHT PHASE -->
    <?php if ($phase === 'night'): ?>
        <div class="mb-6 p-4 bg-indigo-100 rounded-lg">
            <h3 class="text-lg font-bold mb-3 text-indigo-900">Night Phase</h3>
            
            <?php if (!isAlive($currentPlayer)): ?>
                <p class="text-base">You are dead and cannot perform night actions.</p>
            
            <?php elseif (in_array($currentPlayer['role'], ['mafia', 'doctor', 'detective'])): ?>
                <?php if ($currentPlayer['nightActionTarget'] === null): ?>
                    <?php if (in_array($currentPlayerId, $game['actionsNeeded'])): ?>
                        <p class="mb-4 text-lg">
                            <?php if ($currentPlayer['role'] === 'mafia'): ?>
                                Choose a player to eliminate:
                            <?php elseif ($currentPlayer['role'] === 'doctor'): ?>
                                Choose a player to save:
                            <?php elseif ($currentPlayer['role'] === 'detective'): ?>
                                Choose a player to investigate:
                            <?php endif; ?>
                        </p>
                        
                        <form id="nightActionForm" class="space-y-3">
                            <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                            
                            <div class="space-y-2">
                                <?php foreach ($alivePlayers as $playerId => $player): ?>
                                    <?php if ($currentPlayer['role'] === 'mafia' && $player['role'] === 'mafia'): continue; endif; ?>
                                    <label for="target_<?php echo $playerId; ?>" class="block p-3 border rounded-lg cursor-pointer mb-2 hover:bg-indigo-50 <?php echo ($playerId === $currentPlayerId) ? 'border-indigo-500' : 'border-gray-300'; ?>">
                                        <div class="flex items-center">
                                            <input type="radio" id="target_<?php echo $playerId; ?>" name="targetId" value="<?php echo $playerId; ?>" 
                                                class="mr-3 h-5 w-5" required>
                                            <span class="text-lg">
                                                <?php echo htmlspecialchars($player['name']); ?>
                                                <?php if ($playerId === $currentPlayerId): ?> (You) <?php endif; ?>
                                            </span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-5">
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-4 rounded-lg text-lg">
                                    Confirm Selection
                                </button>
                            </div>
                        </form>
                        
                        <div id="actionMessage" class="mt-4 hidden"></div>
                    <?php else: ?>
                        <p class="text-indigo-700 text-center py-3 text-lg">
                            You've already submitted your night action. Please wait for others.
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-indigo-700 text-center py-3 text-lg">
                        You've chosen <?php echo htmlspecialchars(getPlayerName($game['players'], $currentPlayer['nightActionTarget'])); ?>. 
                        Please wait for others.
                    </p>
                <?php endif; ?>
            
            <?php else: ?>
                <p class="text-center py-3 text-lg">You are a regular villager. Please wait while special roles perform their night actions.</p>
            <?php endif; ?>
            
            <?php if ($isStoryteller): ?>
                <div class="mt-6 border-t border-indigo-200 pt-4">
                    <h4 class="text-indigo-800 font-bold mb-2">Storyteller View</h4>
                    <div class="overflow-auto max-h-64">
                        <p class="text-lg mb-2">Waiting for actions from:</p>
                        <ul class="list-disc list-inside text-base space-y-1">
                            <?php foreach ($game['actionsNeeded'] as $pendingPlayerId): ?>
                                <li class="py-1">
                                    <?php 
                                    echo htmlspecialchars($game['players'][$pendingPlayerId]['name']); 
                                    echo ' ('; 
                                    echo ucfirst($game['players'][$pendingPlayerId]['role']);
                                    echo ')';
                                    ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <!-- DAY RESULTS PHASE -->
    <?php elseif ($phase === 'day_results'): ?>
        <div class="mb-6 p-4 bg-yellow-100 rounded-lg">
            <h3 class="text-lg font-bold mb-3 text-yellow-800">Night Results</h3>
            
            <?php
            $nightResult = $game['nightResults'][$game['currentNight']-1] ?? null;
            $killedPlayerId = $nightResult['killed'] ?? null;
            ?>
            
            <?php if ($isStoryteller): ?>
                <div class="mb-4 font-semibold text-center">
                    <p class="text-lg">Storyteller: It's time to announce what happened during the night...</p>
                </div>
            <?php endif; ?>
            
            <?php if ($killedPlayerId): ?>
                <div class="p-4 bg-red-100 rounded-lg mb-4 text-center">
                    <p class="text-lg">During the night, <strong><?php echo htmlspecialchars(getPlayerName($game['players'], $killedPlayerId)); ?></strong> was killed by the mafia.</p>
                </div>
            <?php else: ?>
                <div class="p-4 bg-green-100 rounded-lg mb-4 text-center">
                    <p class="text-lg">The night passed peacefully. No one was killed.</p>
                </div>
            <?php endif; ?>
            
            <?php
            // Show detective results only to the detective
            if ($currentPlayer['role'] === 'detective' && isAlive($currentPlayer)):
                $investigation = $nightResult['investigation'] ?? null;
                if ($investigation && $investigation['playerId']):
            ?>
                <div class="p-4 bg-blue-100 rounded-lg mt-4 text-center">
                    <p class="text-lg">Your investigation revealed that <strong><?php echo htmlspecialchars(getPlayerName($game['players'], $investigation['playerId'])); ?></strong> is 
                    <strong><?php echo $investigation['result'] === 'mafia' ? 'a member of the mafia' : 'not a member of the mafia'; ?></strong>.</p>
                </div>
            <?php 
                endif;
            endif; 
            ?>
            
            <?php if (isAlive($currentPlayer) || $isHost): ?>
                <div class="mt-5">
                    <form action="index.php?action=start_vote" method="post">
                        <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                        <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-4 px-4 rounded-lg text-lg">
                            Proceed to Discussion & Voting
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    <!-- DAY VOTING PHASE -->
    <?php elseif ($phase === 'day_vote'): ?>
        <div class="mb-6 p-4 bg-yellow-100 rounded-lg">
            <h3 class="text-lg font-bold mb-3 text-yellow-800">Day Voting</h3>
            
            <?php if (!isAlive($currentPlayer)): ?>
                <p class="text-center py-3 text-lg">You are dead and cannot vote.</p>
            <?php elseif ($currentPlayer['votedFor'] === null): ?>
                <p class="mb-4 text-lg text-center">Vote for a player you suspect is the Mafia:</p>
                
                <form id="voteForm" class="space-y-3">
                    <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                    
                    <div class="space-y-2">
                        <?php foreach ($alivePlayers as $playerId => $player): ?>
                            <?php if ($playerId === $currentPlayerId): continue; endif; ?>
                            <label for="vote_<?php echo $playerId; ?>" class="block p-3 border rounded-lg cursor-pointer mb-2 hover:bg-yellow-50 border-gray-300">
                                <div class="flex items-center">
                                    <input type="radio" id="vote_<?php echo $playerId; ?>" name="targetId" value="<?php echo $playerId; ?>" 
                                           class="mr-3 h-5 w-5" required>
                                    <span class="text-lg"><?php echo htmlspecialchars($player['name']); ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-5">
                        <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-4 px-4 rounded-lg text-lg">
                            Submit Vote
                            <?php if ($isSheriff): ?>
                                (Sheriff - Counts as 2)
                            <?php endif; ?>
                        </button>
                    </div>
                </form>
                
                <div id="voteMessage" class="mt-4 hidden"></div>
                
            <?php else: ?>
                <p class="text-yellow-700 text-center py-3 text-lg">
                    You've voted for <?php echo htmlspecialchars(getPlayerName($game['players'], $currentPlayer['votedFor'])); ?>. 
                    Please wait for others to vote.
                </p>
            <?php endif; ?>
            
            <div class="mt-6">
                <h4 class="font-bold mb-3 text-center">Current Votes</h4>
                <div class="overflow-x-auto">
                    <table class="w-full bg-white rounded-lg overflow-hidden">
                        <thead class="bg-yellow-50">
                            <tr>
                                <th class="py-3 px-4 border-b border-gray-200 text-left text-sm font-semibold">Player</th>
                                <th class="py-3 px-4 border-b border-gray-200 text-left text-sm font-semibold">Voted For</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alivePlayers as $playerId => $player): ?>
                                <tr>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <div class="flex items-center">
                                            <span class="font-medium">
                                                <?php echo htmlspecialchars($player['name']); ?>
                                            </span>
                                            <?php if ($playerId === $currentPlayerId): ?> 
                                                <span class="ml-1 text-xs font-semibold text-blue-600">(You)</span> 
                                            <?php endif; ?>
                                            <?php if ($playerId === $game['hostPlayerId']): ?> 
                                                <span class="ml-1 text-xs font-semibold text-red-800">(Host)</span> 
                                            <?php endif; ?>
                                            <?php if (isset($game['sheriff']) && $playerId === $game['sheriff']): ?> 
                                                <span class="sheriff-badge ml-1" title="Sheriff">👮</span> 
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php if ($player['votedFor'] !== null): ?>
                                            <?php echo htmlspecialchars(getPlayerName($game['players'], $player['votedFor'])); ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">Not voted yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    <!-- END DAY RESULTS PHASE -->
    <?php elseif ($phase === 'end_day_results'): ?>
        <div class="mb-6 p-4 bg-yellow-100 rounded-lg">
            <h3 class="text-lg font-bold mb-3 text-yellow-800 text-center">Voting Results</h3>
            
            <?php
            $voteResult = $game['voteResults'][$game['currentNight']-1] ?? null;
            $lynched = $voteResult['lynched'] ?? null;
            $tie = $voteResult['tie'] ?? false;
            ?>
            
            <?php if ($isStoryteller): ?>
                <div class="mb-4 font-semibold text-center">
                    <p class="text-lg">Storyteller: It's time to announce the results of the vote...</p>
                </div>
            <?php endif; ?>
            
            <?php if ($tie): ?>
                <div class="p-4 bg-blue-100 rounded-lg mb-4 text-center">
                    <p class="text-lg">The vote ended in a tie. No one was lynched today.</p>
                </div>
            <?php elseif ($lynched): ?>
                <div class="p-4 bg-red-100 rounded-lg mb-4 text-center">
                    <p class="text-lg">The village has decided to lynch <strong><?php echo htmlspecialchars(getPlayerName($game['players'], $lynched)); ?></strong>.</p>
                    <p class="mt-2 text-lg">This player was a <strong><?php echo ucfirst($game['players'][$lynched]['role']); ?></strong>.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($voteResult && isset($voteResult['votes'])): ?>
                <div class="mt-6">
                    <h4 class="font-bold mb-3 text-center">Vote Tally</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full bg-white rounded-lg overflow-hidden">
                            <thead class="bg-yellow-50">
                                <tr>
                                    <th class="py-3 px-4 border-b border-gray-200 text-left text-sm font-semibold">Player</th>
                                    <th class="py-3 px-4 border-b border-gray-200 text-left text-sm font-semibold">Votes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Sort players by vote count in descending order
                                $voteCounts = $voteResult['votes'];
                                arsort($voteCounts);
                                
                                foreach ($voteCounts as $targetId => $count): 
                                ?>
                                    <tr class="<?php echo ($lynched && $targetId === $lynched) ? 'bg-red-50' : ''; ?>">
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <div class="flex items-center">
                                                <span class="font-medium">
                                                    <?php echo htmlspecialchars(getPlayerName($game['players'], $targetId)); ?>
                                                </span>
                                                <?php if ($targetId === $currentPlayerId): ?> 
                                                    <span class="ml-1 text-xs font-semibold text-blue-600">(You)</span> 
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200 font-bold text-lg">
                                            <?php echo $count; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isAlive($currentPlayer) || $isHost): ?>
                <div class="mt-5">
                    <form action="index.php?action=proceed_to_night" method="post">
                        <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-4 rounded-lg text-lg">
                            Proceed to Night
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
    <!-- GAME OVER PHASE -->
    <?php elseif ($phase === 'end'): ?>
        <div class="mb-6 p-4 <?php echo $game['winner'] === 'mafia' ? 'bg-red-100' : 'bg-green-100'; ?> rounded-lg">
            <h3 class="text-lg font-bold mb-3 text-center">Game Over</h3>
            
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold mb-3">
                    <?php if ($game['winner'] === 'mafia'): ?>
                        <span class="text-red-600">The Mafia Wins!</span>
                    <?php else: ?>
                        <span class="text-green-600">The Villagers Win!</span>
                    <?php endif; ?>
                </h2>
                <p class="text-lg">
                    <?php if ($game['winner'] === 'mafia'): ?>
                        The mafia has taken control of the village.
                    <?php else: ?>
                        All members of the mafia have been eliminated!
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="mt-6">
                <h4 class="font-bold mb-3 text-center">Player Roles</h4>
                <div class="overflow-x-auto">
                    <table class="w-full bg-white rounded-lg overflow-hidden">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 border-b border-gray-200 text-left text-sm font-semibold">Player</th>
                                <th class="py-3 px-4 border-b border-gray-200 text-left text-sm font-semibold">Role</th>
                                <th class="py-3 px-4 border-b border-gray-200 text-left text-sm font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($game['players'] as $playerId => $player): ?>
                                <tr>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <div class="flex items-center">
                                            <span class="font-medium">
                                                <?php echo htmlspecialchars($player['name']); ?>
                                            </span>
                                            <?php if ($playerId === $currentPlayerId): ?> 
                                                <span class="ml-1 text-xs font-semibold text-blue-600">(You)</span> 
                                            <?php endif; ?>
                                            <?php if ($playerId === $game['hostPlayerId']): ?> 
                                                <span class="ml-1 text-xs font-semibold text-red-800">(Host)</span> 
                                            <?php endif; ?>
                                            <?php if (isset($game['sheriff']) && $playerId === $game['sheriff']): ?> 
                                                <span class="sheriff-badge ml-1" title="Sheriff">👮</span> 
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php echo getRoleDisplayName($player['role']); ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200 font-semibold <?php echo $player['status'] === 'alive' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo ucfirst($player['status']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if ($isHost): ?>
                <div class="mt-6 flex flex-col space-y-3">
                    <form action="index.php?action=reset_game" method="post">
                        <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-4 rounded-lg text-lg">
                            Play Again with Same Players
                        </button>
                    </form>
                    
                    <form action="index.php?action=new_game" method="post">
                        <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-4 rounded-lg text-lg">
                            Start New Game
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Player list (always shown) -->
    <div class="mb-6">
        <h3 class="text-lg font-bold mb-3 text-red-900">Players</h3>
        <div class="overflow-x-auto">
            <?php foreach ($game['players'] as $playerId => $playerData): ?>
                <div class="player-card <?php echo $playerData['status'] === 'dead' ? 'opacity-50' : ''; ?> <?php echo $playerId === $currentPlayerId ? 'border-2 border-blue-500' : ''; ?> mb-3 p-3 bg-white rounded-lg shadow flex items-center">
                    <!-- Player photo or initials -->
                    <?php if (!empty($playerData['photoPath'])): ?>
                        <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 mr-3 flex-shrink-0">
                            <img src="<?php echo $playerData['photoPath']; ?>" alt="Profile" class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full flex items-center justify-center bg-blue-600 text-white font-bold mr-3 flex-shrink-0">
                            <?php 
                            $initials = '';
                            $nameParts = explode(' ', $playerData['name']);
                            foreach ($nameParts as $part) {
                                if (!empty($part)) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                    if (strlen($initials) >= 2) break;
                                }
                            }
                            echo htmlspecialchars(strlen($initials) > 0 ? $initials : substr($playerData['name'], 0, 2));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex-grow">
                        <div class="flex justify-between items-center">
                            <span class="font-medium <?php echo $playerId === $game['hostPlayerId'] ? 'text-red-600' : ''; ?>">
                                <?php echo htmlspecialchars($playerData['name']); ?>
                                <?php if ($playerId === $game['hostPlayerId']): ?>
                                    <span class="text-xs">(Host)</span>
                                <?php endif; ?>
                            </span>
                            
                            <?php if ($playerData['status'] === 'dead'): ?>
                                <span class="text-xs font-medium text-red-600">Dead</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($phase === 'end'): ?>
                            <div class="text-xs text-gray-700">
                                <?php echo ucfirst($playerData['role'] ?? 'Unknown'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($isHost && $playerData['status'] === 'alive' && $playerId !== $currentPlayerId): ?>
                        <form action="index.php?action=remove_player" method="post" class="ml-2" onsubmit="return confirm('Are you sure you want to remove this player from the game?');">
                            <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                            <input type="hidden" name="playerId" value="<?php echo $playerId; ?>">
                            <button type="submit" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                Remove
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="text-center mt-6">
        <a href="index.php?action=leave_game" class="inline-block text-red-600 hover:text-red-800 hover:underline text-base py-3 px-6 border border-transparent rounded-lg">Leave Game</a>
    </div>
</div>

<!-- JavaScript for AJAX actions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile optimized radio button behavior (highlight selected items)
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // First remove highlight from all labels
            this.closest('form').querySelectorAll('label').forEach(label => {
                label.classList.remove('border-indigo-500', 'border-yellow-500', 'bg-indigo-50', 'bg-yellow-50');
                label.classList.add('border-gray-300');
            });
            
            // Then highlight the selected one
            if (this.checked) {
                const parentLabel = this.closest('label');
                if (this.name === 'targetId' && this.form.id === 'nightActionForm') {
                    parentLabel.classList.add('border-indigo-500', 'bg-indigo-50');
                    parentLabel.classList.remove('border-gray-300');
                } else if (this.name === 'targetId' && this.form.id === 'voteForm') {
                    parentLabel.classList.add('border-yellow-500', 'bg-yellow-50');
                    parentLabel.classList.remove('border-gray-300');
                }
            }
        });
    });
    
    // Night action form submission
    const nightActionForm = document.getElementById('nightActionForm');
    if (nightActionForm) {
        nightActionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(nightActionForm);
            
            fetch('index.php?action=submit_action', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const actionMessage = document.getElementById('actionMessage');
                    actionMessage.innerHTML = '<div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg text-center">Action submitted successfully! Please wait for others.</div>';
                    actionMessage.classList.remove('hidden');
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    const actionMessage = document.getElementById('actionMessage');
                    actionMessage.innerHTML = `<div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg text-center">Error: ${data.error}</div>`;
                    actionMessage.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error submitting night action:', error);
            });
        });
    }
    
    // Vote form submission
    const voteForm = document.getElementById('voteForm');
    if (voteForm) {
        voteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(voteForm);
            
            fetch('index.php?action=submit_vote', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const voteMessage = document.getElementById('voteMessage');
                    voteMessage.innerHTML = '<div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg text-center">Vote submitted successfully!</div>';
                    voteMessage.classList.remove('hidden');
                    
                    // Reload the page after voting or if all have voted
                    setTimeout(() => {
                        window.location.reload();
                    }, data.allVoted ? 1500 : 1000);
                } else {
                    const voteMessage = document.getElementById('voteMessage');
                    voteMessage.innerHTML = `<div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg text-center">Error: ${data.error}</div>`;
                    voteMessage.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error submitting vote:', error);
            });
        });
    }
    
    // Auto-refresh game state
    function pollGameState() {
        fetch('index.php?action=lobby_status&code=<?php echo $gameCode; ?>')
            .then(response => response.json())
            .then(data => {
                // If phase changed, reload the page
                if (data.phase !== '<?php echo $phase; ?>') {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error polling game state:', error);
            });
    }
    
    // Poll every 3 seconds
    setInterval(pollGameState, 3000);
});
</script>