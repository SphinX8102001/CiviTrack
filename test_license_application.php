<?php
// Test script to check driving license application functionality
require_once 'db_connect.php';

echo "<h2>Testing Driving License Application</h2>";

// Test 1: Check if license_application table exists and has correct structure
echo "<h3>1. Checking license_application table structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE license_application");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Test 2: Check current applications in the table
echo "<h3>2. Current applications in license_application table:</h3>";
$result = mysqli_query($conn, "SELECT * FROM license_application ORDER BY created_date DESC");
if ($result) {
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Application ID</th><th>NID</th><th>Application Date</th><th>Status</th><th>Created Date</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['application_id'] . "</td>";
            echo "<td>" . $row['NID'] . "</td>";
            echo "<td>" . $row['application_date'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No applications found in license_application table.";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}

// Test 3: Test inserting a sample application
echo "<h3>3. Testing sample application insertion:</h3>";
$test_nid = '1234567890'; // Use the sample NID from the database
$application_date = date('Y-m-d');

$stmt = mysqli_prepare($conn, "INSERT INTO license_application (NID, application_date, status, created_date) VALUES (?, ?, 'pending', ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sss", $test_nid, $application_date, $application_date);
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Sample application inserted successfully!<br>";
        echo "Application ID: " . mysqli_insert_id($conn) . "<br>";
    } else {
        echo "❌ Error inserting sample application: " . mysqli_stmt_error($stmt) . "<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "❌ Error preparing statement: " . mysqli_error($conn) . "<br>";
}

// Test 4: Check if the sample was inserted
echo "<h3>4. Checking if sample application was inserted:</h3>";
$result = mysqli_query($conn, "SELECT * FROM license_application WHERE NID = '$test_nid' ORDER BY created_date DESC LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "✅ Sample application found:<br>";
    echo "Application ID: " . $row['application_id'] . "<br>";
    echo "NID: " . $row['NID'] . "<br>";
    echo "Status: " . $row['status'] . "<br>";
    echo "Application Date: " . $row['application_date'] . "<br>";
    echo "Created Date: " . $row['created_date'] . "<br>";
} else {
    echo "❌ Sample application not found.";
}

mysqli_close($conn);
?>
