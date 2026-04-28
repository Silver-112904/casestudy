<?php
require 'db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
$user_id = $_SESSION['user_id'];

// Helper function for JSON responses
function sendJsonResponse($success, $message, $redirect = null) {
    if($redirect) {
        header("Location: $redirect&msg=" . urlencode($message));
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
    }
    exit;
}

// ========== ACCEPT TRADE (with validation) ==========
if(isset($_GET['accept'])) {
    $trade_id = (int)$_GET['accept'];
    
    // Validate trade exists and user is recipient
    $stmt = $conn->prepare("SELECT * FROM trades WHERE id = ? AND to_user = ? AND status = 'pending'");
    $stmt->bind_param("ii", $trade_id, $user_id);
    $stmt->execute();
    $trade = $stmt->get_result()->fetch_assoc();
    
    if(!$trade) {
        header("Location: dashboard.php?error=invalid_trade");
        exit;
    }
    
    // Validate coin balance for the offerer
    if($trade['coin_diff'] > 0) {
        $stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->bind_param("i", $trade['from_user']);
        $stmt->execute();
        $from_user = $stmt->get_result()->fetch_assoc();
        
        if($from_user['coins'] < $trade['coin_diff']) {
            header("Location: dashboard.php?error=insufficient_coins");
            exit;
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update trade status
        $stmt = $conn->prepare("UPDATE trades SET status = 'completed' WHERE id = ?");
        $stmt->bind_param("i", $trade_id);
        $stmt->execute();
        
        // Mark items as traded
        $stmt = $conn->prepare("UPDATE items SET status = 'traded' WHERE id = ?");
        $stmt->bind_param("i", $trade['item1_id']);
        $stmt->execute();
        
        if($trade['item2_id']) {
            $stmt = $conn->prepare("UPDATE items SET status = 'traded' WHERE id = ?");
            $stmt->bind_param("i", $trade['item2_id']);
            $stmt->execute();
        }
        
        // Transfer coins if any
        if($trade['coin_diff'] > 0) {
            $stmt = $conn->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
            $stmt->bind_param("ii", $trade['coin_diff'], $trade['from_user']);
            $stmt->execute();
            
            $stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
            $stmt->bind_param("ii", $trade['coin_diff'], $trade['to_user']);
            $stmt->execute();
        }
        
        $conn->commit();
        
        // Update both users' sessions
        $stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['coins'] = $stmt->get_result()->fetch_assoc()['coins'];
        
        header("Location: dashboard.php?msg=accepted");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: dashboard.php?error=trade_failed");
    }
    exit;
}

// ========== DECLINE TRADE ==========
if(isset($_GET['decline'])) {
    $trade_id = (int)$_GET['decline'];
    
    $stmt = $conn->prepare("UPDATE trades SET status = 'declined' WHERE id = ? AND to_user = ?");
    $stmt->bind_param("ii", $trade_id, $user_id);
    $stmt->execute();
    
    header("Location: dashboard.php?msg=declined");
    exit;
}

// ========== ACCEPT COUNTER OFFER ==========
if(isset($_GET['accept_counter']) && isset($_GET['trade_id'])) {
    $counter_id = (int)$_GET['accept_counter'];
    $trade_id = (int)$_GET['trade_id'];
    
    // Get counter offer with validation
    $stmt = $conn->prepare("SELECT * FROM offers WHERE id = ? AND to_user = ? AND status = 'pending'");
    $stmt->bind_param("ii", $counter_id, $user_id);
    $stmt->execute();
    $counter = $stmt->get_result()->fetch_assoc();
    
    if(!$counter) {
        header("Location: dashboard.php?error=counter_not_found");
        exit;
    }
    
    // Get trade details
    $stmt = $conn->prepare("SELECT * FROM trades WHERE id = ?");
    $stmt->bind_param("i", $trade_id);
    $stmt->execute();
    $trade = $stmt->get_result()->fetch_assoc();
    
    // Validate coin balance
    if($counter['offered_coins'] > 0) {
        $stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->bind_param("i", $trade['from_user']);
        $stmt->execute();
        $from_user = $stmt->get_result()->fetch_assoc();
        
        if($from_user['coins'] < $counter['offered_coins']) {
            header("Location: dashboard.php?error=insufficient_coins");
            exit;
        }
    }
    
    $conn->begin_transaction();
    
    try {
        // Update trade
        $stmt = $conn->prepare("UPDATE trades SET coin_diff = ?, status = 'completed' WHERE id = ?");
        $stmt->bind_param("ii", $counter['offered_coins'], $trade_id);
        $stmt->execute();
        
        // Mark counter as accepted
        $stmt = $conn->prepare("UPDATE offers SET status = 'accepted' WHERE id = ?");
        $stmt->bind_param("i", $counter_id);
        $stmt->execute();
        
        // Mark items as traded
        $stmt = $conn->prepare("UPDATE items SET status = 'traded' WHERE id = ?");
        $stmt->bind_param("i", $trade['item1_id']);
        $stmt->execute();
        
        if($trade['item2_id']) {
            $stmt = $conn->prepare("UPDATE items SET status = 'traded' WHERE id = ?");
            $stmt->bind_param("i", $trade['item2_id']);
            $stmt->execute();
        }
        
        // Transfer coins
        if($counter['offered_coins'] > 0) {
            $stmt = $conn->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
            $stmt->bind_param("ii", $counter['offered_coins'], $trade['from_user']);
            $stmt->execute();
            
            $stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
            $stmt->bind_param("ii", $counter['offered_coins'], $trade['to_user']);
            $stmt->execute();
        }
        
        $conn->commit();
        
        // Update session
        $stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['coins'] = $stmt->get_result()->fetch_assoc()['coins'];
        
        header("Location: dashboard.php?msg=counter_accepted");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: dashboard.php?error=trade_failed");
    }
    exit;
}

// ========== DECLINE COUNTER OFFER ==========
if(isset($_GET['decline_counter'])) {
    $counter_id = (int)$_GET['decline_counter'];
    
    $stmt = $conn->prepare("UPDATE offers SET status = 'declined' WHERE id = ? AND to_user = ?");
    $stmt->bind_param("ii", $counter_id, $user_id);
    $stmt->execute();
    
    header("Location: dashboard.php?msg=counter_declined");
    exit;
}

// ========== COUNTER OFFER ==========
if(isset($_GET['counter'])) {
    $trade_id = (int)$_GET['counter'];
    
    // Validate trade exists and user is recipient
    $stmt = $conn->prepare("SELECT * FROM trades WHERE id = ? AND to_user = ? AND status = 'pending'");
    $stmt->bind_param("ii", $trade_id, $user_id);
    $stmt->execute();
    $trade = $stmt->get_result()->fetch_assoc();
    
    if(!$trade) {
        header("Location: dashboard.php?error=invalid_trade");
        exit;
    }
    
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_coin_diff = (int)$_POST['new_coin_amount'];
        $counter_message = $_POST['counter_message'];
        
        // Validate coin amount is not negative
        if($new_coin_diff < 0) {
            $error = "Coin amount cannot be negative";
        } else {
            // Insert counter offer with to_user field
            $stmt = $conn->prepare("INSERT INTO offers (trade_id, from_user, to_user, offered_coins, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iiiss", $trade_id, $user_id, $trade['from_user'], $new_coin_diff, $counter_message);
            
            if($stmt->execute()) {
                $stmt = $conn->prepare("UPDATE trades SET status = 'countered' WHERE id = ?");
                $stmt->bind_param("i", $trade_id);
                $stmt->execute();
                header("Location: dashboard.php?msg=counter_sent");
                exit;
            } else {
                $error = "Failed to send counter offer";
            }
        }
    }
    
    // Display counter offer form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Counter Offer | Mythic Market</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
            body { background: linear-gradient(135deg, #0a0f1e 0%, #0c1222 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
            .card { background: rgba(15,23,42,0.95); backdrop-filter: blur(12px); border-radius: 28px; padding: 36px; max-width: 550px; width: 100%; border: 1px solid rgba(71,85,105,0.4); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
            h2 { color: white; margin-bottom: 8px; font-size: 28px; }
            .item-preview { background: rgba(59,130,246,0.1); padding: 20px; border-radius: 20px; margin: 24px 0; border: 1px solid rgba(59,130,246,0.2); }
            label { color: #94a3b8; display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; }
            input, textarea { width: 100%; background: rgba(0,0,0,0.4); border: 1px solid #334155; padding: 12px 16px; border-radius: 14px; color: white; margin-bottom: 20px; font-size: 14px; transition: all 0.2s; }
            input:focus, textarea:focus { outline: none; border-color: #3b82f6; background: rgba(0,0,0,0.6); }
            .btn { background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; padding: 14px; border-radius: 40px; color: white; font-weight: 600; cursor: pointer; width: 100%; font-size: 16px; transition: transform 0.2s; }
            .btn:hover { transform: translateY(-2px); }
            .back { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px; color: #94a3b8; text-decoration: none; font-size: 14px; transition: color 0.2s; }
            .back:hover { color: white; }
            .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
            .badge { display: inline-block; padding: 4px 12px; border-radius: 40px; font-size: 12px; font-weight: 600; margin-left: 8px; }
            .badge-sell { background: rgba(34,197,94,0.2); color: #4ade80; }
            .badge-swap { background: rgba(168,85,247,0.2); color: #c084fc; }
        </style>
    </head>
    <body>
        <div class="card">
            <a href="dashboard.php" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h2>💰 Counter Offer</h2>
            <p style="color: #94a3b8; margin-bottom: 8px;">Propose new terms for this trade</p>
            
            <div class="item-preview">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="font-size: 16px;"><?php echo htmlspecialchars($trade['item1_id']); ?></strong>
                    <span class="badge badge-<?php echo $trade['type']; ?>"><?php echo ucfirst($trade['type']); ?></span>
                </div>
                <?php if($trade['coin_diff'] > 0): ?>
                    <p style="color: #fbbf24; margin-top: 8px;"><i class="fas fa-coins"></i> Original offer: <?php echo $trade['coin_diff']; ?> MC</p>
                <?php endif; ?>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <label><i class="fas fa-coins"></i> Your Coin Offer (MC)</label>
                <input type="number" name="new_coin_amount" value="<?php echo $trade['coin_diff']; ?>" min="0" required>
                <label><i class="fas fa-comment"></i> Message (Optional)</label>
                <textarea name="counter_message" rows="3" placeholder="Add a note to explain your counter offer..."></textarea>
                <button type="submit" class="btn">Send Counter Offer</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ========== INITIATE TRADE (with validation) ==========
if(isset($_GET['initiate'])) {
    $item_id = (int)$_GET['initiate'];
    
    // Validate item exists and is active
    $stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    
    if(!$item || $item['user_id'] == $user_id) {
        header("Location: marketplace.php?error=cannot_trade");
        exit;
    }
    
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $coin_offer = (int)$_POST['coin_amount'];
        $message = $_POST['message'] ?? '';
        
        // Validate coin offer
        if($coin_offer < 0) {
            $error = "Coin offer cannot be negative";
        } elseif($item['type'] == 'sell' && $item['price'] && $coin_offer < $item['price']) {
            $error = "Offer must be at least " . $item['price'] . " MC";
        } else {
            // Check if user has enough coins for the offer
            if($coin_offer > $_SESSION['coins']) {
                $error = "You don't have enough coins. You have " . $_SESSION['coins'] . " MC";
            } else {
                $stmt = $conn->prepare("INSERT INTO trades (item1_id, from_user, to_user, type, coin_diff, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iiisi", $item_id, $user_id, $item['user_id'], $item['type'], $coin_offer);
                
                if($stmt->execute()) {
                    header("Location: dashboard.php?msg=offer_sent");
                    exit;
                } else {
                    $error = "Failed to send offer";
                }
            }
        }
    }
    
    // Get user's items for potential swap (but simplified for now)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Initiate Trade | Mythic Market</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
            body { background: linear-gradient(135deg, #0a0f1e 0%, #0c1222 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
            .card { background: rgba(15,23,42,0.95); backdrop-filter: blur(12px); border-radius: 28px; padding: 36px; max-width: 600px; width: 100%; border: 1px solid rgba(71,85,105,0.4); }
            h2 { color: white; margin-bottom: 8px; font-size: 28px; }
            .item-preview { background: rgba(59,130,246,0.1); padding: 20px; border-radius: 20px; margin: 24px 0; border: 1px solid rgba(59,130,246,0.2); display: flex; gap: 16px; align-items: center; }
            .item-info { flex: 1; }
            label { color: #94a3b8; display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; }
            input, textarea { width: 100%; background: rgba(0,0,0,0.4); border: 1px solid #334155; padding: 12px 16px; border-radius: 14px; color: white; margin-bottom: 20px; font-size: 14px; }
            input:focus, textarea:focus { outline: none; border-color: #3b82f6; }
            .btn { background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; padding: 14px; border-radius: 40px; color: white; font-weight: 600; cursor: pointer; width: 100%; font-size: 16px; }
            .back { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px; color: #94a3b8; text-decoration: none; }
            .back:hover { color: white; }
            .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 20px; }
            .badge { padding: 4px 12px; border-radius: 40px; font-size: 12px; display: inline-block; margin-top: 8px; }
            .badge-sell { background: rgba(34,197,94,0.2); color: #4ade80; }
            .balance { background: rgba(245,158,11,0.1); padding: 8px 16px; border-radius: 12px; margin-top: 16px; font-size: 14px; color: #fbbf24; text-align: center; }
        </style>
    </head>
    <body>
        <div class="card">
            <a href="marketplace.php" class="back"><i class="fas fa-arrow-left"></i> Back to Marketplace</a>
            <h2>📨 Initiate Trade</h2>
            <p style="color: #94a3b8;">Make an offer for this item</p>
            
            <div class="item-preview">
                <div class="item-info">
                    <strong style="font-size: 18px;"><?php echo htmlspecialchars($item['title']); ?></strong>
                    <span class="badge badge-<?php echo $item['type']; ?>"><?php echo ucfirst($item['type']); ?></span>
                    <?php if($item['price']): ?>
                        <p style="color: #fbbf24; margin-top: 8px;"><i class="fas fa-tag"></i> Asking price: <?php echo $item['price']; ?> MC</p>
                    <?php endif; ?>
                    <p style="color: #94a3b8; font-size: 13px; margin-top: 8px;"><i class="fas fa-user"></i> Seller: <?php echo htmlspecialchars($item['username'] ?? 'Unknown'); ?></p>
                </div>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <label><i class="fas fa-coins"></i> Your Coin Offer (MC)</label>
                <input type="number" name="coin_amount" placeholder="Enter amount" value="<?php echo $item['price'] ?: 0; ?>" min="0" required>
                <label><i class="fas fa-comment"></i> Message (Optional)</label>
                <textarea name="message" rows="3" placeholder="Add a note to the seller..."></textarea>
                <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Send Offer</button>
            </form>
            
            <div class="balance">
                <i class="fas fa-wallet"></i> Your balance: <?php echo number_format($_SESSION['coins']); ?> MC
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ========== DELETE ITEM ==========
if(isset($_GET['delete_item'])) {
    $item_id = (int)$_GET['delete_item'];
    
    $stmt = $conn->prepare("UPDATE items SET status = 'removed' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    
    header("Location: inventory.php");
    exit;
}

header("Location: dashboard.php");
?>