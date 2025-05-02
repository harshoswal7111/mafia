<?php $content = 'lobby.php'; ?>
<div class="space-y-6">
    <div class="text-center">
        <h2 class="text-xl font-semibold">Game Code: <?= $gameCode ?></h2>
        <p class="text-gray-600">Share this code with other players</p>
    </div>

    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-medium mb-2">Players (<?= count($gameData['players']) ?>)</h3>
        <ul class="space-y-2">
            <?php foreach ($gameData['players'] as $player): ?>
                <li class="flex items-center justify-between bg-white p-3 rounded shadow">
                    <span><?= htmlspecialchars($player['name']) ?></span>
                    <?php if ($_SESSION['playerId'] === $gameData['hostPlayerId']): ?>
                        <button class="text-red-500 hover:text-red-700">Remove</button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($_SESSION['playerId'] === $gameData['hostPlayerId']): ?>
        <form method="POST" action="?action=start_game" class="text-center">
            <button type="submit" 
                    class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition-colors">
                Start Game
            </button>
        </form>
    <?php else: ?>
        <p class="text-center text-gray-600">Waiting for host to start the game...</p>
    <?php endif; ?>
</div>