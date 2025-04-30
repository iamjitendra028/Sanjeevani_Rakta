<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect without selecting a database first
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS blood_bank");
    echo "<p>Database 'blood_bank' checked/created successfully</p>";
    
    // Select the database
    $pdo->exec("USE blood_bank");
    
    // Drop existing tables if they exist to avoid foreign key constraints
    $pdo->exec("DROP TABLE IF EXISTS blood_inventory");
    $pdo->exec("DROP TABLE IF EXISTS donation_records");
    $pdo->exec("DROP TABLE IF EXISTS donors");
    $pdo->exec("DROP TABLE IF EXISTS blood_requests");
    $pdo->exec("DROP TABLE IF EXISTS users");
    echo "<p>Removed any existing tables to rebuild them correctly</p>";
    
    // Create users table with all required fields
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'donor', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>Table 'users' created successfully</p>";
    
    // Create donors table
    $sql = "CREATE TABLE donors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        name VARCHAR(100) NOT NULL,
        blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        city VARCHAR(100) NOT NULL DEFAULT 'Unknown',
        address VARCHAR(255),
        last_donation_date DATE,
        is_available BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "<p>Table 'donors' created successfully</p>";
    
    // Create blood_requests table
    $sql = "CREATE TABLE blood_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
        units_needed INT NOT NULL DEFAULT 1,
        contact VARCHAR(100) NOT NULL,
        hospital VARCHAR(255),
        city VARCHAR(100),
        patient_name VARCHAR(100),
        relationship ENUM('self','family','friend','medical_staff','other'),
        medical_reason VARCHAR(255),
        urgency ENUM('normal','urgent','critical') DEFAULT 'normal',
        status ENUM('pending','fulfilled','cancelled') DEFAULT 'pending',
        request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "<p>Table 'blood_requests' created successfully</p>";
    
    // Create blood_inventory table
    $sql = "CREATE TABLE blood_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
        units INT NOT NULL DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>Table 'blood_inventory' created successfully</p>";
    
    // Create donation_records table
    $sql = "CREATE TABLE donation_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        donor_id INT,
        blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
        donation_date DATE NOT NULL,
        status ENUM('pending','accepted','rejected') DEFAULT 'pending',
        units INT NOT NULL DEFAULT 1,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "<p>Table 'donation_records' created successfully</p>";
    
    // Create default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute(['admin', $admin_password]);
    echo "<p>Default admin user created (Username: admin, Password: admin123)</p>";
    
    // Initialize blood inventory with zero units for each type
    $blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    foreach ($blood_types as $type) {
        $stmt = $pdo->prepare("INSERT INTO blood_inventory (blood_type, units) VALUES (?, 0)");
        $stmt->execute([$type]);
    }
    echo "<p>Blood inventory initialized</p>";
    
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #d4edda; border-radius: 5px;'>
            Database setup completed successfully! <br>
            <a href='register.php'>Register a new account</a> or <a href='index.php'>Go to homepage</a>
          </div>";
    
} catch (PDOException $e) {
    die("<div style='color: red; margin-top: 20px;'>Database setup failed: " . $e->getMessage() . "</div>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Blood Bank</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/logo.png" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-database"></i> Database Setup</h1>
        </div>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </nav>
    </header>
    <script src="assets/js/script.js"></script>
</body>
</html>