<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=donor_register.php");
    exit();
}

$message = '';
$message_class = '';

// Check if user is already a donor
$is_donor = false;
$donor_id = null;

try {
    $check = $pdo->prepare("SELECT id FROM donors WHERE user_id = ?");
    $check->execute([$_SESSION['user_id']]);
    
    if ($check->rowCount() > 0) {
        $is_donor = true;
        $donor_id = $check->fetchColumn();
    }
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
    $message_class = "error";
}

// Handle donor registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_donor'])) {
    $name = trim($_POST['name']);
    $blood_type = $_POST['blood_type'];
    $phone = trim($_POST['phone']);
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $city = isset($_POST['city']) ? trim($_POST['city']) : 'Unknown';
    $last_donation_date = !empty($_POST['last_donation_date']) ? $_POST['last_donation_date'] : null;
    $user_id = $_SESSION['user_id'];
    
    try {
        if (!$is_donor) {
            // Insert the donor information
            $stmt = $pdo->prepare("INSERT INTO donors (name, blood_type, phone, email, city, last_donation_date, user_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $blood_type, $phone, $email, $city, $last_donation_date, $user_id])) {
                $donor_id = $pdo->lastInsertId(); 
                $is_donor = true;
                $message = "Thank you for registering as a donor!";
                $message_class = "success";
            } else {
                $message = "Error: Could not register as donor.";
                $message_class = "error";
            }
        } else {
            $message = "You are already registered as a donor.";
            $message_class = "error";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_class = "error";
    }
}

// Handle donation submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['donate_blood'])) {
    $donation_date = $_POST['donation_date'];
    $units = isset($_POST['units']) ? (int)$_POST['units'] : 1;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Donor information (if they're donating without being a registered donor)
    if (!$is_donor) {
        $message = "You must register as a donor before donating blood.";
        $message_class = "error";
    } else {
        try {
            // Get donor blood type
            $stmt = $pdo->prepare("SELECT blood_type FROM donors WHERE id = ?");
            $stmt->execute([$donor_id]);
            $blood_type = $stmt->fetchColumn();
            
            // Record the donation
            $stmt = $pdo->prepare("INSERT INTO donation_records (donor_id, blood_type, donation_date, units, notes) 
                                 VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$donor_id, $blood_type, $donation_date, $units, $notes])) {
                $message = "Thank you for your blood donation! An administrator will review and accept your donation.";
                $message_class = "success";
            } else {
                $message = "Error: Could not record donation.";
                $message_class = "error";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_class = "error";
        }
    }
}

// Get donor information if registered
if ($is_donor) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM donors WHERE id = ?");
        $stmt->execute([$donor_id]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM donation_records WHERE donor_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$donor_id]);
        $recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_class = "error";
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
    <title>Register as Donor - Blood Bank</title>
    <style>
        .container-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        
        .tab:hover {
            background-color: #e9ecef;
        }
        
        .tab.active {
            background-color: white;
            font-weight: bold;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .donation-record {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .donation-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .donor-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .donor-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-item i {
            width: 20px;
            color: #dc3545;
            margin-right: 5px;
        }
        
        .info-item strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.jpeg" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-user-plus"></i> <?php echo $is_donor ? 'Donor Dashboard' : 'Register as Blood Donor'; ?></h1>
        </div>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="view_blood.php"><i class="fas fa-tint"></i> View Donors</a>
        </nav>
    </header>
    
    <main>
        <div class="hero-banner" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/backgrounds/donor-register.jpg');">
            <h2><?php echo $is_donor ? 'Welcome Donor!' : 'Become a Blood Donor'; ?></h2>
            <p><?php echo $is_donor ? 'Thank you for being a blood donor. You can donate blood now.' : 'Your donation can save lives. Register now to join our community of blood donors.'; ?></p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_class; ?>">
                <i class="fas <?php echo $message_class == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($is_donor): ?>
            <!-- Donor dashboard -->
            <div class="container-tabs">
                <div class="tab active" onclick="showTab('tab-donate')"><i class="fas fa-tint"></i> Donate Blood</div>
                <div class="tab" onclick="showTab('tab-history')"><i class="fas fa-history"></i> Donation History</div>
                <div class="tab" onclick="showTab('tab-profile')"><i class="fas fa-user-circle"></i> Your Profile</div>
            </div>
            
            <div class="donor-card">
                <div class="tab-content active" id="tab-donate">
                    <h3><i class="fas fa-heartbeat"></i> Donate Blood</h3>
                    <p>Fill out the form below to record a new blood donation. An administrator will review and accept your donation.</p>
                    
                    <form method="POST" onsubmit="return validateDonationForm()">
                        <label for="donation_date"><i class="fas fa-calendar"></i> Donation Date:</label>
                        <input type="date" id="donation_date" name="donation_date" required max="<?php echo date('Y-m-d'); ?>">
                        
                        <label for="units"><i class="fas fa-vial"></i> Units (Default: 1):</label>
                        <input type="number" id="units" name="units" min="1" max="3" value="1">
                        
                        <label for="notes"><i class="fas fa-sticky-note"></i> Notes (Optional):</label>
                        <textarea id="notes" name="notes" rows="4"></textarea>
                        
                        <button type="submit" name="donate_blood"><i class="fas fa-heart"></i> Record Blood Donation</button>
                    </form>
                </div>
                
                <div class="tab-content" id="tab-history">
                    <h3><i class="fas fa-history"></i> Your Donation History</h3>
                    
                    <?php if (isset($recent_donations) && count($recent_donations) > 0): ?>
                        <?php foreach ($recent_donations as $donation): ?>
                            <?php
                                $status_class = '';
                                switch ($donation['status']) {
                                    case 'pending':
                                        $status_class = 'status-pending';
                                        break;
                                    case 'accepted':
                                        $status_class = 'status-accepted';
                                        break;
                                    case 'rejected':
                                        $status_class = 'status-rejected';
                                        break;
                                }
                            ?>
                            <div class="donation-record">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong><?php echo htmlspecialchars($donation['donation_date']); ?></strong>
                                    <span class="donation-status <?php echo $status_class; ?>">
                                        <?php echo ucfirst(htmlspecialchars($donation['status'])); ?>
                                    </span>
                                </div>
                                <p><?php echo htmlspecialchars($donation['blood_type']); ?> - <?php echo htmlspecialchars($donation['units']); ?> unit(s)</p>
                                <?php if (!empty($donation['notes'])): ?>
                                    <p><small><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($donation['notes']); ?></small></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No donation records found. Donate blood today!</p>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="tab-profile">
                    <h3><i class="fas fa-user-circle"></i> Your Donor Profile</h3>
                    
                    <div class="donor-info">
                        <div class="info-item">
                            <strong><i class="fas fa-user"></i> Name</strong>
                            <?php echo htmlspecialchars($donor['name']); ?>
                        </div>
                        <div class="info-item">
                            <strong><i class="fas fa-tint"></i> Blood Type</strong>
                            <?php echo htmlspecialchars($donor['blood_type']); ?>
                        </div>
                        <div class="info-item">
                            <strong><i class="fas fa-phone"></i> Phone</strong>
                            <?php echo htmlspecialchars($donor['phone']); ?>
                        </div>
                        <div class="info-item">
                            <strong><i class="fas fa-envelope"></i> Email</strong>
                            <?php echo !empty($donor['email']) ? htmlspecialchars($donor['email']) : 'Not provided'; ?>
                        </div>
                        <div class="info-item">
                            <strong><i class="fas fa-map-marker-alt"></i> City</strong>
                            <?php echo htmlspecialchars($donor['city']); ?>
                        </div>
                        <div class="info-item">
                            <strong><i class="fas fa-calendar"></i> Last Donation</strong>
                            <?php echo !empty($donor['last_donation_date']) ? htmlspecialchars($donor['last_donation_date']) : 'Never'; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Registration form for new donors -->
            <form method="POST" onsubmit="return validateDonorForm()">
                <div class="form-icon"><i class="fas fa-heartbeat"></i></div>
                <h3>Donor Registration Form</h3>
                
                <label for="name"><i class="fas fa-user"></i> Full Name:</label>
                <input type="text" id="name" name="name" required>
                
                <label for="blood_type"><i class="fas fa-tint"></i> Blood Type:</label>
                <select id="blood_type" name="blood_type" required>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                </select>
                
                <label for="phone"><i class="fas fa-phone"></i> Phone Number:</label>
                <input type="text" id="phone" name="phone" required>
                
                <label for="email"><i class="fas fa-envelope"></i> Email (Optional):</label>
                <input type="email" id="email" name="email">
                
                <label for="city"><i class="fas fa-map-marker-alt"></i> City:</label>
                <input type="text" id="city" name="city" required>
                
                <label for="last_donation_date"><i class="fas fa-calendar"></i> Last Donation Date (if any):</label>
                <input type="date" id="last_donation_date" name="last_donation_date" max="<?php echo date('Y-m-d'); ?>">
                
                <button type="submit" name="register_donor"><i class="fas fa-heart"></i> Register as Donor</button>
            </form>
        <?php endif; ?>
    </main>
    
    <script>
    function showTab(tabId) {
        // Remove active class from all tabs and tab contents
        document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Add active class to selected tab and content
        document.getElementById(tabId).classList.add('active');
        document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
    }
    
    function validateDonationForm() {
        const donationDate = document.getElementById('donation_date');
        if (!donationDate.value) {
            alert('Please select a donation date');
            return false;
        }
        
        const units = document.getElementById('units');
        if (units.value < 1 || units.value > 3) {
            alert('Units must be between 1 and 3');
            return false;
        }
        
        return true;
    }
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>