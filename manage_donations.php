<?php
session_start();
include 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_class = '';

// Handle donation acceptance/rejection
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        if ($action === 'accept') {
            // Get donation details
            $stmt = $pdo->prepare("SELECT * FROM donation_records WHERE id = ?");
            $stmt->execute([$id]);
            $donation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($donation) {
                // Update donation status
                $stmt = $pdo->prepare("UPDATE donation_records SET status = 'accepted' WHERE id = ?");
                $stmt->execute([$id]);
                
                // Update blood inventory
                $stmt = $pdo->prepare("UPDATE blood_inventory SET units = units + ? WHERE blood_type = ?");
                $stmt->execute([$donation['units'], $donation['blood_type']]);
                
                // Update donor's last donation date
                $stmt = $pdo->prepare("UPDATE donors SET last_donation_date = ? WHERE id = ?");
                $stmt->execute([$donation['donation_date'], $donation['donor_id']]);
                
                $message = "Donation accepted successfully! Blood inventory updated.";
                $message_class = "success";
            }
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE donation_records SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = "Donation rejected.";
            $message_class = "error";
        }
        
        // Commit transaction
        $pdo->commit();
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        $message = "Error processing donation: " . $e->getMessage();
        $message_class = "error";
    }
}

// Get donations list
try {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
    $where_clause = $filter !== 'all' ? "WHERE dr.status = '$filter'" : "";
    
    $stmt = $pdo->query("
        SELECT dr.*, d.name, d.phone, d.email 
        FROM donation_records dr 
        JOIN donors d ON dr.donor_id = d.id 
        $where_clause
        ORDER BY dr.created_at DESC
    ");
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Manage Blood Donations - Blood Bank</title>
    <style>
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        
        .filter-tab {
            padding: 8px 16px;
            background-color: #f8f9fa;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            background-color: #35424a;
            color: white;
        }
        
        .filter-tab:hover:not(.active) {
            background-color: #e9ecef;
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
        
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .card-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .no-donations {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-heartbeat"></i> Manage Blood Donations</h1>
        </div>
        <nav>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_stock.php"><i class="fas fa-warehouse"></i> Manage Stock</a>
            <a href="manage_requests.php"><i class="fas fa-clipboard-list"></i> Manage Requests</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </nav>
    </header>
    
    <main>
        <div class="welcome-box">
            <h2><i class="fas fa-heartbeat"></i> Blood Donation Management</h2>
            <p>Accept or reject blood donations and update inventory.</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_class; ?>">
                <i class="fas <?php echo $message_class == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter tabs -->
        <div class="filter-tabs">
            <a href="?filter=pending" class="filter-tab <?php echo (!isset($_GET['filter']) || $_GET['filter'] === 'pending') ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Pending
            </a>
            <a href="?filter=accepted" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'accepted') ? 'active' : ''; ?>">
                <i class="fas fa-check"></i> Accepted
            </a>
            <a href="?filter=rejected" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'rejected') ? 'active' : ''; ?>">
                <i class="fas fa-times"></i> Rejected
            </a>
            <a href="?filter=all" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'all') ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php elseif (isset($donations) && count($donations) > 0): ?>
            <?php foreach ($donations as $donation): ?>
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
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-tint"></i> 
                            <?php echo htmlspecialchars($donation['blood_type']); ?> 
                            (<?php echo htmlspecialchars($donation['units']); ?> units)
                        </h3>
                        <span class="donation-status <?php echo $status_class; ?>">
                            <?php echo ucfirst(htmlspecialchars($donation['status'])); ?>
                        </span>
                    </div>
                    
                    <p><strong>Donor:</strong> <?php echo htmlspecialchars($donation['name']); ?></p>
                    <p><strong>Donation Date:</strong> <?php echo htmlspecialchars($donation['donation_date']); ?></p>
                    
                    <div class="card-details">
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($donation['phone']); ?></p>
                        <?php if (!empty($donation['email'])): ?>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($donation['email']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($donation['notes'])): ?>
                            <p><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($donation['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($donation['status'] === 'pending'): ?>
                        <div class="card-actions">
                            <a href="?action=accept&id=<?php echo $donation['id']; ?>" class="button">
                                <i class="fas fa-check"></i> Accept Donation
                            </a>
                            <a href="?action=reject&id=<?php echo $donation['id']; ?>" class="button" style="background-color: #6c757d;">
                                <i class="fas fa-times"></i> Reject Donation
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-donations">
                <i class="fas fa-info-circle"></i> No donations found.
            </div>
        <?php endif; ?>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>