<?php
// Start a session
session_start();

// Check if the user is logged in and is a police officer
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'police') {
    // Redirect to the login page if not
    header("Location: login.php");
    exit();
}

// Include the database connection file
require_once 'db_connect.php';

// Initialize variables for messages and search results
$search_message = '';
$citizen_details = null;
$tax_records = [];
$employment_records = [];
$criminal_records = [];
$pending_applications = [];

// Handle NID search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_nid'])) {
    $search_nid = $_POST['nid'];

    // Fetch citizen details
    $stmt_citizen = mysqli_prepare($conn, "SELECT * FROM citizen WHERE NID = ?");
    mysqli_stmt_bind_param($stmt_citizen, "s", $search_nid);
    mysqli_stmt_execute($stmt_citizen);
    $result_citizen = mysqli_stmt_get_result($stmt_citizen);
    $citizen_details = mysqli_fetch_assoc($result_citizen);
    mysqli_stmt_close($stmt_citizen);

    if ($citizen_details) {
        $search_message = "<p style='color:green;'>Citizen found.</p>";
        
        // Fetch Tax Records
        $stmt_tax = mysqli_prepare($conn, "SELECT year, yearly_income, tax_amount, payment_status FROM tax_record WHERE NID = ?");
        mysqli_stmt_bind_param($stmt_tax, "s", $search_nid);
        mysqli_stmt_execute($stmt_tax);
        $result_tax = mysqli_stmt_get_result($stmt_tax);
        while ($row = mysqli_fetch_assoc($result_tax)) {
            $tax_records[] = $row;
        }
        mysqli_stmt_close($stmt_tax);

        // Fetch Employment Records
        $stmt_employment = mysqli_prepare($conn, "SELECT company_name, job_title, start_date, end_date, salary FROM employment_record WHERE NID = ?");
        mysqli_stmt_bind_param($stmt_employment, "s", $search_nid);
        mysqli_stmt_execute($stmt_employment);
        $result_employment = mysqli_stmt_get_result($stmt_employment);
        while ($row = mysqli_fetch_assoc($result_employment)) {
            $employment_records[] = $row;
        }
        mysqli_stmt_close($stmt_employment);

        // Fetch Criminal Records
        $stmt_criminal = mysqli_prepare($conn, "SELECT * FROM criminal_record WHERE NID = ?");
        mysqli_stmt_bind_param($stmt_criminal, "s", $search_nid);
        mysqli_stmt_execute($stmt_criminal);
        $result_criminal = mysqli_stmt_get_result($stmt_criminal);
        while ($row = mysqli_fetch_assoc($result_criminal)) {
            $criminal_records[] = $row;
        }
        mysqli_stmt_close($stmt_criminal);
    } else {
        $search_message = "<p style='color:red;'>No citizen found with that NID.</p>";
    }
}

// Handle updating a criminal record
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_criminal_record'])) {
    $record_id = $_POST['record_id'];
    $date_of_offence = $_POST['date_of_offence'];
    $case_type = $_POST['case_type'];
    $case_status = $_POST['case_status'];
    $penalty = $_POST['penalty'];
    $description = $_POST['description'];

    $stmt_update_criminal = mysqli_prepare($conn, "UPDATE criminal_record SET date_of_offence = ?, case_type = ?, case_status = ?, penalty = ?, description = ?, updated_date = CURRENT_DATE WHERE criminal_record_id = ?");
    mysqli_stmt_bind_param($stmt_update_criminal, "sssssi", $date_of_offence, $case_type, $case_status, $penalty, $description, $record_id);
    mysqli_stmt_execute($stmt_update_criminal);
    
    // Redirect to self to prevent form resubmission
    header("Location: police_dashboard.php?nid=" . urlencode($_POST['nid']));
    exit();
}

// Handle verifying a passport application
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_passport_application'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'];
    $rejection_reason = $_POST['rejection_reason'] ?? null;
    
    $stmt_verify = mysqli_prepare($conn, "UPDATE passport_application SET status = ?, verification_date = CURRENT_DATE, verified_by_police_id = ?, rejection_reason = ? WHERE application_id = ?");
    mysqli_stmt_bind_param($stmt_verify, "siss", $new_status, $_SESSION['user_id'], $rejection_reason, $application_id);
    mysqli_stmt_execute($stmt_verify);
    
    // If approved, create passport record and delete from applications
    if ($new_status === 'approved') {
        $stmt_get_nid = mysqli_prepare($conn, "SELECT NID FROM passport_application WHERE application_id = ?");
        mysqli_stmt_bind_param($stmt_get_nid, "i", $application_id);
        mysqli_stmt_execute($stmt_get_nid);
        $result_nid = mysqli_stmt_get_result($stmt_get_nid);
        $nid_data = mysqli_fetch_assoc($result_nid);
        mysqli_stmt_close($stmt_get_nid);
        
        if ($nid_data) {
            $passport_number = 'P' . str_pad($application_id, 8, '0', STR_PAD_LEFT);
            $issue_date = date('Y-m-d');
            $expiry_date = date('Y-m-d', strtotime('+10 years'));
            
            $stmt_create_passport = mysqli_prepare($conn, "INSERT INTO passport (NID, passport_number, issue_date, expiry_date, status, created_date) VALUES (?, ?, ?, ?, 'active', ?) ON DUPLICATE KEY UPDATE passport_number = ?, issue_date = ?, expiry_date = ?, status = 'active', created_date = ?");
            mysqli_stmt_bind_param($stmt_create_passport, "sssssssss", $nid_data['NID'], $passport_number, $issue_date, $expiry_date, $issue_date, $passport_number, $issue_date, $expiry_date, $issue_date);
            mysqli_stmt_execute($stmt_create_passport);
            mysqli_stmt_close($stmt_create_passport);
            
            // Delete the approved application from passport_application table
            $stmt_delete_app = mysqli_prepare($conn, "DELETE FROM passport_application WHERE application_id = ?");
            mysqli_stmt_bind_param($stmt_delete_app, "i", $application_id);
            mysqli_stmt_execute($stmt_delete_app);
            mysqli_stmt_close($stmt_delete_app);
        }
    }
    
    // Redirect to self to prevent form resubmission
    header("Location: police_dashboard.php");
    exit();
}

// Handle verifying a driving license application
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_license_application'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'];
    $rejection_reason = $_POST['rejection_reason'] ?? null;
    
    $stmt_verify = mysqli_prepare($conn, "UPDATE license_application SET status = ?, verification_date = CURRENT_DATE, verified_by_police_id = ?, rejection_reason = ? WHERE application_id = ?");
    mysqli_stmt_bind_param($stmt_verify, "siss", $new_status, $_SESSION['user_id'], $rejection_reason, $application_id);
    mysqli_stmt_execute($stmt_verify);
    
    // If approved, create driving license record and delete from applications
    if ($new_status === 'approved') {
        $stmt_get_nid = mysqli_prepare($conn, "SELECT NID FROM license_application WHERE application_id = ?");
        mysqli_stmt_bind_param($stmt_get_nid, "i", $application_id);
        mysqli_stmt_execute($stmt_get_nid);
        $result_nid = mysqli_stmt_get_result($stmt_get_nid);
        $nid_data = mysqli_fetch_assoc($result_nid);
        mysqli_stmt_close($stmt_get_nid);
        
        if ($nid_data) {
            $license_number = 'DL' . str_pad($application_id, 8, '0', STR_PAD_LEFT);
            $issue_date = date('Y-m-d');
            $expiry_date = date('Y-m-d', strtotime('+5 years'));
            
            $stmt_create_license = mysqli_prepare($conn, "INSERT INTO driving_license (NID, license_number, issue_date, expiry_date, status, created_date) VALUES (?, ?, ?, ?, 'active', ?) ON DUPLICATE KEY UPDATE license_number = ?, issue_date = ?, expiry_date = ?, status = 'active', created_date = ?");
            mysqli_stmt_bind_param($stmt_create_license, "sssssssss", $nid_data['NID'], $license_number, $issue_date, $expiry_date, $issue_date, $license_number, $issue_date, $expiry_date, $issue_date);
            mysqli_stmt_execute($stmt_create_license);
            mysqli_stmt_close($stmt_create_license);
            
            // Delete the approved application from license_application table
            $stmt_delete_app = mysqli_prepare($conn, "DELETE FROM license_application WHERE application_id = ?");
            mysqli_stmt_bind_param($stmt_delete_app, "i", $application_id);
            mysqli_stmt_execute($stmt_delete_app);
            mysqli_stmt_close($stmt_delete_app);
        }
    }
    
    // Redirect to self to prevent form resubmission
    header("Location: police_dashboard.php");
    exit();
}

// Fetch all pending passport applications for verification
$stmt_pending_passport_apps = mysqli_prepare($conn, "SELECT pa.application_id, pa.application_date, pa.NID, c.first_name, c.last_name FROM passport_application pa JOIN citizen c ON pa.NID = c.NID WHERE pa.status = 'pending'");
mysqli_stmt_execute($stmt_pending_passport_apps);
$result_pending_passport_apps = mysqli_stmt_get_result($stmt_pending_passport_apps);
while ($row = mysqli_fetch_assoc($result_pending_passport_apps)) {
    $row['document_type'] = 'Passport';
    $pending_applications[] = $row;
}
mysqli_stmt_close($stmt_pending_passport_apps);

// Fetch all pending driving license applications for verification
$stmt_pending_license_apps = mysqli_prepare($conn, "SELECT la.application_id, la.application_date, la.NID, c.first_name, c.last_name FROM license_application la JOIN citizen c ON la.NID = c.NID WHERE la.status = 'pending'");
mysqli_stmt_execute($stmt_pending_license_apps);
$result_pending_license_apps = mysqli_stmt_get_result($stmt_pending_license_apps);
while ($row = mysqli_fetch_assoc($result_pending_license_apps)) {
    $row['document_type'] = 'Driving License';
    $pending_applications[] = $row;
}
mysqli_stmt_close($stmt_pending_license_apps);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Dashboard - CiviTrack</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .criminal-update-form { display: none; }
        .search-form, .record-form { background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .search-form input[type="text"], .search-form button { padding: 10px; margin-right: 10px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, Police Officer <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>This is your dashboard. You can search for citizens and manage their criminal records and document applications.</p>
        
        <div class="search-form">
            <h3>Search for a Citizen</h3>
            <form action="police_dashboard.php" method="post">
                <label for="nid">Enter Citizen NID:</label>
                <input type="text" id="nid" name="nid" required>
                <button type="submit" name="search_nid">Search</button>
            </form>
            <?php echo $search_message; ?>
        </div>

        <?php if ($citizen_details): ?>
        <div class="section">
            <h3>Citizen Records for NID: <?php echo htmlspecialchars($citizen_details['NID']); ?></h3>
            
            <h4>Personal Information</h4>
            <ul>
                <li><strong>Full Name:</strong> <?php echo htmlspecialchars($citizen_details['first_name'] . ' ' . $citizen_details['last_name']); ?></li>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($citizen_details['email']); ?></li>
                <li><strong>Address:</strong> <?php echo htmlspecialchars($citizen_details['address']); ?></li>
                <li><strong>Phone:</strong> <?php htmlspecialchars($citizen_details['phone']); ?></li>
                <li><strong>Gender:</strong> <?php echo htmlspecialchars($citizen_details['gender']); ?></li>
            </ul>

            <h4>Tax Records</h4>
            <?php if (count($tax_records) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Yearly Income</th>
                            <th>Tax Amount</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tax_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['year']); ?></td>
                                <td><?php echo htmlspecialchars($record['yearly_income']); ?></td>
                                <td><?php echo htmlspecialchars($record['tax_amount']); ?></td>
                                <td><?php echo htmlspecialchars($record['payment_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No tax records found for this citizen.</p>
            <?php endif; ?>

            <h4>Employment Records</h4>
            <?php if (count($employment_records) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Job Title</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employment_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($record['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['end_date'] ?? 'Current'); ?></td>
                                <td><?php echo htmlspecialchars($record['salary'] ? '$' . number_format($record['salary'], 2) : 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No employment records found for this citizen.</p>
            <?php endif; ?>
            
            <h4>Criminal Records</h4>
            <?php if (count($criminal_records) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date of Offence</th>
                            <th>Case Type</th>
                            <th>Case Status</th>
                            <th>Penalty</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($criminal_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['date_of_offence']); ?></td>
                                <td><?php echo htmlspecialchars($record['case_type']); ?></td>
                                <td><?php echo htmlspecialchars($record['case_status']); ?></td>
                                <td><?php echo htmlspecialchars($record['penalty']); ?></td>
                                <td><?php echo htmlspecialchars($record['description'] ?? 'N/A'); ?></td>
                                <td>
                                    <button onclick="showUpdateForm(<?php echo $record['criminal_record_id']; ?>, '<?php echo htmlspecialchars(json_encode($record)); ?>')">Update</button>
                                </td>
                            </tr>
                            <tr class="criminal-update-form" id="update-form-<?php echo $record['criminal_record_id']; ?>">
                                <td colspan="6">
                                    <form action="police_dashboard.php" method="post">
                                        <input type="hidden" name="update_criminal_record" value="1">
                                        <input type="hidden" name="nid" value="<?php echo htmlspecialchars($search_nid); ?>">
                                        <input type="hidden" name="record_id" value="<?php echo $record['criminal_record_id']; ?>">
                                        
                                        <label for="date_of_offence">Date of Offence:</label>
                                        <input type="date" name="date_of_offence" value="<?php echo htmlspecialchars($record['date_of_offence']); ?>" required>
                                        
                                        <label for="case_type">Case Type:</label>
                                        <input type="text" name="case_type" value="<?php echo htmlspecialchars($record['case_type']); ?>" required>

                                        <label for="case_status">Case Status:</label>
                                        <select name="case_status" required>
                                            <option value="pending" <?php echo $record['case_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="under_investigation" <?php echo $record['case_status'] === 'under_investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                                            <option value="closed" <?php echo $record['case_status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                            <option value="convicted" <?php echo $record['case_status'] === 'convicted' ? 'selected' : ''; ?>>Convicted</option>
                                            <option value="acquitted" <?php echo $record['case_status'] === 'acquitted' ? 'selected' : ''; ?>>Acquitted</option>
                                        </select>

                                        <label for="penalty">Penalty:</label>
                                        <input type="text" name="penalty" value="<?php echo htmlspecialchars($record['penalty']); ?>">

                                        <label for="description">Description:</label>
                                        <textarea name="description" rows="3"><?php echo htmlspecialchars($record['description'] ?? ''); ?></textarea>

                                        <button type="submit">Save Changes</button>
                                        <button type="button" onclick="hideUpdateForm(<?php echo $record['criminal_record_id']; ?>)">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No criminal records found for this citizen.</p>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <div class="section">
            <h3>Pending Document Applications</h3>
            <?php if (count($pending_applications) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>NID</th>
                            <th>Full Name</th>
                            <th>Document Type</th>
                            <th>Application Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['NID']); ?></td>
                                <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['document_type']); ?></td>
                                <td><?php echo htmlspecialchars($app['application_date']); ?></td>
                                <td>
                                    <?php if ($app['document_type'] === 'Passport'): ?>
                                        <form action="police_dashboard.php" method="post" style="display:inline-block;">
                                            <input type="hidden" name="verify_passport_application" value="1">
                                            <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" style="background-color: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Approve</button>
                                        </form>
                                        <form action="police_dashboard.php" method="post" style="display:inline-block;">
                                            <input type="hidden" name="verify_passport_application" value="1">
                                            <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <input type="text" name="rejection_reason" placeholder="Rejection reason" required>
                                            <button type="submit" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="police_dashboard.php" method="post" style="display:inline-block;">
                                            <input type="hidden" name="verify_license_application" value="1">
                                            <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" style="background-color: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Approve</button>
                                        </form>
                                        <form action="police_dashboard.php" method="post" style="display:inline-block;">
                                            <input type="hidden" name="verify_license_application" value="1">
                                            <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <input type="text" name="rejection_reason" placeholder="Rejection reason" required>
                                            <button type="submit" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending applications to review.</p>
            <?php endif; ?>
        </div>

        <p><a href="logout.php">Log Out</a></p>
    </div>

    <script>
        function showUpdateForm(id) {
            document.getElementById('update-form-' + id).style.display = 'table-row';
        }
        function hideUpdateForm(id) {
            document.getElementById('update-form-' + id).style.display = 'none';
        }
    </script>
</body>
</html>
<?php
// Close the database connection
mysqli_close($conn);
?>
