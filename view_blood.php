<?php
session_start();
include 'db.php';

try {
    $stmt = $pdo->query("SELECT name, blood_type, phone, email, city, last_donation_date FROM donors ORDER BY blood_type");
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Available Blood Donors - Blood Bank</title>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-tint"></i> Available Blood Donors</h1>
        </div>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="request.php"><i class="fas fa-procedures"></i> Request Blood</a>
        </nav>
    </header>

    <main>
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <?php if (count($donors) > 0): ?>
                <table>
                    <tr>
                        <th><i class="fas fa-user"></i> Name</th>
                        <th><i class="fas fa-tint"></i> Blood Type</th>
                        <th><i class="fas fa-map-marker-alt"></i> City</th>
                        <th><i class="fas fa-phone"></i> Phone</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-calendar"></i> Last Donation</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                    </tr>
                    <?php foreach ($donors as $donor): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($donor['name']); ?></td>
                        <td><?php echo htmlspecialchars($donor['blood_type']); ?></td>
                        <td><?php echo htmlspecialchars($donor['city']); ?></td>
                        <td><?php echo htmlspecialchars($donor['phone']); ?></td>
                        <td><?php echo htmlspecialchars($donor['email'] ?? 'N/A'); ?></td>
                        <td><?php echo $donor['last_donation_date'] ? htmlspecialchars($donor['last_donation_date']) : 'Never'; ?></td>
                        <td>
                            <?php
                            // Calculate eligibility (3 months since last donation)
                            $status = "Available";
                            $status_class = "";
                            
                            if (!empty($donor['last_donation_date'])) {
                                $last_date = new DateTime($donor['last_donation_date']);
                                $now = new DateTime();
                                $diff = $now->diff($last_date);
                                $days_since = $diff->days;
                                
                                if ($days_since < 90) {
                                    $status = "Not eligible for " . (90 - $days_since) . " days";
                                    $status_class = "highlighted";
                                }
                            }
                            echo "<span class=\"$status_class\">$status</span>";
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="no-donors"><i class="fas fa-exclamation-triangle"></i> No donors available at this time.</div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>