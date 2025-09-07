<?php
// Start a session
session_start();

// Include the database connection file
require_once 'db_connect.php';

// Initialize a message variable
$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Check if the login form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute a statement to fetch user data
    $stmt = mysqli_prepare($conn, "SELECT user_id, username, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Debug: Log the user_id for debugging
            error_log("Login successful for user_id: " . $user['user_id'] . ", role: " . $user['role']);

            // Redirect based on user role
            switch ($user['role']) {
                case 'citizen':
                    // Fetch citizen's NID and store in session
                    $stmt_nid = mysqli_prepare($conn, "SELECT NID FROM citizen WHERE user_id = ?");
                    if (!$stmt_nid) {
                        die("Prepare failed: " . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($stmt_nid, "i", $user['user_id']);
                    mysqli_stmt_execute($stmt_nid);
                    $result_nid = mysqli_stmt_get_result($stmt_nid);
                    $citizen_info = mysqli_fetch_assoc($result_nid);
                    
                    if (!$citizen_info) {
                        die("Error: No citizen record found for user_id: " . $user['user_id'] . ". Please contact administrator.");
                    }
                    
                    $_SESSION['nid'] = $citizen_info['NID'];
                    error_log("Citizen NID set: " . $citizen_info['NID']);
                    mysqli_stmt_close($stmt_nid);
                    header("Location: citizen_dashboard.php");
                    break;
                case 'police':
                    header("Location: police_dashboard.php");
                    break;
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                default:
                    $message = "Invalid user role.";
            }
            exit();
        } else {
            $message = "Invalid username or password.";
        }
    } else {
        $message = "Invalid username or password.";
    }

    // Close the statement
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CiviTrack</title>
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
        .login-container { 
            background-color: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); 
            width: 400px; 
            max-width: 90%;
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
        input[type="text"], input[type="password"] { 
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
        .register-link { 
            text-align: center; 
            margin-top: 20px; 
        }
        .register-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>User Login</h2>
        <?php if (!empty($message)): ?>
            <p class="message <?php echo strpos($message, 'Invalid') !== false ? 'error' : ''; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Log In</button>
        </form>
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>
<?php
// Close the database connection
mysqli_close($conn);
?>
