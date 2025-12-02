<div class="relative mb-8">
                        <!-- Table Area (Center) -->
                        <div class="table-area p-6 mb-6 flex flex-wrap justify-center items-center min-h-[200px]">
                            <?php if (count($table_cards) > 0): ?>
                            <div class="w-full text-center mb-4">
                                <span class="bg-gray-800 px-3 py-1 rounded text-sm">
                                    Cards on table
                                </span>
                            </div>
                            
                            <div class="flex flex-wrap justify-center gap-2">
                                <?php foreach ($table_cards as $card): ?>
                                <div class="relative">
                                    <div class="card played-card">
                                        <div class="card-inner">
                                            <div class="card-front" style="color: <?php echo getCardColor($card['suit']) === 'red' ? '#e53e3e' : '#2d3748'; ?>">
                                                <div class="flex justify-between">
                                                    <div class="card-value"><?php echo getCardValueDisplay($card['value']); ?></div>
                                                    <div class="card-suit"><?php echo getCardSymbol($card['suit']); ?></div>
                                                </div>
                                                <div class="card-center">
                                                    <?php echo getCardSymbol($card['suit']); ?>
                                                </div>
                                                <div class="flex justify-between">
                                                    <div class="card-suit"><?php echo getCardSymbol($card['suit']); ?></div>
                                                    <div class="card-value"><?php echo getCardValueDisplay($card['value']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="absolute -bottom-4 left-0 right-0 text-center">
                                        <span class="text-xs bg-gray-800 px-2 py-1 rounded-full">
                                            <?php echo htmlspecialchars($card['username']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-gray-300 italic">
                                Waiting for the first card to be played...
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Current Player Indicator -->
                        <?php if (!$game_over): ?>
                        <div class="text-center mb-6">
                            <div class="inline-block bg-yellow-600 px-4 py-2 rounded-full">
                                Current Player: 
                                <span class="font-bold">
                                    <?php 
                                    foreach ($players as $player) {
                                        if ($player['player_number'] == $current_player_number) {
                                            echo getPlayerNameWithTeam($player, $current_user);
                                            break;
                                        }
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Previous Round Cards -->
                        <?php if ($current_round > 1): ?>
<div class="flex flex-col md:flex-row gap-4 mb-6">
    <div class="bg-gray-800 rounded-lg p-4 md:w-1/2">
        <h2 class="text-xl font-bold mb-3">Last Round Cards</h2>
        
        <?php
        // Get cards from the previous round
        $prev_round = $current_round - 1;
        $prev_round_cards_sql = "SELECT c.*, m.player_id as move_player_id, m.id as move_id, m.move_time
                                FROM game_cards c 
                                JOIN game_moves m ON c.id = m.card_id
                                WHERE c.game_id = $game_id AND c.round = $prev_round AND c.status = 'won'
                                ORDER BY m.move_time ASC";
        $prev_round_cards_result = $conn->query($prev_round_cards_sql);
        $prev_round_cards = [];
        $move_order = 1;
        
        while ($card = $prev_round_cards_result->fetch_assoc()) {
            // Get the player who PLAYED the card (from game_moves)
            $player_sql = "SELECT username, player_number, team FROM game_players WHERE id = {$card['move_player_id']}";
            $player_result = $conn->query($player_sql);
            $player = $player_result->fetch_assoc();
            
            // Add player info to the card data
            $card['username'] = $player['username'];
            $card['player_number'] = $player['player_number'];
            $card['team'] = $player['team'];
            $card['move_order'] = $move_order++;
            
            $prev_round_cards[] = $card;
        }
        
        // Get the winner of the previous round
        $prev_round_winner_sql = "SELECT winner_player_id FROM game_rounds WHERE game_id = $game_id AND round_number = $prev_round";
        $prev_round_winner_result = $conn->query($prev_round_winner_sql);
        $prev_round_winner_id = $prev_round_winner_result->fetch_assoc()['winner_player_id'];
        
        // Get winner username
        $winner_username = '';
        foreach ($players as $player) {
            if ($player['id'] == $prev_round_winner_id) {
                $winner_username = $player['username'];
                break;
            }
        }
        ?>
        
        <div class="text-center mb-4">
            <span class="bg-yellow-700 px-3 py-1 rounded text-sm">
                Round <?php echo $prev_round; ?> Winner: <?php echo $winner_username === $current_user ? 'You' : htmlspecialchars($winner_username); ?>
            </span>
        </div>
        
        <div class="flex flex-wrap justify-center gap-2 md:gap-1">
            <?php foreach ($prev_round_cards as $card): ?>
            <div class="relative">
                <div class="card played-card md:w-[80px] md:h-[112px]">
                    <div class="card-inner">
                        <div class="card-front" style="color: <?php echo getCardColor($card['suit']) === 'red' ? '#e53e3e' : '#2d3748'; ?>">
                            <div class="flex justify-between">
                                <div class="card-value"><?php echo getCardValueDisplay($card['value']); ?></div>
                                <div class="card-suit"><?php echo getCardSymbol($card['suit']); ?></div>
                            </div>
                            <div class="card-center">
                                <?php echo getCardSymbol($card['suit']); ?>
                            </div>
                            <div class="flex justify-between">
                                <div class="card-suit"><?php echo getCardSymbol($card['suit']); ?></div>
                                <div class="card-value"><?php echo getCardValueDisplay($card['value']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-10 left-0 right-0 text-center">
                    <div class="text-xs bg-gray-700 px-2 py-1 rounded-full mb-1">
                        <?php echo htmlspecialchars($card['username']); ?>
                    </div>
                    <div class="text-xs bg-gray-900 px-2 py-1 rounded-full">
                        Played <?php echo $card['move_order']; ?><?php echo getOrdinalSuffix($card['move_order']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Chat Section (Side by side with Last Round Cards) -->
    <div class="chat-container p-4 bg-gray-800 rounded-lg md:w-1/2">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-bold">Game Chat</h3>
            <div class="chat-close md:hidden">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
        
        <div class="chat-messages p-3 bg-gray-900 rounded-lg mb-3" id="chatMessages" style="height: 200px; max-height: 200px;">
            <!-- Messages will be loaded here -->
            <div class="text-center text-gray-500 text-sm py-2">Loading messages...</div>
        </div>
        
        <form id="chatForm" class="flex gap-2">
            <div class="relative flex-grow">
                <input type="text" id="messageInput" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type a message...">
                <div id="mentionDropdown" class="mention-dropdown hidden"></div>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
                        
                        <!-- Player's Hand -->
                        <div class="bg-gray-800 rounded-lg p-4 mb-6 <?php echo ($current_player_number == $current_user_player_number && !$game_over) ? 'player-area active' : 'player-area'; ?>">
                            <h2 class="text-xl font-bold mb-3">Your Hand</h2>
                            
                            <?php if (count($hand_cards) > 0): ?>
                            <div class="flex flex-wrap justify-center">
                                <?php foreach ($hand_cards as $card): ?>
                                <form method="post" action="playroom.php?code=<?php echo $game_code; ?>" class="inline-block">
                                    <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                    <button type="submit" name="play_card" class="card <?php echo ($current_player_number == $current_user_player_number && !$game_over) ? 'playable' : ''; ?>" <?php echo ($current_player_number != $current_user_player_number || $game_over) ? 'disabled' : ''; ?>>
                                        <div class="card-inner">
                                            <div class="card-front" style="color: <?php echo getCardColor($card['suit']) === 'red' ? '#e53e3e' : '#2d3748'; ?>">
                                                <div class="flex justify-between">
                                                    <div class="card-value"><?php echo getCardValueDisplay($card['value']); ?></div>
                                                    <div class="card-suit"><?php echo getCardSymbol($card['suit']); ?></div>
                                                </div>
                                                <div class="card-center">
                                                    <?php echo getCardSymbol($card['suit']); ?>
                                                </div>
                                                <div class="flex justify-between">
                                                    <div class="card-suit"><?php echo getCardSymbol($card['suit']); ?></div>
                                                    <div class="card-value"><?php echo getCardValueDisplay($card['value']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </form>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-gray-300 italic">
                                You have no cards left.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Other Players -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            <?php foreach ($players as $player): ?>
                            <?php if ($player['id'] != $current_user_player_id): ?>
                            <div class="bg-gray-800 rounded-lg p-4 <?php echo ($current_player_number == $player['player_number'] && !$game_over) ? 'player-area active' : 'player-area'; ?>">
                                <h3 class="font-bold mb-2">
                                    <?php echo getPlayerNameWithTeam($player, $current_user); ?>
                                    <span class="text-sm font-normal text-gray-400">
                                        (<?php echo $player['points']; ?> pts)
                                    </span>
                                </h3>
                                
                                <?php
                                // Get player's cards (only count, not actual cards)
                                $player_cards_sql = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND player_id = {$player['id']} AND status = 'hand'";
                                $player_cards_result = $conn->query($player_cards_sql);
                                $player_card_count = $player_cards_result->fetch_assoc()['count'];
                                ?>
                                
                                <div class="flex flex-wrap justify-center">
                                    <?php for ($i = 0; $i < $player_card_count; $i++): ?>
                                    <div class="card hidden-card">
                                        <div class="card-inner">
                                            <div class="card-front">
                                                <!-- Card front (never shown) -->
                                            </div>
                                            <div class="card-back">
                                                <!-- Card back design -->
                                            </div>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                    
                                    <?php if ($player_card_count == 0): ?>
                                    <div class="text-center text-gray-300 italic w-full">
                                        No cards left.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                     <!-- Previous Rounds -->
<?php if (count($played_cards) > 0): ?>
<div class="bg-gray-800 rounded-lg p-4 mb-6">
    <h2 class="text-xl font-bold mb-3">Previous Rounds</h2>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-gray-700 rounded-lg overflow-hidden">
            <thead>
                <tr class="bg-gray-600">
                    <th class="px-4 py-2 text-left">Round</th>
                    <th class="px-4 py-2 text-left">Cards Played</th>
                    <th class="px-4 py-2 text-left hidden md:table-cell">Winner</th>
                    <th class="px-4 py-2 text-right">Points</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grouped_cards = [];
                foreach ($played_cards as $card) {
                    $round = $card['round_number'];
                    if (!isset($grouped_cards[$round])) {
                        $grouped_cards[$round] = [
                            'cards' => [],
                            'winner_id' => $card['winner_player_id'],
                            'round' => $round
                        ];
                    }
                    $grouped_cards[$round]['cards'][] = $card;
                }
                
                // Sort by round in descending order
                krsort($grouped_cards);
                
                foreach ($grouped_cards as $round => $data): 
                    $winner_name = '';
                    foreach ($players as $player) {
                        if ($player['id'] == $data['winner_id']) {
                            $winner_name = $player['username'];
                            break;
                        }
                    }
                    
                    // Calculate points for this round
                    $round_points = 0;
                    foreach ($data['cards'] as $card) {
                        $round_points += getCardPointValue($card['value']);
                    }
                ?>
                <tr class="border-b border-gray-600">
                    <td class="px-4 py-3"><?php echo $round; ?></td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            <?php 
                            // Get the move info for all cards in this round
                            $move_info_sql = "SELECT m.card_id, m.player_id, m.move_time, 
                                             p.username, c.suit, c.value
                                             FROM game_moves m
                                             JOIN game_players p ON m.player_id = p.id
                                             JOIN game_cards c ON m.card_id = c.id
                                             WHERE m.game_id = $game_id AND m.round = $round
                                             ORDER BY m.move_time ASC";
                            $move_info_result = $conn->query($move_info_sql);
                            
                            $move_cards = [];
                            $play_order = 1;
                            
                            // Organize move info for display
                            while ($move = $move_info_result->fetch_assoc()) {
                                $move['play_order'] = $play_order++;
                                $move_cards[] = $move;
                            }
                            
                            foreach ($move_cards as $card): 
                            ?>
                            <div class="inline-flex items-center bg-gray-800 px-2 py-1 rounded text-xs">
                                <span class="<?php echo getCardColor($card['suit']) === 'red' ? 'text-red-500' : 'text-white'; ?> mr-1">
                                    <?php echo getCardSymbol($card['suit']); ?>
                                </span>
                                <?php echo getCardValueDisplay($card['value']); ?>
                                <span class="ml-1 text-gray-400 hidden sm:inline">(<?php echo htmlspecialchars($card['username']); ?> - <?php echo $card['play_order']; ?><?php echo getOrdinalSuffix($card['play_order']); ?>)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <?php echo $winner_name === $current_user ? 'You' : htmlspecialchars($winner_name); ?>
                    </td>
                    <td class="px-4 py-3 text-right font-bold">
                        +<?php echo $round_points; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
</div>