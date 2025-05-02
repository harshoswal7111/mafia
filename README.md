# Mafia Game Facilitator

A web-based facilitator for the popular social deduction game "Mafia" (also known as "Werewolf"). This app allows in-person players to manage game state without needing physical cards or a dedicated non-player moderator.

## Features

- **Simple Setup**: Host creates a game and shares a 5-character code with players
- **Role Assignment**: Automatically assigns roles (mafia, doctor, detective, villager)
- **Game Flow Management**: Handles night actions, day discussions, voting, and win conditions
- **Multiple Settings**:
  - Storyteller Mode (host narrates events for added immersion)
  - Sheriff Mode (one villager gets a vote that counts twice)
  - Adjustable mafia count
- **Real-time Updates**: Lobby and game state changes reflected via polling
- **Mobile-Friendly**: Responsive design using Tailwind CSS

## Requirements

- PHP 7.4+ web server
- Standard web hosting support (no database required)

## Installation

1. **Clone or download this repository**

2. **Upload files to your web hosting**
   - Upload all files to your hosting service's public web directory
   - This application uses PHP files for data storage, so a standard PHP hosting environment is sufficient

3. **File permissions**
   - Ensure the `data` directory and its subdirectories are writable by the web server
   ```
   chmod -R 755 data
   ```

4. **Configuration**
   - The app works out of the box with no database setup or configuration needed
   - Security protections for the data directory are included in .htaccess files

## Usage

1. **Host a Game**
   - Visit the homepage and enter your name to host a new game
   - Share the 5-character game code with other players
   - Configure game settings (mafia count, storyteller mode, sheriff mode)
   - Start the game when all players have joined

2. **Join a Game**
   - Enter the 5-character game code and your name on the homepage
   - Wait in the lobby for the host to start the game

3. **Game Play**
   - **Night Phase**: Special roles (mafia, doctor, detective) perform actions
   - **Day Results**: See who was killed during the night
   - **Day Voting**: All players vote on who to eliminate
   - **End Day Results**: See voting results and who was eliminated
   - Game continues until either all mafia are eliminated or mafia outnumber villagers

## Security Notes

The app includes several security measures:
- Game data is stored in PHP files that prevent direct access
- Game codes are randomly generated alphanumeric strings
- Session handling for player identification
- No database means simplified deployment and maintenance

## License

This project is open source and available for free use and modification.

## Credits

Created by [Your Name]