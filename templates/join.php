<?php $content = 'join.php'; ?>
<form method="POST" class="mt-3">
    <div class="mb-3">
        <label class="form-label fw-medium">Game Code</label>
        <input type="text" name="gameCode" required 
               class="form-control form-control-lg text-uppercase"
               placeholder="ABCDE"
               pattern="[A-Za-z]{5}">
    </div>
    <div class="mb-4">
        <label class="form-label fw-medium">Your Name</label>
        <input type="text" name="playerName" required 
               class="form-control form-control-lg"
               placeholder="Player name">
    </div>
    <button type="submit" class="btn btn-primary w-100 py-3">
        Join Game
    </button>
</form>