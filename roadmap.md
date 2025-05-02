# Roadmap: Mafia Facilitator Web App (PHP + Tailwind CSS, No DB) - Hostinger Deployment Structure

This document outlines the steps to create a web application that facilitates an in-person game of Mafia, designed for deployment on shared hosting like Hostinger where direct file access might be a concern. The app uses PHP and Tailwind CSS only, with no database.

**Core Strategy for Data Protection:** Game state data will be stored in `.php` files within a dedicated directory. These files will return the game data when `include`d by the main application scripts but will terminate with a "Forbidden" message if accessed directly via HTTP.

**Core Technologies:**

*   **Backend:** PHP (latest stable version recommended)
*   **Frontend Styling:** Tailwind CSS
*   **Data Storage:** PHP Files (returning arrays), managed via PHP's file system functions.

**Key Constraints:**

*   No database allowed.
*   All project files will reside within the web-accessible directory (e.g., `public_html`).
*   Data files must be protected from direct URL access *without relying solely* on server configuration like `.htaccess`.
*   No computer-controlled players (Bots).

---

## Phase 0: Project Setup & Foundation (Est. Time: 1-2 days)

1.  **Project Structure (within `public_html` or repo root):**
    ```
    / (e.g., public_html)
    ├── index.php           # Main router/entry point
    ├── src/                # Core PHP classes & functions (game logic, state management)
    │   └── GameState.php   # Example class to handle game data interaction
    ├── templates/          # PHP files generating HTML (views)
    │   ├── layout.php
    │   ├── home.php
    │   ├── lobby.php
    │   └── game.php
    ├── assets/             # CSS, JS (minimal), images
    │   └── css/
    │       └── style.css   # Compiled TailwindCSS
    ├── data/               # Directory for game state (SHOULD ideally have restrictive permissions/rules if possible)
    │   ├── index.php       # Prevents directory listing, denies access
    │   └── games/          # Directory holding individual game state files
    │       ├── index.php   # Prevents directory listing, denies access
    │       └── ABCDE.php   # Example game state file (NOT .json)
    │       └── FGHIJ.php
    └── .gitignore          # Git ignore file (e.g., ignore data/games/* if not tracking state in git)
    ```
    *   **`data/index.php` and `data/games/index.php` Content:**
        ```php
        <?php
        // Prevent directory listing and direct access
        http_response_code(403);
        die("Forbidden");
        ?>
        ```
2.  **Basic Routing:**
    *   Set up `index.php` to handle requests based on URL parameters (e.g., `?action=host`, `?action=join&code=ABCDE`, `?action=game&code=ABCDE`). Include necessary files from `src/` and `templates/`.
3.  **Tailwind CSS Setup:**
    *   Install and configure Tailwind CSS.
    *   Set up a build process to generate `assets/css/style.css`. Commit the compiled CSS file to the repository for simple deployment.
4.  **Version Control:** Initialize a Git repository. Add `data/games/` to `.gitignore` unless you want to commit example/empty structures.

---

## Phase 1: Core Game State Management (Using PHP Files) (Est. Time: 3-5 days)

1.  **Game State PHP File Structure:**
    *   Each game state file (e.g., `data/games/ABCDE.php`) will look like this:
        ```php
        <?php
        // Prevent direct script execution
        if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
             http_response_code(403);
             die("Forbidden");
        }

        // Return the game state array when included
        return [
            "gameCode" => "ABCDE",
            "hostPlayerId" => "unique_host_id",
            "players" => [
                "unique_player_id_1" => [ "name" => "Alice", "role" => null, "status" => "alive", "votedFor" => null, "nightActionTarget" => null ],
                "unique_player_id_2" => [ "name" => "Bob", "role" => null, "status" => "alive", "votedFor" => null, "nightActionTarget" => null ]
            ],
            "settings" => [
                "storytellerMode" => false,
                "sheriffMode" => false,
                "mafiaCount" => 1
            ],
            "phase" => "lobby",
            // ... other game state fields (currentNight, actionsNeeded, etc.)
            "winner" => null
        ];
        ?>
        ```
2.  **Game State Functions (e.g., within `src/GameState.php` class):**
    *   `getGameFilePath(gameCode)`: Returns the full path like `/path/to/public_html/data/games/ABCDE.php`. Perform basic sanitization on `gameCode` to prevent directory traversal (e.g., allow only alphanumeric).
    *   `getGame(gameCode)`:
        1.  Get file path using `getGameFilePath`.
        2.  Check if file exists (`file_exists`). Return `false` or throw exception if not.
        3.  **Use `include` to load the data:** `$gameData = include $filePath;`. The `include` statement will execute the PHP file, which returns the array.
        4.  Return `$gameData`. (File locking for reads is less critical here if writes are atomic and locked, but consider potential inconsistencies if reads happen during a write).
    *   `saveGame(gameCode, gameData)`:
        1.  Get file path using `getGameFilePath`.
        2.  **Acquire an exclusive lock:** Use `fopen` with 'c' mode (create if not exists, pointer at beginning, doesn't truncate), then `flock($handle, LOCK_EX)`. This is CRITICAL.
        3.  **Format the data as a PHP string:** Use `var_export($gameData, true)` to get a string representation of the PHP array.
        4.  **Construct the full PHP file content:**
            ```php
            $phpFileContent = "<?php\n";
            $phpFileContent .= "// Prevent direct script execution\n";
            $phpFileContent .= "if (basename(__FILE__) == basename(\$_SERVER[\"SCRIPT_FILENAME\"])) { http_response_code(403); die(\"Forbidden\"); }\n\n";
            $phpFileContent .= "// Return the game state array when included\n";
            $phpFileContent .= "return " . var_export($gameData, true) . ";\n";
            $phpFileContent .= "?>";
            ```
        5.  **Write the content:** Use `ftruncate($handle, 0)` to clear the file, `rewind($handle)` to go to the start, and `fwrite($handle, $phpFileContent)` to write the new content.
        6.  **Release the lock and close:** `fflush($handle)`, `flock($handle, LOCK_UN)`, `fclose($handle)`.
    *   `createGame(hostName)`: Generate unique code, prepare initial data array, call `saveGame`.
    *   `addPlayer(gameCode, playerName)`: Load game (`getGame`), modify players array, save game (`saveGame`).
    *   `updatePlayer`, `updateSettings` etc.: Follow the load-modify-save pattern, always using `getGame` and `saveGame` which handle locking.
3.  **Unique Player Identification:**
    *   Use PHP sessions (`session_start()`) or manually set cookies (`setcookie()`) to store the `gameCode` and unique `playerId` for each user interacting with a game. Read these from `$_SESSION` or `$_COOKIE` to identify the user on subsequent requests.

---

## Phase 2: Lobby & Game Setup UI (Est. Time: 2-3 days)

*(Largely the same as the previous roadmap, but ensuring all interactions use the new `getGame` and `saveGame` functions)*

1.  **Homepage (`index.php` or `index.php?action=home`):** Host/Join forms.
2.  **Host Action (`index.php?action=host` - POST):** Call `createGame`, store IDs in session/cookie, redirect to Lobby.
3.  **Join Action (`index.php?action=join` - POST):** Validate code (check file existence via `getGameFilePath`), call `addPlayer`, store IDs, redirect to Lobby.
4.  **Lobby Page (`index.php?action=lobby&code=[game_code]`):**
    *   Use `getGame` to fetch current state.
    *   Display code, players.
    *   **Host View:** Controls for Sheriff, Storyteller, **Mafia Count**. Changes trigger an update via AJAX/form post calling an `updateSettings` action, which uses `saveGame`.
    *   **Player View:** Display settings.
    *   Polling endpoint (`index.php?action=lobby_status&code=[game_code]`) uses `getGame` to return JSON needed for updates.
5.  **Start Game Action (`index.php?action=start_game` - POST by host):**
    *   Load game state (`getGame`).
    *   Validate settings & player count.
    *   Assign roles, update phase to 'night', initialize `actionsNeeded`.
    *   Save updated state (`saveGame`).
    *   Redirect players to the Game page (`index.php?action=game&code=[game_code]`).

---

## Phase 3: Night Phase Logic & UI (Est. Time: 3-4 days)

*(Largely the same, ensuring use of `getGame` and `saveGame`)*

1.  **Game Page (`index.php?action=game&code=[game_code]`):**
    *   Use `getGame` to load state.
    *   Render view based on player role and game phase ('night').
    *   Mafia/Doctor/Detective see target selection forms. Others see waiting message.
2.  **Night Action Submission (`index.php?action=submit_action` - POST):**
    *   Get player ID, game code from session/cookie/form.
    *   Load game (`getGame`).
    *   Validate action.
    *   Update player's `nightActionTarget`, remove from `actionsNeeded`.
    *   Save game (`saveGame`).
3.  **Night Phase Progression:**
    *   After action submission, reload state (`getGame`). Check if `actionsNeeded` is empty.
    *   If empty: Process actions (determine kills/saves/investigations), update player statuses, update phase to `day_results`.
    *   Save final night results (`saveGame`).
    *   Redirect/instruct client to refresh to see Day phase.

---

## Phase 4: Day Phase Logic & UI (Est. Time: 3-4 days)

*(Largely the same, ensuring use of `getGame` and `saveGame`)*

1.  **Game Page - Day Results (Phase `day_results`):** Load state (`getGame`), display night outcome, show Detective result privately, identify Storyteller. Show "Proceed to Voting" button.
2.  **Proceed to Voting Action (`index.php?action=start_vote` - POST):** Load state, update phase to `day_vote`, clear votes, save state. Redirect.
3.  **Game Page - Day Voting (Phase `day_vote`):** Load state, display voting options. Disable if player already voted.
4.  **Submit Vote Action (`index.php?action=submit_vote` - POST):** Load state, record vote, save state. Check if all voted.
5.  **Vote Tally & Lynching:** If all voted, load state, tally votes (Sheriff x2), determine lynch, update status, update phase to `end_day_results`, save state. Redirect.
6.  **Game Page - End Day Results (Phase `end_day_results`):** Load state, display tally/lynch result, show "Proceed to Night" button.

---

## Phase 5: Game End & Loop (Est. Time: 1-2 days)

*(Largely the same, ensuring use of `getGame` and `saveGame`)*

1.  **Win Condition Check:** After any status change (death), load state (`getGame`), check win conditions. If met, update phase to `end`, set `winner`, save state (`saveGame`).
2.  **Game Page - End Game (Phase `end`):** Load state (`getGame`), display winner/roles. Host sees Reset/New Game/Leave. Player sees Leave.
    *   **Reset Action:** Load state, reset specific fields (roles, status, phase), save state.
    *   **New Game Action:** Get file path, use `unlink($filePath)` to delete the game file. Redirect host.
3.  **Game Loop (Proceed to Night button):** Load state, update phase to `night`, reset night variables (`actionsNeeded`, `killedTonight`, etc.), save state. Redirect.

---

## Phase 6: Enhancements & Polish (Est. Time: 1-2 days)

*(Largely the same)*

1.  **Storyteller Mode:** Implement display logic based on `settings.storytellerMode`.
2.  **UI/UX:** Refine Tailwind, add instructions, improve polling feedback.
3.  **Error Handling:** Robust checks for file operations, invalid inputs, invalid states.

---

## Phase 7: Testing & Deployment (Est. Time: 2-4 days)

1.  **Testing:**
    *   Thoroughly test all roles, actions, phases, Mafia counts, settings.
    *   **Crucially test concurrent actions (multiple votes/night actions simultaneously)** to ensure the `flock`-based locking in `saveGame` prevents data corruption. Open multiple browsers/private windows.
    *   Test win conditions, game reset, game deletion (`unlink`).
    *   **Verify direct access prevention:** Try navigating to `yourdomain.com/data/` , `yourdomain.com/data/games/`, and `yourdomain.com/data/games/ABCDE.php` (use a real game code). You should get a "Forbidden" 403 error.
2.  **Deployment (Hostinger via Git):**
    *   Ensure your Git repository is set up to deploy to the correct Hostinger directory (e.g., `public_html`).
    *   Make sure the `.gitignore` prevents unwanted files (like actual game state files, unless intended) from being committed.
    *   Commit the compiled `assets/css/style.css`.
    *   After deployment, SSH or use Hostinger's File Manager to **verify write permissions** for the web server user (e.g., `www-data` or similar) on the `data/` and `data/games/` directories. PHP needs to be able to create, write, and delete files (`.php` state files) in `data/games/`.
    *   **Optional but Recommended:** Even with the PHP file protection, try adding a `.htaccess` file inside the `data/` directory with `Deny from all` as an extra layer of defense, if Hostinger respects it there.

---

**Key Changes Summary:**

*   **Folder Structure:** Explicitly defined for deployment within `public_html`, includes protective `index.php` files in data directories.
*   **Data Storage:** Changed from `.json` to `.php` files containing `return [...] ;`.
*   **Data Protection:** Relies on PHP code within data files (`die("Forbidden");`) to prevent direct access, making it less dependent on server config.
*   **State Management:** `getGame` uses `include`, `saveGame` uses `fopen`/`flock`/`fwrite`/`fclose` with `var_export` to safely read/write the PHP data files, ensuring atomicity and preventing race conditions via locking.

This structure provides a reasonable level of security for the game state files within the constraints of shared hosting and avoiding complex configurations. Remember that file locking (`flock`) is absolutely paramount for `saveGame`.