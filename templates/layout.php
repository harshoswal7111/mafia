<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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

        <?php /* Main content will be inserted here */ ?>
        
    </div>

    <footer class="bg-gray-800 text-white text-center p-4 mt-auto text-sm">
        <p>&copy; <?php echo date('Y'); ?> Mafia Game Facilitator</p>
    </footer>
</body>
</html>