<?php
// Debug script to check database setup and login issues
session_start();
require_once 'db_connect.php';

echo "<h2>Database Debug Information</h2>";

// Check if users table exists and has data
$result = mysqli_query($conn, "SELECT * FROM users");
if ($result) {
    echo "<h3>Users Table:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>user_id</th><th>username</th><th>password</th><th>role</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . substr($row['password'], 0, 20) . "...</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error querying users table: " . mysqli_error($conn);
}

// Check if citizen table exists and has data
$result = mysqli_query($conn, "SELECT * FROM citizen");
if ($result) {
    echo "<h3>Citizen Table:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>NID</th><th>user_id</th><th>first_name</th><th>last_name</th><th>email</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['NID'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['first_name'] . "</td>";
        echo "<td>" . $row['last_name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error querying citizen table: " . mysqli_error($conn);
}

// Test password verification
echo "<h3>Password Verification Test:</h3>";
$test_password = "password";
$hashed_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
if (password_verify($test_password, $hashed_password)) {
    echo "Password verification: SUCCESS<br>";
} else {
    echo "Password verification: FAILED<br>";
}

// Test login process
echo "<h3>Login Process Test:</h3>";
$username = "citizen1";
$password = "password";

$stmt = mysqli_prepare($conn, "SELECT user_id, username, password, role FROM users WHERE username = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        echo "User found: " . $user['username'] . " (ID: " . $user['user_id'] . ")<br>";
        if (password_verify($password, $user['password'])) {
            echo "Password verification: SUCCESS<br>";
            
            // Check if citizen record exists
            $stmt2 = mysqli_prepare($conn, "SELECT NID FROM citizen WHERE user_id = ?");
            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, "i", $user['user_id']);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                $citizen = mysqli_fetch_assoc($result2);
                
                if ($citizen) {
                    echo "Citizen record found: NID = " . $citizen['NID'] . "<br>";
                } else {
                    echo "Citizen record NOT found for user_id: " . $user['user_id'] . "<br>";
                }
                mysqli_stmt_close($stmt2);
            } else {
                echo "Error preparing citizen query: " . mysqli_error($conn) . "<br>";
            }
        } else {
            echo "Password verification: FAILED<br>";
        }
    } else {
        echo "User not found<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing user query: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?>
