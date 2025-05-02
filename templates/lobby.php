<?php
// Get current player ID
$currentPlayerId = $_SESSION['playerId'] ?? '';
$isHost = ($currentPlayerId === $game['hostPlayerId']);
$gameCode = $game['gameCode'];
$playerCount = count($game['players']);
?>

<div class="bg-white p-8 rounded-lg shadow-lg max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Game Lobby: <?php echo $gameCode; ?></h1>
    
    <!-- Success/Error Messages -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Host Controls -->
    <?php if ($game['hostPlayerId'] === ($_SESSION['playerId'] ?? '')): ?>
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <h2 class="text-2xl font-bold mb-2">Host Controls</h2>
            
            <div class="mb-4">
                <p class="font-semibold mb-2">Game Settings:</p>
                <form id="settingsForm">
                    <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                    
                    <div class="flex flex-wrap mb-2">
                        <div class="w-full md:w-1/2 mb-2 md:mb-0">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="storytellerMode" class="form-checkbox" <?php echo $game['settings']['storytellerMode'] ? 'checked' : ''; ?>>
                                <span class="ml-2">Storyteller Mode</span>
                            </label>
                            <p class="text-sm text-gray-600">Host narrates the game story</p>
                        </div>
                        
                        <div class="w-full md:w-1/2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="sheriffMode" class="form-checkbox" <?php echo $game['settings']['sheriffMode'] ? 'checked' : ''; ?>>
                                <span class="ml-2">Sheriff Mode</span>
                            </label>
                            <p class="text-sm text-gray-600">Players can elect a sheriff</p>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="block text-gray-700">
                            Mafia Count:
                            <select name="mafiaCount" class="mt-1 form-select block w-full">
                                <?php 
                                $playerCount = count($game['players']);
                                $maxMafia = max(1, floor(($playerCount - 2) / 2)); // Maximum mafia is half of non-essential roles
                                
                                for ($i = 1; $i <= $maxMafia; $i++): 
                                ?>
                                    <option value="<?php echo $i; ?>" <?php echo $game['settings']['mafiaCount'] == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>
                    
                    <div class="text-right">
                        <button type="button" id="updateSettingsBtn" class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded">
                            Update Settings
                        </button>
                    </div>
                </form>
            </div>
            
            <form action="index.php?action=start_game" method="post">
                <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded w-full">
                    Start Game
                </button>
            </form>
        </div>
    <?php endif; ?>
    
    <!-- Game Info -->
    <div class="mb-6">
        <p class="mb-2"><strong>Game Code:</strong> <span class="text-xl font-mono bg-gray-100 px-2 py-1 rounded"><?php echo $gameCode; ?></span></p>
        <p class="mb-2"><strong>Your Role:</strong> <?php echo $game['hostPlayerId'] === ($_SESSION['playerId'] ?? '') ? 'Host' : 'Player'; ?></p>
    </div>
    
    <!-- Players List -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-2">Players (<?php echo count($game['players']); ?>)</h2>
        <div id="playersList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($game['players'] as $player): ?>
                <div class="bg-gray-50 p-3 rounded-lg flex flex-col items-center">
                    <?php if (!empty($player['photoPath'])): ?>
                        <img src="<?php echo $player['photoPath']; ?>" alt="<?php echo htmlspecialchars($player['name']); ?>" class="w-16 h-16 object-cover rounded-full mb-2">
                    <?php else: ?>
                        <div class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center mb-2">
                            <span class="text-gray-600 text-2xl"><?php echo substr($player['name'], 0, 1); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <p class="font-semibold text-center">
                        <?php echo htmlspecialchars($player['name']); ?>
                        <?php if ($player['id'] === $game['hostPlayerId']): ?>
                            <span class="text-xs bg-blue-200 text-blue-800 px-1 py-0.5 rounded">Host</span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($game['hostPlayerId'] === ($_SESSION['playerId'] ?? '') && $player['id'] !== $game['hostPlayerId']): ?>
                        <form action="index.php?action=remove_player" method="post" class="mt-1">
                            <input type="hidden" name="gameCode" value="<?php echo $gameCode; ?>">
                            <input type="hidden" name="playerId" value="<?php echo $player['id']; ?>">
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">Remove</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Leave Game Button -->
    <div class="mt-8">
        <a href="index.php?action=leave_game" class="text-gray-500 hover:text-gray-700">Leave Game</a>
    </div>

    <!-- Lobby JavaScript for auto-refresh -->
    <script>
        // Function to refresh player list via AJAX
        function refreshLobby() {
            const gameCode = '<?php echo $gameCode; ?>';
            
            fetch(`index.php?action=get_lobby_status&gameCode=${gameCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update player count
                        const playerCountElements = document.querySelectorAll('h2:contains("Players")');
                        if (playerCountElements.length > 0) {
                            playerCountElements[0].textContent = `Players (${data.players.length})`;
                        }
                        
                        // Update players list
                        const playersListElement = document.getElementById('playersList');
                        if (playersListElement) {
                            let playersHTML = '';
                            
                            data.players.forEach(player => {
                                let photoHTML = '';
                                if (player.photoPath) {
                                    photoHTML = `<img src="${player.photoPath}" alt="${player.name}" class="w-16 h-16 object-cover rounded-full mb-2">`;
                                } else {
                                    photoHTML = `
                                        <div class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center mb-2">
                                            <span class="text-gray-600 text-2xl">${player.name.substring(0, 1)}</span>
                                        </div>
                                    `;
                                }
                                
                                let hostBadge = player.id === data.hostPlayerId ? 
                                    '<span class="text-xs bg-blue-200 text-blue-800 px-1 py-0.5 rounded">Host</span>' : '';
                                
                                let removeButton = '';
                                const currentPlayerId = '<?php echo $currentPlayerId; ?>';
                                if (currentPlayerId === data.hostPlayerId && player.id !== data.hostPlayerId) {
                                    removeButton = `
                                        <form action="index.php?action=remove_player" method="post" class="mt-1">
                                            <input type="hidden" name="gameCode" value="${gameCode}">
                                            <input type="hidden" name="playerId" value="${player.id}">
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">Remove</button>
                                        </form>
                                    `;
                                }
                                
                                playersHTML += `
                                    <div class="bg-gray-50 p-3 rounded-lg flex flex-col items-center">
                                        ${photoHTML}
                                        <p class="font-semibold text-center">
                                            ${player.name}
                                            ${hostBadge}
                                        </p>
                                        ${removeButton}
                                    </div>
                                `;
                            });
                            
                            playersListElement.innerHTML = playersHTML;
                        }
                        
                        // If game has started, redirect to game page
                        if (data.gameStarted) {
                            window.location.href = `index.php?action=game&gameCode=${gameCode}`;
                        }
                    } else {
                        console.error("Failed to refresh lobby:", data.message);
                    }
                })
                .catch(error => {
                    console.error("Error refreshing lobby:", error);
                });
        }
        
        // Auto-refresh functionality
        let refreshInterval = null;
        const refreshRate = 5000; // Refresh every 5 seconds
        
        function startAutoRefresh() {
            if (!refreshInterval) {
                refreshInterval = setInterval(refreshLobby, refreshRate);
                document.getElementById('toggleRefresh').textContent = 'Pause Auto-Refresh';
                document.getElementById('refreshStatus').textContent = 'Auto-refresh is active';
            }
        }
        
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
                document.getElementById('toggleRefresh').textContent = 'Start Auto-Refresh';
                document.getElementById('refreshStatus').textContent = 'Auto-refresh is paused';
            }
        }
        
        function toggleAutoRefresh() {
            if (refreshInterval) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        }
        
        // Add refresh controls to the page
        document.addEventListener('DOMContentLoaded', function() {
            const controlsDiv = document.createElement('div');
            controlsDiv.className = 'mt-4 mb-2 flex items-center justify-between';
            controlsDiv.innerHTML = `
                <span id="refreshStatus" class="text-sm text-gray-600">Auto-refresh is active</span>
                <div>
                    <button id="toggleRefresh" class="text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded mr-2">
                        Pause Auto-Refresh
                    </button>
                    <button id="manualRefresh" class="text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded">
                        Refresh Now
                    </button>
                </div>
            `;
            
            // Insert before the Leave Game button
            const leaveGameDiv = document.querySelector('.mt-8');
            leaveGameDiv.parentNode.insertBefore(controlsDiv, leaveGameDiv);
            
            // Add event listeners
            document.getElementById('toggleRefresh').addEventListener('click', toggleAutoRefresh);
            document.getElementById('manualRefresh').addEventListener('click', refreshLobby);
            
            // Start auto-refresh immediately
            startAutoRefresh();
        });
    </script>
</div>