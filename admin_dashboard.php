<?php
session_start();
include 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get blood inventory stats
try {
    $stmt = $pdo->query("SELECT * FROM blood_inventory ORDER BY blood_type");
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending donations
    $stmt = $pdo->query("
        SELECT dr.*, d.name, d.phone 
        FROM donation_records dr 
        JOIN donors d ON dr.donor_id = d.id 
        WHERE dr.status = 'pending'
        ORDER BY dr.created_at DESC
    ");
    $pending_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending blood requests
    $stmt = $pdo->query("
        SELECT * FROM blood_requests
        WHERE status = 'pending'
        ORDER BY urgency DESC, request_date DESC
    ");
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Admin Dashboard - Blood Bank</title>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .dashboard-card h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-card h3 i {
            color: #dc3545;
        }
        
        .admin-action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .admin-action-buttons a {
            flex: 1;
            text-align: center;
            padding: 12px;
            background-color: #35424a;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .admin-action-buttons a:hover {
            background-color: #2c3e50;
            transform: translateY(-3px);
        }
        
        .inventory-card {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .inventory-item {
            flex: 1 0 calc(25% - 10px);
            min-width: 100px;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }
        
        .inventory-item h4 {
            color: #dc3545;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .inventory-item p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            color: #666;
        }
        
        .low-stock {
            background-color: #f8d7da;
        }
        
        .good-stock {
            background-color: #d1e7dd;
        }
        
        .request-list, .donation-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .list-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .list-item .badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-left: 5px;
        }
        
        .badge-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .badge-urgent {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-critical {
            background-color: #dc3545;
            color: white;
        }
        
        .no-items {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .request-details {
            display: none;
            padding: 10px;
            background-color: #f8f9fa;
            border-top: 1px dashed #ddd;
            margin-top: 10px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            margin-bottom: 5px;
        }

        .detail-label {
            font-weight: bold;
            color: #495057;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
        </div>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="manage_stock.php"><i class="fas fa-warehouse"></i> Manage Stock</a>
            <a href="manage_donations.php"><i class="fas fa-heartbeat"></i> Accept Donations</a>
            <a href="manage_requests.php"><i class="fas fa-clipboard-list"></i> Manage Requests</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>
    
    <main>
        <div class="welcome-box">
            <h2><i class="fas fa-user-shield"></i> Welcome,  <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <p>This is your control center for managing blood donations, inventory, and requests.</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="admin-action-buttons">
            <a href="manage_stock.php"><i class="fas fa-warehouse"></i> Manage Blood Stock</a>
            <a href="manage_donations.php"><i class="fas fa-heartbeat"></i> Accept Blood Donations</a>
            <a href="manage_requests.php"><i class="fas fa-clipboard-list"></i> Process Blood Requests</a>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3><i class="fas fa-warehouse"></i> Blood Inventory</h3>
                <div class="inventory-card">
                    <?php if (isset($inventory) && count($inventory) > 0): ?>
                        <?php foreach ($inventory as $item): ?>
                            <?php 
                                $stock_class = '';
                                if ($item['units'] < 5) {
                                    $stock_class = 'low-stock';
                                } elseif ($item['units'] > 10) {
                                    $stock_class = 'good-stock';
                                }
                            ?>
                            <div class="inventory-item <?php echo $stock_class; ?>">
                                <h4><?php echo htmlspecialchars($item['blood_type']); ?></h4>
                                <p><?php echo htmlspecialchars($item['units']); ?> units</p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-items">No inventory data available</div>
                    <?php endif; ?>
                </div>
                <div style="text-align: right; margin-top: 15px;">
                    <a href="manage_stock.php" class="button"><i class="fas fa-edit"></i> Update Inventory</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="fas fa-heartbeat"></i> Pending Donations</h3>
                <div class="donation-list">
                    <?php if (isset($pending_donations) && count($pending_donations) > 0): ?>
                        <?php foreach ($pending_donations as $donation): ?>
                            <div class="list-item">
                                <strong><?php echo htmlspecialchars($donation['name']); ?></strong> 
                                <span class="badge badge-pending">Pending</span><br>
                                <?php echo htmlspecialchars($donation['blood_type']); ?> (<?php echo htmlspecialchars($donation['units']); ?> units)<br>
                                <small>Date: <?php echo htmlspecialchars($donation['donation_date']); ?></small>
                                <div style="margin-top: 5px;">
                                    <a href="manage_donations.php?action=accept&id=<?php echo $donation['id']; ?>" class="button">Accept</a>
                                    <a href="manage_donations.php?action=reject&id=<?php echo $donation['id']; ?>" class="button" style="background-color: #6c757d;">Reject</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-items">No pending donations</div>
                    <?php endif; ?>
                </div>
                <div style="text-align: right; margin-top: 15px;">
                    <a href="manage_donations.php" class="button"><i class="fas fa-arrow-right"></i> See All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="fas fa-clipboard-list"></i> Pending Blood Requests</h3>
                <div class="request-list">
                    <?php if (isset($pending_requests) && count($pending_requests) > 0): ?>
                        <?php foreach ($pending_requests as $request): ?>
                            <?php 
                                $urgency_class = 'badge-pending';
                                if ($request['urgency'] === 'urgent') {
                                    $urgency_class = 'badge-urgent';
                                } elseif ($request['urgency'] === 'critical') {
                                    $urgency_class = 'badge-critical';
                                }
                            ?>
                            <div class="list-item">
                                <strong>Blood Type: <?php echo htmlspecialchars($request['blood_type']); ?></strong> 
                                <span class="badge <?php echo $urgency_class; ?>"><?php echo ucfirst(htmlspecialchars($request['urgency'])); ?></span>
                                <a href="#" onclick="toggleDetails('request-<?php echo $request['id']; ?>', event)" class="toggle-link">
                                    <i class="fas fa-chevron-down"></i>
                                </a><br>
                                <?php echo htmlspecialchars($request['units_needed']); ?> units needed<br>
                                <?php if (!empty($request['city'])): ?>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($request['city']); ?><br>
                                <?php endif; ?>
                                <small>Date: <?php echo date('Y-m-d', strtotime($request['request_date'])); ?></small>
                                
                                <div id="request-<?php echo $request['id']; ?>" class="request-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Patient:</span>
                                        <span><?php echo !empty($request['patient_name']) ? htmlspecialchars($request['patient_name']) : 'Not specified'; ?></span>
                                    </div>
                                    <?php if (!empty($request['relationship'])): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Relationship:</span>
                                        <span><?php echo ucfirst(htmlspecialchars($request['relationship'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['hospital'])): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Hospital:</span>
                                        <span><?php echo htmlspecialchars($request['hospital']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['medical_reason'])): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Reason:</span>
                                        <span><?php echo htmlspecialchars($request['medical_reason']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Contact:</span>
                                        <span><?php echo htmlspecialchars($request['contact']); ?></span>
                                    </div>
                                    <div class="card-actions">
                                        <a href="manage_requests.php?action=fulfill&id=<?php echo $request['id']; ?>" class="button">
                                            Fulfill Request
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-items">No pending requests</div>
                    <?php endif; ?>
                </div>
                <div style="text-align: right; margin-top: 15px;">
                    <a href="manage_requests.php" class="button"><i class="fas fa-arrow-right"></i> Process Requests</a>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js"></script>
    <script>
    function toggleDetails(id, event) {
        event.preventDefault();
        const details = document.getElementById(id);
        const icon = event.currentTarget.querySelector('i');
        
        if (details.style.display === 'block') {
            details.style.display = 'none';
            icon.className = 'fas fa-chevron-down';
        } else {
            details.style.display = 'block';
            icon.className = 'fas fa-chevron-up';
        }
    }
    </script>
</body>
</html>