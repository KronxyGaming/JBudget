<?php
    require 'includes/db.php';
    session_start();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
        $username = mysqli_real_escape_string($con, $_POST['username']);
        $password = mysqli_real_escape_string($con, $_POST['password']);
        $email = mysqli_real_escape_string($con, $_POST['email']);
        $phone_number = mysqli_real_escape_string($con, $_POST['phone_number']);
    
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check = "SELECT * FROM user WHERE username = '$username'";
        $result = mysqli_query($con, $check);
        if(mysqli_num_rows($result) > 0) {
            echo "Username already taken. Please choose another.";
        } else {
            $query = "INSERT INTO user (username, password, email, phone_number)
              VALUES ('$username', '$hashed_password', '$email', '$phone_number')";
        
            if(mysqli_query($con, $query)) {
                $_SESSION['username'] = $username;
                echo "Registered!";
                header("Location: index.php");
                exit;
            } else {
                echo "Error: " . mysqli_error($con);
            }
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register an Account</title>
    <link rel="stylesheet" href="/my-website/assets/styles.css">
</head>
<body>
    <img src="/my-website/assets/jbudget.PNG" alt="Logo" class="logo">
    <div class="center">
        <h1>Welcome, Please Register</h1>

        <form action="register.php" method="POST">
            <div class="text_field">
                <input type="text" name="username" placeholder="Username" required>
                <span> </span>
            </div>
            <div class="text_field">
                <input type="password" name="password" placeholder="Password" required>
                <span> </span>
            </div>
            <div class="text_field">
                <input type="text" name="email" placeholder="Email" required>
                <span> </span>
            </div>
            <div class="text_field">
                <input type="text" name="phone_number" placeholder="Phone Number">
                <span> </span>
            </div>
            <div class="submit">
                <button type="submit" name="register">Register</button>
            </div>
        </form>

        <div class="credentials">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
</body>
</html>