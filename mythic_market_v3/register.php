<?php require 'db.php';
if(isset($_SESSION['user_id'])) header("Location: index.php");

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    
    if($stmt->execute()) {
        echo "<div class='alert success'>✅ Registered! <a href='login.php'>Login here</a></div>";
    } else {
        echo "<div class='alert error'>❌ Username or email taken.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Register</title><link rel="stylesheet" href="./assets/css/style.css"></head>
<body>
<div class="container">
    <form method="POST">
        <h2>Join Mythic Market</h2>
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn">Register</button>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
</div>
</body>
</html>