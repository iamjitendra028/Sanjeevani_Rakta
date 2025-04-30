<?php
session_start();
include 'db.php';

$message = '';
$message_class = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $blood_type = $_POST['blood_type'];
    $contact = $_POST['contact'];
    $city = isset($_POST['city']) ? trim($_POST['city']) : null;
    $units = isset($_POST['units']) ? (int)$_POST['units'] : 1;
    $urgency = isset($_POST['urgency']) ? $_POST['urgency'] : 'normal';
    $hospital = isset($_POST['hospital']) ? trim($_POST['hospital']) : null;
    $patient_name = isset($_POST['patient_name']) ? trim($_POST['patient_name']) : null;
    $relationship = isset($_POST['relationship']) ? trim($_POST['relationship']) : null;
    $medical_reason = isset($_POST['medical_reason']) ? trim($_POST['medical_reason']) : null;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    try {
        // Insert the request with more detailed information
        $stmt = $pdo->prepare("INSERT INTO blood_requests (blood_type, contact, city, units_needed, urgency, hospital, 
                              patient_name, relationship, medical_reason, user_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$blood_type, $contact, $city, $units, $urgency, $hospital, 
                          $patient_name, $relationship, $medical_reason, $user_id])) {
            $message = "Blood request submitted successfully! We will contact you soon.";
            $message_class = "success";
        } else {
            $message = "Error: Could not submit request.";
            $message_class = "error";
        }
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
    <title>Request Blood - Blood Bank</title>
    <style>
        .form-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-columns {
                grid-template-columns: 1fr;
            }
        }
        
        .urgency-selector {
            display: flex;
            margin-bottom: 20px;
        }
        
        .urgency-option {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            background: #f8f8f8;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .urgency-option:first-child {
            border-radius: 4px 0 0 4px;
        }
        
        .urgency-option:last-child {
            border-radius: 0 4px 4px 0;
        }
        
        .urgency-option.selected {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        
        .urgency-option.normal.selected {
            background: #28a745;
            border-color: #28a745;
        }
        
        .urgency-option.urgent.selected {
            background: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        
        .urgency-option.critical.selected {
            background: #dc3545;
            border-color: #dc3545;
        }
        
        .urgency-option:hover:not(.selected) {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/icon/logo.png" alt="Blood Bank Logo" class="logo">
            <h1><i class="fas fa-procedures"></i> Request Blood</h1>
        </div>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="view_blood.php"><i class="fas fa-tint"></i> View Available Blood</a>
        </nav>
    </header>

    <main>
        <div class="hero-banner" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/backgrounds/blood-request.jpg');">
            <h2>Need Blood?</h2>
            <p>Fill out the form below to submit a blood request. We'll connect you with available donors.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_class; ?>">
                <i class="fas <?php echo $message_class == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" onsubmit="return validateRequestForm()">
            <div class="form-icon"><i class="fas fa-tint-slash"></i></div>
            <h3>Blood Request Form</h3>
            
            <div class="form-columns">
                <div>
                    <label for="patient_name"><i class="fas fa-user-injured"></i> Patient Name:</label>
                    <input type="text" id="patient_name" name="patient_name" placeholder="Name of person needing blood" required>
                    
                    <label for="relationship"><i class="fas fa-user-friends"></i> Your Relationship to Patient:</label>
                    <select id="relationship" name="relationship">
                        <option value="self">Self</option>
                        <option value="family">Family Member</option>
                        <option value="friend">Friend</option>
                        <option value="medical_staff">Medical Staff</option>
                        <option value="other">Other</option>
                    </select>
                    
                    <label for="blood_type"><i class="fas fa-tint"></i> Blood Type Needed:</label>
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
                    
                    <label for="units"><i class="fas fa-list-ol"></i> Units Needed:</label>
                    <input type="number" id="units" name="units" min="1" value="1" required>
                </div>
                
                <div>
                    <label for="hospital"><i class="fas fa-hospital"></i> Hospital/Medical Facility:</label>
                    <input type="text" id="hospital" name="hospital" placeholder="Where blood is needed">
                    
                    <label for="city"><i class="fas fa-map-marker-alt"></i> City:</label>
                    <input type="text" id="city" name="city" required>
                    
                    <label for="contact"><i class="fas fa-phone"></i> Contact (Phone/Email):</label>
                    <input type="text" id="contact" name="contact" required>
                    
                    <label for="medical_reason"><i class="fas fa-notes-medical"></i> Medical Reason (Optional):</label>
                    <input type="text" id="medical_reason" name="medical_reason" placeholder="Surgery, Accident, etc.">
                </div>
            </div>
            
            <label><i class="fas fa-exclamation-triangle"></i> Urgency Level:</label>
            <div class="urgency-selector">
                <div class="urgency-option normal selected" data-value="normal" onclick="selectUrgency(this)">
                    <i class="fas fa-clock"></i> Normal
                </div>
                <div class="urgency-option urgent" data-value="urgent" onclick="selectUrgency(this)">
                    <i class="fas fa-exclamation"></i> Urgent
                </div>
                <div class="urgency-option critical" data-value="critical" onclick="selectUrgency(this)">
                    <i class="fas fa-exclamation-circle"></i> Critical
                </div>
            </div>
            <input type="hidden" id="urgency" name="urgency" value="normal">
            
            <button type="submit"><i class="fas fa-paper-plane"></i> Request Blood</button>
        </form>
    </main>
    
    <script>
    function selectUrgency(element) {
        // Remove selected class from all options
        document.querySelectorAll('.urgency-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Add selected class to clicked option
        element.classList.add('selected');
        
        // Update hidden input value
        document.getElementById('urgency').value = element.getAttribute('data-value');
    }
    
    function validateRequestForm() {
        const patientName = document.getElementById('patient_name');
        const contact = document.getElementById('contact');
        const city = document.getElementById('city');
        
        if (patientName.value.trim() === '') {
            alert('Please enter the patient name');
            patientName.focus();
            return false;
        }
        
        if (city.value.trim() === '') {
            alert('Please enter your city');
            city.focus();
            return false;
        }
        
        if (contact.value.trim() === '') {
            alert('Please enter contact information');
            contact.focus();
            return false;
        }
        
        return true;
    }
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>