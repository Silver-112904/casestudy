<?php require 'db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

$user_id = $_GET['id'] ?? $_SESSION['user_id'];
$profile = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$ratings = $conn->query("SELECT ratings.*, users.username FROM ratings JOIN users ON ratings.rater_id = users.id WHERE rated_id = $user_id");
?>

<!DOCTYPE html>
<html>
<head><title><?php echo htmlspecialchars($profile['username']); ?> | Profile</title><link rel="stylesheet" href="./assets/css/style.css"></head>
<body>
<nav class="navbar"><a href="javascript:history.back()" class="back-button"><i class="fas fa-arrow-left"></i> Back</a></nav>

<div class="container">
    <div class="glass-card" style="text-align: center;">
        <h1>👤 <?php echo htmlspecialchars($profile['username']); ?></h1>
        <p>🪙 <?php echo $profile['coins']; ?> Mythic Coins</p>
        <p>⭐ Rating: <?php echo $profile['rating_avg'] ?: 'No ratings yet'; ?> / 5</p>
        <p>📅 Member since: <?php echo date('F Y', strtotime($profile['created_at'])); ?></p>
    </div>
    
    <div class="glass-card" style="margin-top: 32px;">
        <h2>📝 Reviews & Ratings</h2>
        <?php while($rating = $ratings->fetch_assoc()): ?>
            <div style="border-bottom: 1px solid rgba(71,85,105,0.3); padding: 12px 0;">
                <strong><?php echo htmlspecialchars($rating['username']); ?></strong> rated ⭐ <?php echo $rating['score']; ?>/5
                <p><?php echo htmlspecialchars($rating['review']); ?></p>
            </div>
        <?php endwhile; ?>
    </div>
</div>
</body>
</html>