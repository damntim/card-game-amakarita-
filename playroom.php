<?php 
include 'process.php';
?> 
<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Amakarita Game Room</title>
                <!-- Tailwind CSS via CDN -->
                <script src="https://cdn.tailwindcss.com"></script>
                <!-- Font Awesome -->
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
                <!-- Google Fonts -->
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                <style>
                    body {
                        font-family: 'Poppins', sans-serif;
                        background-color: #1a4731;
                    }
                    
                    .card {
                        width: 100px;
                        height: 140px;
                        perspective: 1000px;
                        margin: 5px;
                        transition: all 0.3s;
                    }
                    
                    .card:hover {
                        transform: translateY(-10px);
                    }
                    
                    .card-inner {
                        position: relative;
                        width: 100%;
                        height: 100%;
                        text-align: center;
                        transition: transform 0.6s;
                        transform-style: preserve-3d;
                        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
                        border-radius: 10px;
                    }
                    
                    .card-back, .card-front {
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        -webkit-backface-visibility: hidden;
                        backface-visibility: hidden;
                        border-radius: 10px;
                    }
                    
                    .card-front {
                        background-color: white;
                        color: black;
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        padding: 5px;
                    }
                    
                    .card-back {
                        background-color: #2d3748;
                        background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDUwIDUwIj48cGF0aCBmaWxsPSIjNGE1NTY4IiBkPSJNMjUgMTBMMTAgMjVMMjUgNDBMNDAgMjVMMjUgMTBaTTI1IDVMMjUgMTVMMzUgMjVMMjUgMzVMMjUgNDVMNDUgMjVMMjUgNVoiLz48L3N2Zz4=');
                        transform: rotateY(180deg);
                    }
                    
                    .card-value {
                        font-size: 24px;
                        font-weight: bold;
                    }
                    
                    .card-suit {
                        font-size: 36px;
                        line-height: 1;
                    }
                    
                    .card-center {
                        font-size: 48px;
                        flex-grow: 1;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .card.playable {
                        cursor: pointer;
                        box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
                    }
                    
                    .card.playable:hover {
                        transform: translateY(-20px);
                    }
                    
                    .player-area {
                        transition: all 0.3s;
                        border: 2px solid transparent;
                    }
                    
                    .player-area.active {
                        border-color: #f59e0b;
                        background-color: rgba(245, 158, 11, 0.1);
                    }
                    
                    .table-area {
                        background-color: #166534;
                        border-radius: 50%;
                        box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.5);
                    }
                    
                    .major-suit {
                        animation: pulse 2s infinite;
                    }
                    
                    @keyframes pulse {
                        0% {
                            transform: scale(1);
                            opacity: 1;
                        }
                        50% {
                            transform: scale(1.05);
                            opacity: 0.8;
                        }
                        100% {
                            transform: scale(1);
                            opacity: 1;
                        }
                    }
                    
                    .hidden-card .card-inner {
                        transform: rotateY(180deg);
                    }
                    
                    .played-card {
                        transition: all 0.5s;
                    }
                    
                    .winner-highlight {
                        box-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
                        animation: winner-glow 1.5s infinite;
                    }
                    
                    @keyframes winner-glow {
                        0% {
                            box-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
                        }
                        50% {
                            box-shadow: 0 0 25px rgba(255, 215, 0, 1);
                        }
                        100% {
                            box-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
                        }
                    }
                    
                    /* Chat styles */
                    .chat-container {
                        background-color: rgba(23, 23, 23, 0.8);
                        border-radius: 10px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        transition: all 0.3s ease;
                    }
                    
                    .chat-messages {
                        height: 250px;
                        overflow-y: auto;
                        scrollbar-width: thin;
                        scrollbar-color: #4a5568 #2d3748;
                    }
                    
                    .chat-messages::-webkit-scrollbar {
                        width: 8px;
                    }
                    
                    .chat-messages::-webkit-scrollbar-track {
                        background: #2d3748;
                        border-radius: 10px;
                    }
                    
                    .chat-messages::-webkit-scrollbar-thumb {
                        background-color: #4a5568;
                        border-radius: 10px;
                    }
                    
                    .message {
                        margin-bottom: 8px;
                        padding: 8px 12px;
                        border-radius: 10px;
                        max-width: 85%;
                        word-break: break-word;
                    }
                    
                    .message-self {
                        background-color: #3b82f6;
                        margin-left: auto;
                    }
                    
                    .message-other {
                        background-color: #4b5563;
                        margin-right: auto;
                    }
                    
                    .message-time {
                        font-size: 0.7rem;
                        opacity: 0.7;
                    }
                    
                    .mention {
                        background-color: rgba(59, 130, 246, 0.3);
                        padding: 2px 4px;
                        border-radius: 4px;
                        font-weight: 500;
                    }
                    
                    .mention-dropdown {
                        position: absolute;
                        background-color: #1f2937;
                        border: 1px solid #374151;
                        border-radius: 6px;
                        max-height: 150px;
                        overflow-y: auto;
                        z-index: 50;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    
                    .mention-item {
                        padding: 8px 12px;
                        cursor: pointer;
                    }
                    
                    .mention-item:hover {
                        background-color: #374151;
                    }
                    
                    .chat-toggle {
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        z-index: 40;
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        background-color: #3b82f6;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        cursor: pointer;
                        display: none;
                    }
                    
                    @media (max-width: 768px) {
                        .chat-container {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            z-index: 30;
                            border-radius: 15px 15px 0 0;
                            max-height: 60vh;
                            transform: translateY(100%);
                            transition: transform 0.3s ease;
                        }
                        
                        .chat-container.active {
                            transform: translateY(0);
                        }
                        
                        .chat-toggle {
                            display: flex;
                        }
                        
                        .chat-messages {
                            height: calc(60vh - 120px);
                        }
                    }
                </style>
                <?php if (!$game_over && $current_player_number != $current_user_player_number): ?>
                <!-- Auto refresh for non-active players -->
                <meta http-equiv="refresh" content="5">
                <?php endif; ?>
            </head>
            <body class="min-h-screen text-white py-6 px-4">
                <div class="max-w-7xl mx-auto">
                    <!-- Game Header -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                        <div>
                            <h1 class="text-3xl font-bold mb-1">Amakarita Game</h1>
                            <p class="text-gray-300">Game Code: <span class="font-mono bg-gray-800 px-2 py-1 rounded"><?php echo $game_code; ?></span></p>
                        </div>
                        
                        <div class="mt-4 md:mt-0 flex flex-col items-end">
                            <div class="flex items-center mb-2">
                                <span class="mr-2">Round:</span>
                                <span class="bg-gray-800 px-3 py-1 rounded font-bold"><?php echo $current_round; ?></span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="mr-2">Major Suit:</span>
                                <span class="bg-gray-800 px-3 py-1 rounded font-bold flex items-center">
                                    <span class="<?php echo getCardColor($major_suit) === 'red' ? 'text-red-500' : 'text-white'; ?> mr-1">
                                        <?php echo getCardSymbol($major_suit); ?>
                                    </span>
                                    <?php echo ucfirst($major_suit); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($game_over): ?>
                    <!-- Game Over Banner -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg p-6 mb-8 text-center">
                        <h2 class="text-2xl font-bold mb-2">Game Over!</h2>
                        <p class="text-xl"><?php echo $winner_info; ?></p>
                        <div class="mt-4">
                            <a href="games.php" class="bg-white text-indigo-700 px-6 py-2 rounded-lg font-bold hover:bg-gray-100 transition">
                                Back to Games
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Scores Section -->
                    <div class="bg-gray-800 rounded-lg p-4 mb-6">
                        <h2 class="text-xl font-bold mb-3">Scores</h2>
                        
                        <?php if ($player_count == 4 || $player_count == 6): ?>
                        <!-- Team Scores -->
                        <div class="grid grid-cols-2 gap-4 mb-2">
                            <div class="bg-gray-700 rounded p-3 flex justify-between items-center">
                                <span class="text-blue-400 font-bold">Team Blue</span>
                                <span class="bg-blue-900 px-3 py-1 rounded-full"><?php echo isset($team_scores[1]) ? $team_scores[1] : 0; ?> pts</span>
                            </div>
                            <div class="bg-gray-700 rounded p-3 flex justify-between items-center">
                                <span class="text-red-400 font-bold">Team Red</span>
                                <span class="bg-red-900 px-3 py-1 rounded-full"><?php echo isset($team_scores[2]) ? $team_scores[2] : 0; ?> pts</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Individual Scores -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-<?php echo min(6, $player_count); ?> gap-2">
                            <?php foreach ($scores as $score): ?>
                            <div class="bg-gray-700 rounded p-2 text-center <?php echo ($game_over && (($player_count < 4 && $score['username'] === $winner_name) || ($player_count >= 4 && $score['team'] === $winner_team))) ? 'winner-highlight' : ''; ?>">
                                <div class="text-sm font-medium mb-1">
                                    <?php echo getPlayerNameWithTeam($score, $current_user); ?>
                                </div>
                                <div class="bg-gray-800 rounded-full px-2 py-1 text-sm">
                                    <?php echo $score['points']; ?> pts
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Game Board -->
                 <?php
                 include 'game_board.php';
                 ?>
                    
                    <!-- Game Controls -->
                    <div class="flex justify-between items-center mb-6">
                        <a href="games.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Games
                        </a>
                        
                        <?php if (!$game_over): ?>
                        <div class="text-sm text-gray-400">
                            <?php if ($current_player_number == $current_user_player_number): ?>
                            <span class="animate-pulse">It's your turn to play!</span>
                            <?php else: ?>
                            <span>Waiting for other players...</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                   
                </div>
                
                <!-- Mobile Chat Toggle Button -->
                <div class="chat-toggle" id="chatToggle">
                    <i class="fas fa-comments text-white text-xl"></i>
                </div>
                
                <script>
                    // Game code for chat functionality
                    const gameCode = '<?php echo $game_code; ?>';
                    const currentUser = '<?php echo $current_user; ?>';
                    let lastMessageId = 0;
                    let gamePlayers = [];
                    let mentionSearch = '';
                    let mentionStartIndex = -1;
                    
                    // DOM elements
                    const chatMessages = document.getElementById('chatMessages');
                    const messageInput = document.getElementById('messageInput');
                    const chatForm = document.getElementById('chatForm');
                    const mentionDropdown = document.getElementById('mentionDropdown');
                    const chatToggle = document.getElementById('chatToggle');
                    const chatContainer = document.querySelector('.chat-container');
                    const chatClose = document.querySelector('.chat-close');
                    
                    // Auto-play configuration
                    const isHumanTurn = <?php echo ($current_player_number == $current_user_player_number && !$game_over) ? 'true' : 'false'; ?>;
                    const autoPlayTimeoutMs = 15000; // 15 seconds
                    let autoPlayTimer = null;
                    
                    // Fetch players for @mentions
                    function fetchPlayers() {
                        fetch(`chat_process.php?action=players&game_code=${gameCode}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.players) {
                                    gamePlayers = data.players;
                                }
                            })
                            .catch(error => console.error('Error fetching players:', error));
                    }
                    
                    // Fetch messages
                    function fetchMessages() {
                        fetch(`chat_process.php?action=fetch&game_code=${gameCode}&last_id=${lastMessageId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.messages && data.messages.length > 0) {
                                    renderMessages(data.messages);
                                    // Update last message ID
                                    lastMessageId = data.messages[data.messages.length - 1].id;
                                }
                                
                                // Remove loading message if it exists
                                const loadingMsg = chatMessages.querySelector('.text-center.text-gray-500');
                                if (loadingMsg && chatMessages.children.length > 1) {
                                    loadingMsg.remove();
                                }
                            })
                            .catch(error => console.error('Error fetching messages:', error));
                    }
                    
                    // Render messages
                    function renderMessages(messages) {
                        let html = '';
                        
                        messages.forEach(message => {
                            const messageClass = message.is_current_user ? 'message-self' : 'message-other';
                            const alignClass = message.is_current_user ? 'text-right' : 'text-left';
                            const date = new Date(message.created_at);
                            const timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            
                            // Process message text for @mentions
                            let messageText = message.message;
                            gamePlayers.forEach(player => {
                                const mentionRegex = new RegExp(`@${player}\\b`, 'g');
                                messageText = messageText.replace(mentionRegex, `<span class="mention">@${player}</span>`);
                            });
                            
                            html += `
                                <div class="message-container ${alignClass} mb-2">
                                    <div class="text-xs text-gray-400 mb-1">${message.username}</div>
                                    <div class="message ${messageClass}">
                                        <div>${messageText}</div>
                                        <div class="message-time ${message.is_current_user ? 'text-right' : 'text-left'}">${timeString}</div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        // Append new messages
                        chatMessages.innerHTML += html;
                        
                        // Scroll to bottom
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                    
                    // Send message
                    function sendMessage(message) {
                        const formData = new FormData();
                        formData.append('action', 'send');
                        formData.append('game_code', gameCode);
                        formData.append('message', message);
                        
                        fetch('chat_process.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Clear input
                                messageInput.value = '';
                                // Fetch messages immediately to show the new message
                                fetchMessages();
                            } else if (data.error) {
                                console.error('Error sending message:', data.error);
                            }
                        })
                        .catch(error => console.error('Error sending message:', error));
                    }
                    
                    // Handle @mentions
                    function handleMention() {
                        const text = messageInput.value;
                        const cursorPos = messageInput.selectionStart;
                        
                        // Find the @ symbol before the cursor
                        let i = cursorPos - 1;
                        while (i >= 0 && text[i] !== ' ' && text[i] !== '@') {
                            i--;
                        }
                        
                        if (i >= 0 && text[i] === '@') {
                            mentionStartIndex = i;
                            mentionSearch = text.substring(i + 1, cursorPos).toLowerCase();
                            
                            // Filter players based on search
                            const filteredPlayers = gamePlayers.filter(player => 
                                player.toLowerCase().includes(mentionSearch)
                            );
                            
                            if (filteredPlayers.length > 0) {
                                showMentionDropdown(filteredPlayers);
                            } else {
                                hideMentionDropdown();
                            }
                        } else {
                            hideMentionDropdown();
                            mentionStartIndex = -1;
                        }
                    }
                    
                    // Show mention dropdown
                    function showMentionDropdown(players) {
                        // Position the dropdown
                        const inputRect = messageInput.getBoundingClientRect();
                        mentionDropdown.style.top = `${inputRect.bottom}px`;
                        mentionDropdown.style.left = `${inputRect.left}px`;
                        mentionDropdown.style.width = `${inputRect.width}px`;
                        
                        // Populate dropdown
                        let html = '';
                        players.forEach(player => {
                            html += `<div class="mention-item" data-username="${player}">@${player}</div>`;
                        });
                        
                        mentionDropdown.innerHTML = html;
                        mentionDropdown.classList.remove('hidden');
                        
                        // Add click event to mention items
                        document.querySelectorAll('.mention-item').forEach(item => {
                            item.addEventListener('click', function() {
                                const username = this.getAttribute('data-username');
                                insertMention(username);
                            });
                        });
                    }
                    
                    // Hide mention dropdown
                    function hideMentionDropdown() {
                        mentionDropdown.classList.add('hidden');
                    }
                    
                    // Insert mention into input
                    function insertMention(username) {
                        if (mentionStartIndex >= 0) {
                            const text = messageInput.value;
                            const beforeMention = text.substring(0, mentionStartIndex);
                            const afterMention = text.substring(messageInput.selectionStart);
                            
                            messageInput.value = `${beforeMention}@${username} ${afterMention}`;
                            
                            // Set cursor position after the inserted mention
                            const newCursorPos = mentionStartIndex + username.length + 2; // +2 for @ and space
                            messageInput.setSelectionRange(newCursorPos, newCursorPos);
                            
                            // Reset mention state
                            hideMentionDropdown();
                            mentionStartIndex = -1;
                            
                            // Focus back on input
                            messageInput.focus();
                        }
                    }
                    
                    // Event listeners
                    document.addEventListener('DOMContentLoaded', function() {
                        // Initial fetch of players and messages
                        fetchPlayers();
                        fetchMessages();
                        
                        // Set up polling for new messages (every 3 seconds)
                        setInterval(fetchMessages, 3000);
                        
                        // Chat form submission
                        chatForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            const message = messageInput.value.trim();
                            if (message) {
                                sendMessage(message);
                            }
                        });
                        
                        // Input for @mentions
                        messageInput.addEventListener('input', handleMention);
                        messageInput.addEventListener('keydown', function(e) {
                            // Handle arrow keys for navigating dropdown
                            if (!mentionDropdown.classList.contains('hidden')) {
                                const items = mentionDropdown.querySelectorAll('.mention-item');
                                const activeItem = mentionDropdown.querySelector('.mention-item.bg-blue-600');
                                let activeIndex = -1;
                                
                                if (activeItem) {
                                    activeIndex = Array.from(items).indexOf(activeItem);
                                }
                                
                                // Down arrow
                                if (e.key === 'ArrowDown') {
                                    e.preventDefault();
                                    if (activeIndex < items.length - 1) {
                                        if (activeItem) activeItem.classList.remove('bg-blue-600', 'text-white');
                                        items[activeIndex + 1].classList.add('bg-blue-600', 'text-white');
                                        items[activeIndex + 1].scrollIntoView({ block: 'nearest' });
                                    }
                                }
                                
                                // Up arrow
                                else if (e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    if (activeIndex > 0) {
                                        if (activeItem) activeItem.classList.remove('bg-blue-600', 'text-white');
                                        items[activeIndex - 1].classList.add('bg-blue-600', 'text-white');
                                        items[activeIndex - 1].scrollIntoView({ block: 'nearest' });
                                    }
                                }
                                
                                // Enter to select
                                else if (e.key === 'Enter' && activeItem) {
                                    e.preventDefault();
                                    const username = activeItem.getAttribute('data-username');
                                    insertMention(username);
                                }
                                
                                // Escape to close dropdown
                                else if (e.key === 'Escape') {
                                    e.preventDefault();
                                    hideMentionDropdown();
                                }
                            }
                        });
                        
                        // Close dropdown when clicking outside
                        document.addEventListener('click', function(e) {
                            if (!messageInput.contains(e.target) && !mentionDropdown.contains(e.target)) {
                                hideMentionDropdown();
                            }
                        });
                        
                        // Mobile chat toggle
                        if (chatToggle) {
                            chatToggle.addEventListener('click', function() {
                                chatContainer.classList.toggle('active');
                                // Scroll to bottom when opening chat
                                if (chatContainer.classList.contains('active')) {
                                    chatMessages.scrollTop = chatMessages.scrollHeight;
                                }
                            });
                        }
                        
                        // Mobile chat close
                        if (chatClose) {
                            chatClose.addEventListener('click', function() {
                                chatContainer.classList.remove('active');
                            });
                        }

                        // Auto-play timer for multi-player human turns
                        if (isHumanTurn) {
                            // Start a 15s timer; if player doesn't play, auto-play on server
                            autoPlayTimer = setTimeout(function() {
                                // Send a POST request to trigger auto-play
                                fetch('playroom.php?code=' + encodeURIComponent(gameCode), {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: 'auto_play=1'
                                }).then(function() {
                                    // After auto-play, reload to reflect new state
                                    window.location.reload();
                                }).catch(function(err) {
                                    console.error('Auto-play failed:', err);
                                });
                            }, autoPlayTimeoutMs);

                            // If user manually plays a card, the page will reload and cancel this timer naturally.
                        }
                    });
                </script>
            </body>
            </html>