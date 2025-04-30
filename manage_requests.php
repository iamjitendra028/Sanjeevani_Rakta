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

// Handle request actions (fulfill or cancel)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get request details
        $stmt = $pdo->prepare("SELECT * FROM blood_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request) {
            if ($action === 'fulfill') {
                // Check if enough blood is available
                $stmt = $pdo->prepare("SELECT units FROM blood_inventory WHERE blood_type = ?");
                $stmt->execute([$request['blood_type']]);
                $available = $stmt->fetchColumn();
                
                if ($available >= $request['units_needed']) {
                    // Update request status
                    $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'fulfilled' WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    // Reduce inventory
                    $stmt = $pdo->prepare("UPDATE blood_inventory SET units = units - ? WHERE blood_type = ?");
                    $stmt->execute([$request['units_needed'], $request['blood_type']]);
                    
                    $message = "Blood request fulfilled successfully!";
                    $message_class = "success";
                } else {
                    $message = "Error: Not enough blood available in inventory";
                    $message_class = "error";
                    $pdo->rollBack();
                    goto end_transaction;
                }
            } elseif ($action === 'cancel') {
                $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$id]);
                
                $message = "Blood request cancelled.";
                $message_class = "success";
            }
            
            // Commit transaction
            $pdo->commit();
        } else {
            $message = "Request not found";
            $message_class = "error";
            $pdo->rollBack();
        }
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        $message = "Error processing request: " . $e->getMessage();
        $message_class = "error";
    }
    
    end_transaction:
}

// Get blood requests list
try {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
    $where_clause = $filter !== 'all' ? "WHERE status = '$filter'" : "";
    
    $stmt = $pdo->query("
        SELECT * FROM blood_requests
        $where_clause
        ORDER BY 
            CASE 
                WHEN urgency = 'critical' THEN 1
                WHEN urgency = 'urgent' THEN 2
                ELSE 3
            END,
            request_date DESC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Manage Blood Requests - Blood Bank</title>
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
        
        .request-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-fulfilled {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .urgency-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        
        .urgency-normal {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .urgency-urgent {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .urgency-critical {
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
        
        .no-requests {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-item {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
        }
        
        .detail-item strong {
            display: block;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .inventory-check {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px 15px;
            margin-top: 15px;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-clipboard-list"></i> Manage Blood Requests</h1>
        </div>
        <nav>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_stock.php"><i class="fas fa-warehouse"></i> Manage Stock</a>
            <a href="manage_donations.php"><i class="fas fa-heartbeat"></i> Accept Donations</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </nav>
    </header>
    
    <main>
        <div class="welcome-box">
            <h2><i class="fas fa-clipboard-list"></i> Blood Request Management</h2>
            <p>Fulfill or cancel blood requests and track their status.</p>
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
            <a href="?filter=fulfilled" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'fulfilled') ? 'active' : ''; ?>">
                <i class="fas fa-check"></i> Fulfilled
            </a>
            <a href="?filter=cancelled" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'cancelled') ? 'active' : ''; ?>">
                <i class="fas fa-times"></i> Cancelled
            </a>
            <a href="?filter=all" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'all') ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php elseif (isset($requests) && count($requests) > 0): ?>
            <?php 
            // Get current inventory levels
            $inventory = [];
            $stmt = $pdo->query("SELECT blood_type, units FROM blood_inventory");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $inventory[$row['blood_type']] = $row['units'];
            }
            ?>
            
            <?php foreach ($requests as $request): ?>
                <?php
                    $status_class = '';
                    switch ($request['status']) {
                        case 'pending':
                            $status_class = 'status-pending';
                            break;
                        case 'fulfilled':
                            $status_class = 'status-fulfilled';
                            break;
                        case 'cancelled':
                            $status_class = 'status-cancelled';
                            break;
                    }
                    
                    $urgency_class = '';
                    switch ($request['urgency']) {
                        case 'normal':
                            $urgency_class = 'urgency-normal';
                            break;
                        case 'urgent':
                            $urgency_class = 'urgency-urgent';
                            break;
                        case 'critical':
                            $urgency_class = 'urgency-critical';
                            break;
                    }
                    
                    // Check if we have enough blood
                    $has_enough = isset($inventory[$request['blood_type']]) && $inventory[$request['blood_type']] >= $request['units_needed'];
                ?>
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-tint"></i> 
                            <?php echo htmlspecialchars($request['blood_type']); ?> 
                            (<?php echo htmlspecialchars($request['units_needed']); ?> units)
                            <span class="urgency-badge <?php echo $urgency_class; ?>">
                                <?php echo ucfirst(htmlspecialchars($request['urgency'])); ?>
                            </span>
                        </h3>
                        <span class="request-status <?php echo $status_class; ?>">
                            <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                        </span>
                    </div>
                    
                    <p>
                        <?php if (!empty($request['patient_name'])): ?>
                            <strong>Patient:</strong> <?php echo htmlspecialchars($request['patient_name']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($request['hospital'])): ?>
                            <strong>Hospital:</strong> <?php echo htmlspecialchars($request['hospital']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($request['city'])): ?>
                            <strong>City:</strong> <?php echo htmlspecialchars($request['city']); ?><br>
                        <?php endif; ?>
                        <strong>Requested:</strong> <?php echo date('F j, Y', strtotime($request['request_date'])); ?>
                    </p>
                    
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong><i class="fas fa-phone"></i> Contact</strong>
                            <?php echo htmlspecialchars($request['contact']); ?>
                        </div>
                        
                        <?php if (!empty($request['relationship'])): ?>
                        <div class="detail-item">
                            <strong><i class="fas fa-user-friends"></i> Relationship</strong>
                            <?php echo ucfirst(htmlspecialchars($request['relationship'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($request['medical_reason'])): ?>
                        <div class="detail-item">
                            <strong><i class="fas fa-notes-medical"></i> Medical Reason</strong>
                            <?php echo htmlspecialchars($request['medical_reason']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($request['status'] === 'pending'): ?>
                        <div class="inventory-check">
                            <?php if ($has_enough): ?>
                                <i class="fas fa-check-circle" style="color: green;"></i> 
                                <strong>Stock Available:</strong> 
                                <?php echo isset($inventory[$request['blood_type']]) ? $inventory[$request['blood_type']] : 0; ?> units of 
                                <?php echo htmlspecialchars($request['blood_type']); ?> blood in inventory
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> 
                                <strong>Insufficient Stock:</strong> 
                                Only <?php echo isset($inventory[$request['blood_type']]) ? $inventory[$request['blood_type']] : 0; ?> units of 
                                <?php echo htmlspecialchars($request['blood_type']); ?> blood available
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-actions">
                            <a href="?action=fulfill&id=<?php echo $request['id']; ?>" class="button" <?php echo !$has_enough ? 'style="opacity:0.5;" title="Not enough blood in inventory"' : ''; ?> <?php echo !$has_enough ? 'onclick="return confirm(\'Warning: Not enough blood in inventory. Proceed anyway?\');"' : ''; ?>>
                                <i class="fas fa-check"></i> Fulfill Request
                            </a>
                            <a href="?action=cancel&id=<?php echo $request['id']; ?>" class="button" style="background-color: #6c757d;">
                                <i class="fas fa-times"></i> Cancel Request
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-requests">
                <i class="fas fa-info-circle"></i> No blood requests found.
            </div>
        <?php endif; ?>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>