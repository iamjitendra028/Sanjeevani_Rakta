<?php
session_start();
include 'db.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                header("Location: $redirect");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Login - Blood Bank</title>
    <style>
        .auth-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-image img {
            max-width: 100%;
            border-radius: 8px;
        }

        .auth-form {
            max-width: 400px;
            margin-left: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .auth-form h3 {
            margin-top: 0;
            text-align: center;
        }

        .auth-form label {
            display: block;
            margin-top: 10px;
        }

        .auth-form input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .auth-form button {
            width: 100%;
            padding: 10px;
            background-color: #35424a;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .auth-form button:hover {
            background-color: #2c3e50;
        }

        .auth-form .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
<header>
    <div class="logo-container">
        <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
        <h1><i class="fas fa-sign-in-alt"></i> Login</h1>
    </div>
    <nav>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
    </nav>
</header>
<main>
    <div class="auth-container">
        <div class="auth-image">
            <img src="assets/images/background/bd3.jpg" alt="Blood Donation">
        </div>
        <div class="auth-form">
            <div class="form-icon"><i class="fas fa-user-circle"></i></div>
            <h3>Login to Your Account</h3>
            
            <?php if (!empty($error_message)): ?>
                <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <label for="username"><i class="fas fa-user"></i> Username:</label>
                <input type="text" id="username" name="username" required>
                
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <input type="password" id="password" name="password" required>
                
                <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</main>
<script src="assets/js/script.js"></script>
</body>
</html>