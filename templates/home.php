<?php require_once 'templates/layout.php'; ?>

<div class="flex flex-col gap-6">
    <!-- Host a Game -->
    <form action="index.php?action=create_game" method="post" class="bg-white p-6 rounded-lg shadow-lg mb-8" enctype="multipart/form-data">
        <h2 class="text-2xl font-bold text-red-900 mb-6">Host a New Game</h2>
        
        <div class="mb-6">
            <label for="hostName" class="block text-lg font-medium text-gray-700 mb-2">Your Name</label>
            <input type="text" id="hostName" name="hostName" required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                   placeholder="Enter your name">
        </div>
        
        <div class="mb-6">
            <label for="profilePhoto" class="block text-lg font-medium text-gray-700 mb-2">Profile Photo (Optional)</label>
            <input type="file" id="profilePhoto" name="profilePhoto" accept="image/*"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
            <p class="mt-1 text-sm text-gray-500">Upload a profile photo or we'll use your initials.</p>
        </div>
        
        <button type="submit" class="w-full bg-red-900 hover:bg-red-800 text-white font-bold py-4 px-6 rounded-lg text-lg">
            Create Game
        </button>
    </form>

    <!-- Join a Game -->
    <form action="index.php?action=join_game" method="post" class="bg-white p-6 rounded-lg shadow-lg" enctype="multipart/form-data">
        <h2 class="text-2xl font-bold text-red-900 mb-6">Join Existing Game</h2>
        
        <div class="mb-6">
            <label for="gameCode" class="block text-lg font-medium text-gray-700 mb-2">Game Code</label>
            <input type="text" id="gameCode" name="gameCode" required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 uppercase tracking-widest"
                   placeholder="Enter game code" maxlength="6">
        </div>
        
        <div class="mb-6">
            <label for="playerName" class="block text-lg font-medium text-gray-700 mb-2">Your Name</label>
            <input type="text" id="playerName" name="playerName" required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                   placeholder="Enter your name">
        </div>
        
        <div class="mb-6">
            <label for="joinProfilePhoto" class="block text-lg font-medium text-gray-700 mb-2">Profile Photo (Optional)</label>
            <input type="file" id="joinProfilePhoto" name="profilePhoto" accept="image/*"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
            <p class="mt-1 text-sm text-gray-500">Upload a profile photo or we'll use your initials.</p>
        </div>
        
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg text-lg">
            Join Game
        </button>
    </form>
</div>

<div class="mt-8 bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-bold mb-4 text-red-900">How to Play</h2>
    <div class="text-gray-700">
        <p class="mb-2 text-base"><strong>Mafia</strong> is a social deduction game where players are secretly assigned roles as either villagers or mafia.</p>
        
        <h3 class="text-lg font-semibold mt-4 mb-2">Roles:</h3>
        <ul class="list-disc pl-5 mb-4">
            <li class="mb-2"><span class="font-semibold role-mafia">Mafia</span> - During the night phase, the mafia vote to eliminate one villager.</li>
            <li class="mb-2"><span class="font-semibold role-doctor">Doctor</span> - Can save one person each night (including themselves) from the mafia.</li>
            <li class="mb-2"><span class="font-semibold role-detective">Detective</span> - Can investigate one player each night to learn if they are mafia or not.</li>
            <li class="mb-2"><span class="font-semibold role-villager">Villagers</span> - Must deduce who the mafia are through discussion and voting.</li>
        </ul>
        
        <h3 class="text-lg font-semibold mt-5 mb-2">Game Flow:</h3>
        <ol class="list-decimal pl-5">
            <li class="mb-2">The game alternates between night and day phases.</li>
            <li class="mb-2">During the night, the mafia, doctor, and detective secretly perform their actions.</li>
            <li class="mb-2">During the day, players discuss who they think is mafia and vote to lynch one person.</li>
            <li class="mb-2">The game continues until either all mafia are eliminated (villagers win) or mafia equals or outnumbers villagers (mafia wins).</li>
        </ol>
        
        <h3 class="text-lg font-semibold mt-5 mb-2">Optional Rules:</h3>
        <ul class="list-disc pl-5">
            <li class="mb-2"><span class="font-semibold">Sheriff Mode</span> - One random villager is assigned as sheriff whose vote counts twice.</li>
            <li class="mb-2"><span class="font-semibold">Storyteller Mode</span> - The host narrates the game events for added immersion.</li>
        </ul>
    </div>
</div>