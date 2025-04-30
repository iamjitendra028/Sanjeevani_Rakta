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

// Handle stock update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        foreach ($_POST['units'] as $id => $units) {
            $units = (int)$units;
            if ($units < 0) $units = 0;
            
            $stmt = $pdo->prepare("UPDATE blood_inventory SET units = ? WHERE id = ?");
            $stmt->execute([$units, $id]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $message = "Blood inventory updated successfully!";
        $message_class = "success";
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        $message = "Error updating inventory: " . $e->getMessage();
        $message_class = "error";
    }
}

// Get current inventory
try {
    $stmt = $pdo->query("SELECT * FROM blood_inventory ORDER BY blood_type");
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Manage Blood Stock - Blood Bank</title>
    <style>
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .inventory-table th,
        .inventory-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .inventory-table th {
            background-color: #35424a;
            color: white;
        }
        
        .inventory-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .inventory-table input[type="number"] {
            width: 100px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .low-stock {
            color: #dc3545;
            font-weight: bold;
        }
        
        .adequate-stock {
            color: #28a745;
        }
        
        .stock-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            color: white;
        }
        
        .status-low {
            background-color: #dc3545;
        }
        
        .status-medium {
            background-color: #ffc107;
            color: #212529;
        }
        
        .status-good {
            background-color: #28a745;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-warehouse"></i> Manage Blood Stock</h1>
        </div>
        <nav>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_donations.php"><i class="fas fa-heartbeat"></i> Accept Donations</a>
            <a href="manage_requests.php"><i class="fas fa-clipboard-list"></i> Manage Requests</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </nav>
    </header>
    
    <main>
        <div class="welcome-box">
            <h2><i class="fas fa-warehouse"></i> Blood Inventory Management</h2>
            <p>Update blood inventory levels and monitor stock status.</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_class; ?>">
                <i class="fas <?php echo $message_class == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php elseif (isset($inventory)): ?>
            <form method="POST">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Blood Type</th>
                            <th>Current Units</th>
                            <th>Status</th>
                            <th>Update Units</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                            <?php
                                $status_class = '';
                                $status_text = '';
                                
                                if ($item['units'] < 5) {
                                    $status_class = 'status-low';
                                    $status_text = 'Low Stock';
                                } elseif ($item['units'] < 10) {
                                    $status_class = 'status-medium';
                                    $status_text = 'Medium Stock';
                                } else {
                                    $status_class = 'status-good';
                                    $status_text = 'Good Stock';
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['blood_type']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['units']); ?> units</td>
                                <td><span class="stock-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <input type="number" name="units[<?php echo $item['id']; ?>]" min="0" value="<?php echo htmlspecialchars($item['units']); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" name="update_stock" class="button"><i class="fas fa-save"></i> Update Inventory</button>
                </div>
            </form>
        <?php endif; ?>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>