<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'mc_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// Update session with latest user data using prepared statement
if(isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT coins, rating_avg, role, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if($user = $result->fetch_assoc()) {
        $_SESSION['coins'] = $user['coins'];
        $_SESSION['rating_avg'] = $user['rating_avg'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
    }
    $stmt->close();
}

// Function to safely get user data
function getUserData($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to validate trade
function validateTrade($conn, $trade_id, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM trades WHERE id = ? AND (from_user = ? OR to_user = ?)");
    $stmt->bind_param("iii", $trade_id, $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>