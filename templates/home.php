<?php require_once 'templates/layout.php'; ?>

<div class="flex flex-col gap-6">
    <!-- Host a game -->
    <div class="w-full bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4 text-red-900">Host a New Game</h2>
        <form action="index.php?action=host" method="post">
            <div class="mb-4">
                <label for="hostName" class="block text-gray-700 text-base font-bold mb-2">Your Name</label>
                <input type="text" name="hostName" id="hostName" 
                       class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       required minlength="2" maxlength="20">
            </div>
            <div class="flex justify-center">
                <button type="submit" class="bg-red-900 hover:bg-red-800 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline w-full sm:w-auto text-lg">
                    Host Game
                </button>
            </div>
        </form>
    </div>

    <!-- Join a game -->
    <div class="w-full bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4 text-red-900">Join a Game</h2>
        <form action="index.php?action=join" method="post">
            <div class="mb-4">
                <label for="gameCode" class="block text-gray-700 text-base font-bold mb-2">Game Code</label>
                <input type="text" name="gameCode" id="gameCode" 
                       class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline uppercase"
                       required minlength="5" maxlength="5" placeholder="ABCDE"
                       autocomplete="off" autocapitalize="characters">
            </div>
            <div class="mb-4">
                <label for="playerName" class="block text-gray-700 text-base font-bold mb-2">Your Name</label>
                <input type="text" name="playerName" id="playerName" 
                       class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       required minlength="2" maxlength="20">
            </div>
            <div class="flex justify-center">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline w-full sm:w-auto text-lg">
                    Join Game
                </button>
            </div>
        </form>
    </div>
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