<?php
// Script to fix citizen data issues
session_start();
require_once 'db_connect.php';

echo "<h2>Fixing Citizen Data</h2>";

// First, let's see what we have
echo "<h3>Current Users:</h3>";
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY user_id");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>user_id</th><th>username</th><th>role</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Current Citizens:</h3>";
$result = mysqli_query($conn, "SELECT * FROM citizen");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>NID</th><th>user_id</th><th>first_name</th><th>last_name</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['NID'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['first_name'] . "</td>";
        echo "<td>" . $row['last_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Find the citizen1 user
$stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = 'citizen1'");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$citizen_user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($citizen_user) {
    $citizen_user_id = $citizen_user['user_id'];
    echo "<h3>Found citizen1 user with user_id: " . $citizen_user_id . "</h3>";
    
    // Check if citizen record exists for this user_id
    $stmt = mysqli_prepare($conn, "SELECT NID FROM citizen WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $citizen_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing_citizen = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($existing_citizen) {
        echo "<p>Citizen record already exists for user_id " . $citizen_user_id . " with NID: " . $existing_citizen['NID'] . "</p>";
    } else {
        echo "<p>No citizen record found for user_id " . $citizen_user_id . ". Creating one...</p>";
        
        // Create citizen record for the existing user
        $stmt = mysqli_prepare($conn, "INSERT INTO citizen (NID, user_id, first_name, last_name, email, address, phone, gender, reg_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $nid = '1234567890';
        $first_name = 'John';
        $last_name = 'Doe';
        $email = 'john.doe@email.com';
        $address = '123 Main St, City';
        $phone = '555-0123';
        $gender = 'male';
        $reg_date = '2023-01-15';
        
        mysqli_stmt_bind_param($stmt, "sissssss", $nid, $citizen_user_id, $first_name, $last_name, $email, $address, $phone, $gender, $reg_date);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: green;'>Citizen record created successfully!</p>";
        } else {
            echo "<p style='color: red;'>Error creating citizen record: " . mysqli_stmt_error($stmt) . "</p>";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    echo "<p style='color: red;'>citizen1 user not found!</p>";
}

// Also create admin and police records if they don't exist
echo "<h3>Checking Admin Record:</h3>";
$stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = 'admin'");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin_user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($admin_user) {
    $admin_user_id = $admin_user['user_id'];
    $stmt = mysqli_prepare($conn, "SELECT admin_id FROM admin WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $admin_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing_admin = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$existing_admin) {
        $stmt = mysqli_prepare($conn, "INSERT INTO admin (user_id) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "i", $admin_user_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: green;'>Admin record created successfully!</p>";
        } else {
            echo "<p style='color: red;'>Error creating admin record: " . mysqli_stmt_error($stmt) . "</p>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<p>Admin record already exists.</p>";
    }
}

echo "<h3>Checking Police Record:</h3>";
$stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = 'police1'");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$police_user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($police_user) {
    $police_user_id = $police_user['user_id'];
    $stmt = mysqli_prepare($conn, "SELECT police_id FROM police WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $police_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing_police = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$existing_police) {
        $stmt = mysqli_prepare($conn, "INSERT INTO police (user_id, employee_id) VALUES (?, ?)");
        $employee_id = 'POL001';
        mysqli_stmt_bind_param($stmt, "is", $police_user_id, $employee_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: green;'>Police record created successfully!</p>";
        } else {
            echo "<p style='color: red;'>Error creating police record: " . mysqli_stmt_error($stmt) . "</p>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<p>Police record already exists.</p>";
    }
}

echo "<h3>Final Status:</h3>";
echo "<p><a href='login.php'>Try logging in again</a></p>";

mysqli_close($conn);
?>
