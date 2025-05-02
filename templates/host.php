<?php $content = 'host.php'; ?>
<form method="POST" class="space-y-4">
    <div>
        <label class="block text-gray-700 mb-2">Your Name</label>
        <input type="text" name="hostName" required 
               class="w-full p-2 border rounded-lg" 
               placeholder="Enter your name">
    </div>
    <button type="submit" 
            class="w-full bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600 transition-colors">
        Create Game
    </button>
</form>