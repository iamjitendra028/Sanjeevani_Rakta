<?php
session_start();
include 'db.php';

// Redirect admin users directly to admin dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

try {
    // Get blood inventory statistics
    $stmt = $pdo->prepare("SELECT blood_type, COUNT(*) as count FROM donors GROUP BY blood_type ORDER BY blood_type");
    $stmt->execute();
    $blood_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get cities with donors
    $stmt = $pdo->prepare("SELECT DISTINCT city FROM donors ORDER BY city LIMIT 10");
    $stmt->execute();
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Blood Bank Management System</title>
    <style>
        .welcome-box {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .feature-item {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
        }
        
        .feature-item h3 {
            margin-top: 0;
        }
        
        .feature-item a {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #35424a;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .feature-item a:hover {
            background-color: #2c3e50;
        }
        
        /* New styles for hero banner */
        .hero-banner {
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/background/bd4.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 80px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .hero-banner h2 {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .hero-banner p {
            font-size: 1.2em;
            max-width: 700px;
            margin: 0 auto 30px;
        }
        
        .hero-button {
            display: inline-block;
            padding: 12px 25px;
            background-color:rgb(208, 53, 68);
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .hero-button:hover {
            background-color:rgb(245, 236, 237);
            transform: scale(1.05);
        }
        
        .hero-button.secondary {
            background-color: transparent;
            border: 2px solid white;
        }
        
        .hero-button.secondary:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        /* Icon styles */
        .icon {
            font-size: 3em;
            color:rgb(164, 120, 124);
            margin-bottom: 15px;
        }
        
        /* Logo styling */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo {
            width: 50px;
            margin-right: 15px;
        }
        
        /* Blood types section */
        .blood-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 40px;
            text-align: center;
        }
        
        .blood-type {
            background-color: white;
            border-radius: 8px;
            padding: 15px 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .blood-icon {
            font-size: 2em;
            color: #dc3545;
            margin-bottom: 10px;
        }
        
        .blood-type h4 {
            margin: 10px 0 5px;
        }
        
        .blood-type p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .city-chips {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        
        .city-chip {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            font-size: 0.9em;
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
            <h1 style="color: #dc3545;"><i class="fas fa-tint" style="color: #dc3545;"></i> Sanjeevani Rakta </h1>
        </div>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <!-- Admin navigation -->
                    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                    <a href="manage_stock.php"><i class="fas fa-warehouse"></i> Manage Stock</a>
                    <a href="manage_donations.php"><i class="fas fa-heartbeat"></i> Accept Donations</a>
                    <a href="manage_requests.php"><i class="fas fa-clipboard-list"></i> Manage Requests</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <!-- Regular user navigation -->
                    <a href="donor_register.php"><i class="fas fa-user-plus"></i> Register as Donor</a>
                    <a href="request.php"><i class="fas fa-tint"></i> Request Blood</a>
                    <a href="view_blood.php"><i class="fas fa-list"></i> View Donors</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <!-- Hero Banner with Background Image -->
        <div class="hero-banner">
            <h2>Donate Blood, Save Lives</h2>
            <p>Your donation can be the gift of life for someone in need. Join our community of donors and make a difference today.</p>
            <div>
                <a href="donor_register.php" class="hero-button"><i class="fas fa-heart"></i> Become a Donor</a>
                <a href="request.php" class="hero-button secondary"><i class="fas fa-tint"></i> Request Blood</a>
            </div>
        </div>
        
        <div class="welcome-box">
            <?php if (isset($_SESSION['username'])): ?>
                <h2><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p>Thank you for being part of our blood donation community.</p>
            <?php else: ?>
                <h2><i class="fas fa-hospital"></i> Welcome to the Blood Bank Management System</h2>
                <p>Please login or register to access all features.</p>
            <?php endif; ?>
        </div>
        
        <div class="feature-grid">
            <div class="feature-item">
                <div class="icon"><i class="fas fa-user-md"></i></div>
                <h3>Become a Donor</h3>
                <p>Register as a blood donor and help save lives in your community.</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="donor_register.php"><i class="fas fa-plus-circle"></i> Register Now</a>
                <?php else: ?>
                    <a href="login.php?redirect=donor_register.php"><i class="fas fa-sign-in-alt"></i> Login to Register</a>
                <?php endif; ?>
            </div>
            
            <div class="feature-item">
                <div class="icon"><i class="fas fa-tint"></i></div>
                <h3>Request Blood</h3>
                <p>Need blood for a patient? Submit a blood request here.</p>
                <a href="request.php"><i class="fas fa-paper-plane"></i> Request Blood</a>
            </div>
            
            <div class="feature-item">
                <div class="icon"><i class="fas fa-search"></i></div>
                <h3>Find Donors</h3>
                <p>Search for blood donors in your city.</p>
                <a href="view_blood.php"><i class="fas fa-list"></i> View Donors</a>
            </div>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="feature-item">
                    <div class="icon"><i class="fas fa-database"></i></div>
                    <h3>Setup Database</h3>
                    <p>Admin tool to set up or repair the database.</p>
                    <a href="setup_db.php"><i class="fas fa-wrench"></i> Setup Database</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Blood Types Section -->
        <h2 style="text-align: center; margin-top: 50px;"><i class="fas fa-tint"></i> Available Blood Types</h2>
        <div class="blood-types">
            <?php if (isset($blood_counts)): ?>
                <?php foreach ($blood_counts as $blood): ?>
                    <div class="blood-type">
                        <div class="blood-icon">
                            <<?php echo strtolower(str_replace('+', '-positive', str_replace('-', '-negative', $blood['blood_type']))); ?>
                                 alt="<?php echo $blood['blood_type']; ?>" width="50">
                        </div>
                        <h4><?php echo $blood['blood_type']; ?></h4>
                        <p><?php echo $blood['count']; ?> donors available</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="blood-type">
                    <h4>A+</h4>
                    <p>No donors yet</p>
                </div>
                <div class="blood-type">
                    <h4>A-</h4>
                    <p>No donors yet</p>
                </div>
                <div class="blood-type">
                    <h4>B+</h4>
                    <p>No donors yet</p>
                </div>
                <div class="blood-type">
                    <h4>B-</h4>
                    <p>No donors yet</p>
                </div>
                <div class="blood-type">
                    <h4>AB+</h4>
                    <p>No donors yet</p>
                </div>
                <div class="blood-type">
                    <h4>AB-</h4>
                    <p>No donors yet</p>
                </div>
                <div class="blood-type">
                    <h4>O+</h4>
                    <p>No donors yet</p>
                </div>
                <div class="blood-type">
                    <h4>O-</h4>
                    <p>No donors yet</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($cities) && count($cities) > 0): ?>
            <div style="margin-top: 40px; text-align: center;">
                <h3><i class="fas fa-map-marker-alt"></i> Cities with Blood Donors</h3>
                <div class="city-chips">
                    <?php foreach ($cities as $city): ?>
                        <div class="city-chip">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($city); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>