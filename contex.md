Rwandan Card Game Web Application Documentation
Overview
This documentation outlines the requirements and specifications for developing a web-based Rwandan card game. The application will be built using PHP and Tailwind CSS with MySQL as the database. The game supports different player modes and configurations, allowing users to play against other people or CPU opponents.
Game Basics
Cards and Values
The game uses 36 cards with the following structure:
Card	Kinyarwanda Name	Value
Ace	Bwana	11
7	Seti/Madam	10
King	Wayine	4
Jack	Watatu	3
Queen	Wapili	2
6	Ngada Sisi	0
5	Ndanda Tanu	0
4	Ngada Yine	0
3	Totwe	0
Card Suits:
•	Hearts (Umutima) - Red
•	Diamonds (Ikaro) - Red
•	Clubs (Umusaraba) - Black
•	Spades (Isuka) - Black
Total: 36 cards with a combined value of 120 points
Game Creation Process
1.	User creates a game by selecting: 
o	Number of players (2, 3, 4, or 6)
o	Game visibility (Public or Private)
2.	Public games: 
o	System generates a joining code
o	Code appears on the front page for others to see
o	Others can copy the code to join
3.	Private games: 
o	System generates a joining code
o	Creator shares code with friends
o	Friends use code to join
4.	Game begins when required number of players join
5.	CPU players can fill missing slots
Game Modes and Rules
Two-Player Mode (User-User or User-CPU)
•	Each player receives 3 cards initially
•	One card is displayed as the "major" (iyakaswe) determining priority suit
•	Example: If Hearts is the major suit, a lower value Heart beats a higher value card of another suit
•	Turn-based play with each player selecting one card per turn
•	Winner of each round takes the cards based on: 
1.	Cards with the major suit beats other suits
2.	For same suit, higher value card wins
•	After each round, players draw a new card (starting with 3, picking from remaining 30)
•	Game continues for 15 rounds until all cards are played
•	Points are calculated based on the value of cards won
•	Player with highest point total wins
•	CPU opponent should be "intelligent" in gameplay decisions
Multi-Player Modes (3, 4, 6 players)
•	For 3 players: All 36 cards are distributed equally
•	For 4 or 6 players: 
o	Players form equal teams (2 teams of 2 for 4-player mode, 2 teams of 3 for 6-player mode)
o	Teams are assigned different color tags for identification
o	Players can only see their own cards, not teammates' cards
•	One major suit is displayed at the beginning
•	Play follows the same priority rules as two-player mode
•	Teams combine their points at the end
Technical Requirements
Frontend
•	Responsive web design using Tailwind CSS
•	Card visualization with proper suit colors (red for Hearts/Diamonds, black for Clubs/Spades)
•	Game UI should show: 
o	Player's current cards
o	Major suit indicator
o	Turn indicator
o	Team identification (for 4/6 player games)
o	Timer for moves (30 seconds)
o	Score tracking
o	Game history
Backend (PHP)
•	User authentication system
•	Game creation and joining logic
•	Game state management
•	Card dealing and shuffling algorithms
•	Game rules implementation
•	CPU AI for computer opponents
•	Realtime game updates (consider using WebSockets)
