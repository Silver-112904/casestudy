<?php require 'db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
$user_id = $_SESSION['user_id'];

// Handle new listing
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $type = $_POST['type'];
    $price = $_POST['price'] ?: NULL;
    $rarity = $_POST['rarity'];
    $rarity_custom = ($rarity == 'Custom') ? $_POST['rarity_custom'] : NULL;
    $category_id = $_POST['category_id'];
    
    // Image upload (optional)
    $image_path = NULL;
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','gif'];
        $filename = time() . '_' . $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $filename);
            $image_path = 'uploads/' . $filename;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO items (user_id, title, description, type, price, rarity, rarity_custom, category_id, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssissss", $user_id, $title, $desc, $type, $price, $rarity, $rarity_custom, $category_id, $image_path);
    $stmt->execute();
    echo "<div class='alert success'>✅ Item listed!</div>";
}

$categories = $conn->query("SELECT * FROM categories");
$my_items = $conn->query("SELECT * FROM items WHERE user_id = $user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Inventory | Mythic Market</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>
<nav class="navbar"><a href="javascript:history.back()" class="back-button"><i class="fas fa-arrow-left"></i> Back to Home</a></nav>

<div class="container">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
        <!-- Create New Listing -->
        <div class="glass-card">
            <h2>➕ Create New Listing</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Item title" required>
                <textarea name="description" placeholder="Description" rows="3"></textarea>
                <select name="type" required>
                    <option value="sell">Sell (Mythic Coins)</option>
                    <option value="swap">Swap (Item ↔ Item)</option>
                    <option value="barter">Barter (Item + Coins)</option>
                </select>
                <input type="number" name="price" placeholder="Price in MC (leave empty for swap/barter)">
                <select name="rarity">
                    <option value="Common">Common</option>
                    <option value="Rare">Rare</option>
                    <option value="Epic">Epic</option>
                    <option value="Legendary">Legendary</option>
                    <option value="Custom">Custom</option>
                </select>
                <input type="text" name="rarity_custom" placeholder="Custom rarity name (if selected)">
                <select name="category_id">
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="file" name="image" accept="image/*">
                <button type="submit" name="create" class="btn">List Item</button>
            </form>
        </div>
        
        <!-- My Current Items -->
        <div class="glass-card">
            <h2>📦 My Items</h2>
            <?php while($item = $my_items->fetch_assoc()): ?>
                <div style="border-bottom: 1px solid rgba(71,85,105,0.3); padding: 16px 0;">
                    <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                    <span class="badge badge-<?php echo $item['type']; ?>"><?php echo ucfirst($item['type']); ?></span>
                    <p>Status: <?php echo $item['status']; ?></p>
                    <a href="trade_handler.php?delete_item=<?php echo $item['id']; ?>" style="color: #ef4444;">Delete</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
</body>
</html>