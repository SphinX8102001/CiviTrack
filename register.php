<?php
// Start a session
session_start();

// Include the database connection file
require_once 'db_connect.php';

// Initialize a message variable
$message = '';

// Check if the registration form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['account_type']; // Get account type from form

    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $reg_date = date("Y-m-d");
    
    // Additional fields based on account type
    if ($role === 'citizen') {
        $nid = $_POST['nid'];
    } elseif ($role === 'police') {
        $employee_id = $_POST['employee_id'];
    }

    // Start a transaction
    mysqli_begin_transaction($conn);

    try {
        // First, insert into the users table
        $stmt_user = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if (!$stmt_user) {
            throw new Exception("User preparation failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_user, "sss", $username, $password, $role);
        mysqli_stmt_execute($stmt_user);
        $user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_user);

        // Then, insert into the appropriate table based on role
        if ($role === 'citizen') {
            $stmt_citizen = mysqli_prepare($conn, "INSERT INTO citizen (NID, user_id, first_name, last_name, email, address, phone, gender, reg_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt_citizen) {
                throw new Exception("Citizen preparation failed: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt_citizen, "sisssssss", $nid, $user_id, $first_name, $last_name, $email, $address, $phone, $gender, $reg_date);
            mysqli_stmt_execute($stmt_citizen);
            mysqli_stmt_close($stmt_citizen);
        } elseif ($role === 'police') {
            $stmt_police = mysqli_prepare($conn, "INSERT INTO police (user_id, employee_id) VALUES (?, ?)");
            if (!$stmt_police) {
                throw new Exception("Police preparation failed: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt_police, "is", $user_id, $employee_id);
            mysqli_stmt_execute($stmt_police);
            mysqli_stmt_close($stmt_police);
        }

        // Commit the transaction
        mysqli_commit($conn);
        $message = "Registration successful! You can now log in.";
        header("Location: login.php?message=" . urlencode($message));
        exit();

    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($conn);
        $message = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Citizen - CiviTrack</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .register-container { 
            background-color: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); 
            width: 90%; 
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        h2 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 30px;
            font-size: 2em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #555;
        }
        input[type="text"], input[type="password"], input[type="email"], select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box;
            font-size: 14px;
        }
        button { 
            width: 100%; 
            padding: 12px; 
            background-color: #007bff; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-top: 20px;
            font-size: 16px;
            font-weight: 600;
        }
        button:hover { 
            background-color: #0056b3; 
        }
        .message { 
            text-align: center; 
            margin-top: 15px; 
            padding: 10px;
            border-radius: 4px;
        }
        .error { 
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .login-link { 
            text-align: center; 
            margin-top: 20px; 
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-height: 600px) {
            body {
                align-items: flex-start;
                padding: 20px 0;
            }
            .register-container {
                margin: 20px auto;
            }
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 20px;
                width: 95%;
            }
            h2 {
                font-size: 1.5em;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>User Registration</h2>
        <?php if (!empty($message)): ?>
            <p class="message <?php echo strpos($message, 'failed') !== false ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="register.php" method="post" id="registrationForm">
            <div class="form-group">
                <label for="account_type">Account Type:</label>
                <select id="account_type" name="account_type" required onchange="toggleFields()">
                    <option value="">Select Account Type</option>
                    <option value="citizen">Citizen</option>
                    <option value="police">Police Officer</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group" id="nid_group" style="display: none;">
                <label for="nid">National ID (NID):</label>
                <input type="text" id="nid" name="nid">
            </div>

            <div class="form-group" id="employee_id_group" style="display: none;">
                <label for="employee_id">Employee ID:</label>
                <input type="text" id="employee_id" name="employee_id">
            </div>

            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
            </div>

            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address">
            </div>

            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone">
            </div>

            <div class="form-group">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <button type="submit">Register</button>
        </form>
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Log In</a></p>
        </div>
    </div>

    <script>
        function toggleFields() {
            const accountType = document.getElementById('account_type').value;
            const nidGroup = document.getElementById('nid_group');
            const employeeIdGroup = document.getElementById('employee_id_group');
            const nidField = document.getElementById('nid');
            const employeeIdField = document.getElementById('employee_id');

            // Hide both groups initially
            nidGroup.style.display = 'none';
            employeeIdGroup.style.display = 'none';
            
            // Clear required attributes
            nidField.removeAttribute('required');
            employeeIdField.removeAttribute('required');

            // Show appropriate group based on selection
            if (accountType === 'citizen') {
                nidGroup.style.display = 'block';
                nidField.setAttribute('required', 'required');
            } else if (accountType === 'police') {
                employeeIdGroup.style.display = 'block';
                employeeIdField.setAttribute('required', 'required');
            }
        }

        // Add form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const accountType = document.getElementById('account_type').value;
            
            if (accountType === 'citizen') {
                const nid = document.getElementById('nid').value;
                if (!nid.trim()) {
                    e.preventDefault();
                    alert('Please enter your National ID (NID) for citizen registration.');
                    return false;
                }
            } else if (accountType === 'police') {
                const employeeId = document.getElementById('employee_id').value;
                if (!employeeId.trim()) {
                    e.preventDefault();
                    alert('Please enter your Employee ID for police registration.');
                    return false;
                }
            }
        });
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>
