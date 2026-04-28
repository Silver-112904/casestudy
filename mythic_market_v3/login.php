<?php require 'db.php';
if(isset($_SESSION['user_id'])) header("Location: index.php");

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($user = $result->fetch_assoc()) {
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['coins'] = $user['coins'];
            header("Location: dashboard.php");
        } else echo "<div class='alert error'>Wrong password</div>";
    } else echo "<div class='alert error'>User not found</div>";
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title><link rel="stylesheet" href="./assets/css/style.css"></head>
<body>
<div class="container">
    <form method="POST">
        <h2>Login to Mythic Market</h2>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn">Login</button>
        <p>No account? <a href="register.php">Register here</a></p>
    </form>
</div>
</body>
</html>