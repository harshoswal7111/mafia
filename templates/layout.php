<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, maximum-scale=1.0, user-scalable=no">
    <title>Mafia Game Facilitator</title>
    <!-- Favicon -->
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/1940/1940953.png" type="image/png">
    <!-- Include Tailwind CSS from CDN for simplicity -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-tap-highlight-color: transparent; /* Remove tap highlight on mobile */
            touch-action: manipulation; /* Faster touch events */
            overscroll-behavior: contain; /* Prevent pull-to-refresh */
        }
        .container {
            flex-grow: 1;
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }
        .role-mafia { color: #ef4444; }
        .role-doctor { color: #3b82f6; }
        .role-detective { color: #eab308; }
        .role-villager { color: #22c55e; }
        .sheriff-badge {
            display: inline-block;
            color: #eab308;
            margin-left: 0.25rem;
        }
        /* Make buttons larger on mobile for easier tapping */
        button, .btn {
            min-height: 44px; /* Minimum tap target size */
        }
        /* Increase font size for better readability on small screens */
        @media (max-width: 640px) {
            body {
                font-size: 16px;
            }
            input, select {
                font-size: 16px; /* Prevent zoom on focus in iOS */
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-red-900 text-white p-4 shadow-md sticky top-0 z-10">
        <div class="container mx-auto">
            <h1 class="text-xl md:text-2xl font-bold text-center">Mafia Game Facilitator</h1>
        </div>
    </header>

    <div class="container mx-auto p-4 pb-16"> <!-- Added padding at bottom for mobile navigation space -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php 
        // Output the main content that was captured in the buffer
        echo $content; 
        ?>
        
    </div>

    <footer class="bg-gray-800 text-white text-center p-4 mt-auto text-sm">
        <p>&copy; <?php echo date('Y'); ?> Harsh Oswal</p>
    </footer>

    <?php if (isset($gameCode)): ?>
    <script>
    // Auto-refresh functionality
    let lastGameState = '';
    let refreshInterval = 3000; // Poll every 3 seconds
    let refreshTimer;
    let consecutiveErrors = 0;
    let maxErrors = 5;

    function pollGameState() {
        const gameCode = '<?php echo $gameCode ?? ''; ?>';
        if (!gameCode) return;
        
        fetch(`index.php?action=get_game_state&gameCode=${gameCode}&timestamp=${Date.now()}`, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            consecutiveErrors = 0; // Reset error counter on success
            return response.json();
        })
        .then(data => {
            if (!data) return;
            
            // Check if game state has changed by comparing a stringified version of the data
            const gameStateString = JSON.stringify({
                phase: data.phase,
                currentNight: data.currentNight,
                actionsNeeded: data.actionsNeeded,
                players: data.players
            });
            
            if (gameStateString !== lastGameState) {
                lastGameState = gameStateString;
                console.log('Game state changed, refreshing...');
                window.location.reload();
                return;
            }
            
            // Check for specific changes that should trigger a refresh
            if (window.location.href.includes('game') && data) {
                let shouldRefresh = false;
                
                // Check if we're on a night phase and all actions are submitted
                if (data.phase === 'night' && data.actionsNeeded && data.actionsNeeded.length === 0) {
                    shouldRefresh = true;
                }
                
                // Check if we're on a day voting phase and everyone has voted
                if (data.phase === 'day_vote') {
                    const alivePlayers = Object.values(data.players).filter(player => player.status === 'alive');
                    const votedPlayers = alivePlayers.filter(player => player.votedFor !== null);
                    
                    if (alivePlayers.length === votedPlayers.length) {
                        shouldRefresh = true;
                    }
                }
                
                if (shouldRefresh) {
                    console.log('Game state condition met for refresh');
                    window.location.reload();
                    return;
                }
            }
        })
        .catch(error => {
            console.error('Error polling game state:', error);
            consecutiveErrors++;
            
            // If we have too many consecutive errors, slow down the polling to reduce load
            if (consecutiveErrors > maxErrors) {
                clearInterval(refreshTimer);
                refreshInterval = 10000; // Slow down to 10 seconds
                refreshTimer = setInterval(pollGameState, refreshInterval);
                console.log('Too many errors, slowing down refresh rate');
            }
        });
    }

    // Start polling when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Start the polling timer
        refreshTimer = setInterval(pollGameState, refreshInterval);
        
        // Also poll immediately
        pollGameState();
        
        // Pause polling when tab is not visible to save resources
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                clearInterval(refreshTimer);
            } else {
                // Resume polling and do an immediate check when returning to the page
                refreshTimer = setInterval(pollGameState, refreshInterval);
                pollGameState();
            }
        });
    });
    </script>
    <?php endif; ?>
</body>
</html>