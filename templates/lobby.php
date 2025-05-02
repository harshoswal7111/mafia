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
    <div class="mb-6">
        <h3 class="text-lg font-bold mb-3 text-red-900">Players (<?php echo $playerCount; ?>)</h3>
        <div id="playerList" class="bg-gray-50 border rounded-md p-2">
            <?php foreach ($game['players'] as $playerId => $player): ?>
                <div class="py-3 px-4 mb-2 rounded <?php echo ($playerId === $game['hostPlayerId']) ? 'bg-red-100' : 'bg-white'; ?> shadow-sm">
                    <?php echo htmlspecialchars($player['name']); ?>
                    <?php if ($playerId === $game['hostPlayerId']): ?>
                        <span class="ml-2 text-sm font-semibold text-red-800">(Host)</span>
                    <?php endif; ?>
                    <?php if ($playerId === $currentPlayerId): ?>
                        <span class="ml-2 text-sm font-semibold text-blue-600">(You)</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Game settings display (for non-host players) -->
    <?php if (!$isHost): ?>
        <div class="mb-6">
            <h3 class="text-lg font-bold mb-3 text-red-900">Game Settings</h3>
            <ul class="list-none bg-gray-50 p-4 rounded-md">
                <li class="py-2 border-b border-gray-200">
                    <div class="flex justify-between">
                        <strong>Storyteller Mode:</strong>
                        <span><?php echo $game['settings']['storytellerMode'] ? 'Enabled' : 'Disabled'; ?></span>
                    </div>
                </li>
                <li class="py-2 border-b border-gray-200">
                    <div class="flex justify-between">
                        <strong>Sheriff Mode:</strong> 
                        <span><?php echo $game['settings']['sheriffMode'] ? 'Enabled' : 'Disabled'; ?></span>
                    </div>
                </li>
                <li class="py-2">
                    <div class="flex justify-between">
                        <strong>Number of Mafia:</strong> 
                        <span><?php echo $game['settings']['mafiaCount']; ?></span>
                    </div>
                </li>
            </ul>
        </div>
    <?php endif; ?>

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
                // If we're in a different phase, reload the page
                if (data.phase !== 'lobby') {
                    window.location.reload();
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
                            <div class="py-3 px-4 mb-2 rounded ${isHost ? 'bg-red-100' : 'bg-white'} shadow-sm">
                                ${player.name}
                                ${isHost ? '<span class="ml-2 text-sm font-semibold text-red-800">(Host)</span>' : ''}
                                ${isCurrentPlayer ? '<span class="ml-2 text-sm font-semibold text-blue-600">(You)</span>' : ''}
                            </div>
                        `;
                    }
                    
                    playerListContainer.innerHTML = playerListHTML;
                }
                
                // Update settings display for non-host players
                if (!<?php echo $isHost ? 'true' : 'false'; ?>) {
                    const storytellerMode = data.settings.storytellerMode ? 'Enabled' : 'Disabled';
                    const sheriffMode = data.settings.sheriffMode ? 'Enabled' : 'Disabled';
                    const mafiaCount = data.settings.mafiaCount;
                    
                    const settingsElements = document.querySelectorAll('ul.list-none li');
                    if (settingsElements.length >= 3) {
                        settingsElements[0].querySelector('span').textContent = storytellerMode;
                        settingsElements[1].querySelector('span').textContent = sheriffMode;
                        settingsElements[2].querySelector('span').textContent = mafiaCount;
                    }
                }
            })
            .catch(error => {
                console.error('Error polling lobby status:', error);
            });
    }
    
    // Poll every 3 seconds
    setInterval(pollLobbyStatus, 3000);
});
</script>