<?php $content = 'game.php'; ?>
<div class="container">
    <?php if ($gameData['phase'] === 'night'): ?>
    <div class="alert alert-dark text-center mb-4">
        Night Phase - Make Your Choice
    </div>
    
    <form method="POST" action="?action=submit_action" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Select Target</label>
            <select name="target" class="form-select" required>
                <?php foreach ($gameData['players'] as $id => $player): ?>
                    <?php if ($id !== $_SESSION['playerId'] && $player['status'] === 'alive'): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($player['name']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Submit Action</button>
    </form>
    
    <?php else: ?>
    <div class="alert alert-warning text-center">
        Day Phase - Discussion Time
    </div>
    <?php endif; ?>
</div>