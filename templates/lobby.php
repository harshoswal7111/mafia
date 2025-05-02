<?php $content = 'lobby.php'; ?>
<div class="container">
    <div class="text-center mb-4">
        <h2>Game Code: <?= $gameCode ?></h2>
        <p class="text-muted">Share this code with other players</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Players (<?= count($gameData['players']) ?>)
        </div>
        <div class="card-body">
            <ul class="list-group">
                <?php foreach ($gameData['players'] as $player): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($player['name']) ?>
                    <?php if ($_SESSION['playerId'] === $gameData['hostPlayerId']): ?>
                    <button class="btn btn-sm btn-danger">Remove</button>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php if ($_SESSION['playerId'] === $gameData['hostPlayerId']): ?>
    <form method="POST" action="?action=start_game" class="text-center">
        <button type="submit" class="btn btn-success btn-lg">
            Start Game
        </button>
    </form>
    <?php else: ?>
    <div class="alert alert-info text-center">
        Waiting for host to start the game...
    </div>
    <?php endif; ?>
</div>