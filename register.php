<?php
session_start();
include 'db.php';

$message = '';
$message_class = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $message = "Username and password are required.";
        $message_class = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_class = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_class = "error";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $message = "Username already exists. Please choose another.";
                $message_class = "error";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                
                if ($stmt->execute([$username, $hashed_password])) {
                    $message = "Registration successful! You can now login.";
                    $message_class = "success";
                    
                    // Auto-login after registration
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $message = "Registration failed. Please try again.";
                    $message_class = "error";
                }
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_class = "error";
        }
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
    <title>Register - Blood Bank</title>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-user-plus"></i> Register</h1>
        </div>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        </nav>
    </header>
    <main>
        <div class="auth-container">
            <div class="auth-image">
                <img src="assets/images/background/bd2.jpg" alt="Blood Donation">
            </div>
            <div class="auth-form">
                <div class="form-icon"><i class="fas fa-user-plus"></i></div>
                <h3>Create a New Account</h3>
                
                <?php if (!empty($message)): ?>
                    <div class="<?php echo $message_class; ?>">
                        <i class="fas <?php echo $message_class == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="registration-form">
                    <label for="username"><i class="fas fa-user"></i> Username:</label>
                    <input type="text" id="username" name="username" required>
                    
                    <label for="password"><i class="fas fa-lock"></i> Password:</label>
                    <input type="password" id="password" name="password" required>
                    
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    
                    <button type="submit"><i class="fas fa-user-plus"></i> Register</button>
                </form>
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>