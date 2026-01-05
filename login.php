<?php
require 'includes/db.php';
session_start();

$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    $query = "SELECT * FROM user WHERE username='$username'";
    $result = mysqli_query($con, $query);

    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            echo "Login Successful!";
            header("Location: index.php");
            exit;
        } else {
            $message = "Incorrect Password";
        }
    } else {
        $message = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="/my-website/assets/styles.css">
</head>
<body>
    <img src="/my-website/assets/jbudget.PNG" alt="Logo" class="logo">
    <div class="center" style="height: 375px;">
        <h1>Login</h1>

        <form action="login.php" method="POST">
            <div class="text_field">
                <input type="text" name="username" placeholder="Username" required>   
                <span> </span>
            </div>
            <div class="text_field">
                <input type="password" name="password" placeholder="Password" required>
                <span> </span>
            </div>
            <div class="submit">
                <button type="submit" name="login">Login</button>
            </div>
        </form>
        <div class="credentials">
            <p>Don't have an account? <a href="register.php">Create an account</a></p>
        </div>
    </div>
</body>
</html>