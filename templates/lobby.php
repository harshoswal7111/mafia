<?php require_once 'templates/layout.php'; ?>

<?php
// Get current player ID
$currentPlayerId = $_SESSION['playerId'] ?? '';
$isHost = ($currentPlayerId === $game['hostPlayerId']);
$gameCode = $game['gameCode'];
$playerCount = count($game['players']);
?>

<div class="bg-white p-5 rounded-lg shadow-md mb-6">
    <div class="flex flex-col justify-center items-center mb-4">
        <h2 class="text-2xl font-bold text-red-900 text-center">Game Lobby</h2>
        <div class="mt-3 px-4 py-2 bg-gray-200 rounded-md text-center w-full">
            <div class="font-bold text-sm">GAME CODE:</div>
            <div class="text-2xl tracking-widest font-bold"><?php echo $gameCode; ?></div>
        </div>
    </div>

    <div class="mb-5">
        <p class="text-gray-700 text-center">Share this code with others to join your game. You need at least 
        <?php echo ($game['settings']['mafiaCount'] + 3); ?> players to start 
        (<?php echo $game['settings']['mafiaCount']; ?> mafia + doctor + detective + at least 1 villager).</p>
    </div>

    <!-- Game settings (host only) -->
    <?php if ($isHost): ?>
        <div class="mb-6 p-4 bg-gray-100 rounded-md">
            <h3 class="text-lg font-bold mb-3 text-red-900">Game Settings</h3>
            <form id="settingsForm" action="index.php?action=update_settings" method="post">
                <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                
                <div class="mb-4">
                    <label class="flex items-center space-x-3 py-2">
                        <input type="checkbox" name="storytellerMode" value="1" 
                               <?php echo $game['settings']['storytellerMode'] ? 'checked' : ''; ?>
                               class="form-checkbox h-6 w-6 text-red-900">
                        <span class="text-base">Storyteller Mode</span>
                    </label>
                    <p class="text-sm text-gray-600 mt-1 ml-9">The host narrates events for added immersion.</p>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center space-x-3 py-2">
                        <input type="checkbox" name="sheriffMode" value="1" 
                               <?php echo $game['settings']['sheriffMode'] ? 'checked' : ''; ?>
                               class="form-checkbox h-6 w-6 text-red-900">
                        <span class="text-base">Sheriff Mode</span>
                    </label>
                    <p class="text-sm text-gray-600 mt-1 ml-9">One random villager becomes the sheriff whose vote counts twice.</p>
                </div>
                
                <div class="mb-4">
                    <label class="block mb-2 text-base" for="mafiaCount">Number of Mafia:</label>
                    <div class="flex items-center">
                        <input type="number" id="mafiaCount" name="mafiaCount" 
                               value="<?php echo $game['settings']['mafiaCount']; ?>" 
                               min="1" max="<?php echo max(1, floor($playerCount / 2) - 1); ?>" 
                               class="form-input w-20 border rounded py-2 px-3 text-lg text-center">
                        <p class="text-sm text-gray-600 ml-3">Recommended: About 1/4 to 1/3 of players.</p>
                    </div>
                </div>
                
                <button type="submit" class="mt-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full">
                    Update Settings
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Player list -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Players</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php foreach ($game['players'] as $playerId => $player): ?>
            <div class="flex items-center justify-between mb-4 bg-white p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <!-- Display player photo or initials -->
                    <?php if (!empty($player['photoPath'])): ?>
                        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 mr-3">
                            <img src="<?php echo $player['photoPath']; ?>" alt="Profile" class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-600 text-white font-bold mr-3">
                            <?php 
                            $initials = '';
                            $nameParts = explode(' ', $player['name']);
                            foreach ($nameParts as $part) {
                                if (!empty($part)) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                    if (strlen($initials) >= 2) break;
                                }
                            }
                            echo htmlspecialchars(strlen($initials) > 0 ? $initials : substr($player['name'], 0, 2));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <span class="font-medium <?= $playerId === $game['hostPlayerId'] ? 'text-red-600' : '' ?>">
                        <?= htmlspecialchars($player['name']) ?>
                        <?php if ($playerId === $game['hostPlayerId']): ?>
                            <span class="text-xs">(Host)</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($isHost && $playerId !== $currentPlayerId): ?>
                    <form action="index.php" method="get" class="ml-4">
                        <input type="hidden" name="action" value="remove_player">
                        <input type="hidden" name="code" value="<?= $game['gameCode'] ?>">
                        <input type="hidden" name="playerId" value="<?= $playerId ?>">
                        <button type="submit" class="text-gray-500 hover:text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Start game button (host only) -->
    <?php if ($isHost): ?>
        <form action="index.php?action=start_game" method="post" class="mb-4">
            <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
            <button type="submit" class="w-full bg-red-900 hover:bg-red-800 text-white font-bold py-4 px-4 rounded-lg text-lg">
                Start Game
            </button>
        </form>
    <?php else: ?>
        <div class="text-center p-4 bg-gray-100 rounded-lg mb-4">
            <p class="text-lg">Waiting for host to start the game...</p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-6">
        <a href="index.php?action=leave_game" class="inline-block text-red-600 hover:text-red-800 hover:underline text-base py-2 px-4 border border-transparent rounded-lg">Leave Game</a>
    </div>
</div>

<!-- JavaScript for AJAX updates -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Settings form submission (if host)
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(settingsForm);
            
            fetch('index.php?action=update_settings', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // No need to do anything if successful
                if (!data.success) {
                    console.error('Failed to update settings:', data.error);
                }
            })
            .catch(error => {
                console.error('Error updating settings:', error);
            });
        });
    }
    
    // Polling for lobby updates
    function pollLobbyStatus() {
        fetch('index.php?action=lobby_status&code=<?php echo $gameCode; ?>')
            .then(response => response.json())
            .then(data => {
                // If the game has started, navigate to the game page
                if (data.phase !== 'lobby') {
                    window.location.href = 'index.php?action=game&code=<?php echo $gameCode; ?>';
                    return;
                }
                
                // Otherwise update the player list
                const playerListContainer = document.getElementById('playerList');
                if (playerListContainer) {
                    let playerListHTML = '';
                    
                    for (const [playerId, player] of Object.entries(data.players)) {
                        const isHost = (playerId === '<?php echo $game['hostPlayerId']; ?>');
                        const isCurrentPlayer = (playerId === '<?php echo $currentPlayerId; ?>');
                        
                        playerListHTML += `
                            <div class="py-3 px-4 mb-2 rounded ${isHost ? 'bg-red-100' : 'bg-white'} shadow-sm flex justify-between items-center">
                                <div>
                                    ${player.name}
                                    ${isHost ? '<span class="ml-2 text-sm font-semibold text-red-800">(Host)</span>' : ''}
                                    ${isCurrentPlayer ? '<span class="ml-2 text-sm font-semibold text-blue-600">(You)</span>' : ''}
                                </div>
                                ${isHost && !isCurrentPlayer ? `
                                    <form action="index.php?action=remove_player" method="post" class="inline" onsubmit="return confirm('Are you sure you want to remove this player?');">
                                        <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                                        <input type="hidden" name="playerId" value="${playerId}">
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                            Remove
                                        </button>
                                    </form>
                                ` : ''}
                            </div>
                        `;
                    }
                    
                    playerListContainer.innerHTML = playerListHTML;
                }
            })
            .catch(error => {
                console.error('Error polling lobby status:', error);
            });
    }
    
    // Poll more frequently (every 1.5 seconds) to ensure game start is detected quickly
    setInterval(pollLobbyStatus, 1500);
    
    // Run immediately to reduce initial waiting time
    pollLobbyStatus();
});
</script>