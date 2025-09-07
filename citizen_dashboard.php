<?php
// Start a session
session_start();

// Check if the user is logged in and is a citizen
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'citizen') {
    // Redirect to the login page if not
    header("Location: login.php");
    exit();
}

// Include the database connection file
require_once 'db_connect.php';

// Fetch citizen's NID from the session
$nid = $_SESSION['nid'];

// Debug: Check if NID is set
if (empty($nid)) {
    die("Error: NID not found in session. Please log in again.");
}

// Debug: Log the NID being used
error_log("Citizen dashboard - NID: " . $nid);

// Initialize a message variable for updates and applications
$update_message = '';
$application_message = '';

// Check if the update form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $nid_to_update = $_POST['nid'];

    // Update the citizen's information
    $stmt_update = mysqli_prepare($conn, "UPDATE citizen SET first_name = ?, last_name = ?, email = ?, address = ?, phone = ? WHERE NID = ?");
    mysqli_stmt_bind_param($stmt_update, "ssssss", $first_name, $last_name, $email, $address, $phone, $nid_to_update);

    if (mysqli_stmt_execute($stmt_update)) {
        $update_message = "<p style='color:green;'>Profile updated successfully!</p>";
    } else {
        $update_message = "<p style='color:red;'>Error updating profile: " . mysqli_stmt_error($stmt_update) . "</p>";
    }
    mysqli_stmt_close($stmt_update);
}

// Check if the application form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_document'])) {
    $document_type = $_POST['document_type'];
    $application_date = date('Y-m-d');
    
    // Debug: Log the document type being processed
    error_log("Citizen dashboard - Document type: " . $document_type);

    if ($document_type === 'Passport') {
        // Check if a pending passport application already exists
        $stmt_check_pending = mysqli_prepare($conn, "SELECT COUNT(*) FROM passport_application WHERE NID = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt_check_pending, "s", $nid);
        mysqli_stmt_execute($stmt_check_pending);
        mysqli_stmt_bind_result($stmt_check_pending, $count);
        mysqli_stmt_fetch($stmt_check_pending);
        mysqli_stmt_close($stmt_check_pending);

        if ($count > 0) {
            $application_message = "<p style='color:red;'>You already have a pending passport application.</p>";
        } else {
            // Insert a new passport application record
            $stmt_apply = mysqli_prepare($conn, "INSERT INTO passport_application (NID, application_date, status, created_date) VALUES (?, ?, 'pending', ?)");
            mysqli_stmt_bind_param($stmt_apply, "sss", $nid, $application_date, $application_date);
            
            if (mysqli_stmt_execute($stmt_apply)) {
                $application_message = "<p style='color:green;'>Passport application submitted successfully!</p>";
            } else {
                $application_message = "<p style='color:red;'>Error submitting passport application: " . mysqli_stmt_error($stmt_apply) . "</p>";
            }
            mysqli_stmt_close($stmt_apply);
        }
    } elseif ($document_type === 'Driving License') {
        error_log("Citizen dashboard - Processing Driving License application for NID: " . $nid);
        
        // Check if a pending driving license application already exists
        $stmt_check_pending = mysqli_prepare($conn, "SELECT COUNT(*) FROM license_application WHERE NID = ? AND status = 'pending'");
        if (!$stmt_check_pending) {
            error_log("Error preparing check pending statement: " . mysqli_error($conn));
            $application_message = "<p style='color:red;'>Database error: " . mysqli_error($conn) . "</p>";
        } else {
            mysqli_stmt_bind_param($stmt_check_pending, "s", $nid);
            mysqli_stmt_execute($stmt_check_pending);
            mysqli_stmt_bind_result($stmt_check_pending, $count);
            mysqli_stmt_fetch($stmt_check_pending);
            mysqli_stmt_close($stmt_check_pending);

            error_log("Citizen dashboard - Pending applications count: " . $count);

            if ($count > 0) {
                $application_message = "<p style='color:red;'>You already have a pending driving license application.</p>";
            } else {
                // Insert a new driving license application record
                $stmt_apply = mysqli_prepare($conn, "INSERT INTO license_application (NID, application_date, status, created_date) VALUES (?, ?, 'pending', ?)");
                if (!$stmt_apply) {
                    error_log("Error preparing insert statement: " . mysqli_error($conn));
                    $application_message = "<p style='color:red;'>Database error: " . mysqli_error($conn) . "</p>";
                } else {
                    mysqli_stmt_bind_param($stmt_apply, "sss", $nid, $application_date, $application_date);
                    
                    if (mysqli_stmt_execute($stmt_apply)) {
                        $application_message = "<p style='color:green;'>Driving license application submitted successfully!</p>";
                        error_log("Citizen dashboard - Driving license application submitted successfully for NID: " . $nid);
                    } else {
                        $application_message = "<p style='color:red;'>Error submitting driving license application: " . mysqli_stmt_error($stmt_apply) . "</p>";
                        error_log("Citizen dashboard - Error submitting driving license application: " . mysqli_stmt_error($stmt_apply));
                    }
                    mysqli_stmt_close($stmt_apply);
                }
            }
        }
    } else {
        error_log("Citizen dashboard - Unknown document type: " . $document_type);
        $application_message = "<p style='color:red;'>Unknown document type selected.</p>";
    }
}

// Fetch all citizen details
$stmt_citizen = mysqli_prepare($conn, "SELECT * FROM citizen WHERE NID = ?");
if (!$stmt_citizen) {
    die("Prepare failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt_citizen, "s", $nid);
mysqli_stmt_execute($stmt_citizen);
$result_citizen = mysqli_stmt_get_result($stmt_citizen);
$citizen_details = mysqli_fetch_assoc($result_citizen);
mysqli_stmt_close($stmt_citizen);

// Check if citizen record exists
if (!$citizen_details) {
    die("Error: Citizen record not found for NID: " . htmlspecialchars($nid) . ". Please contact administrator.");
}

// Fetch Tax Records
$tax_records = [];
$stmt_tax = mysqli_prepare($conn, "SELECT year, yearly_income, tax_amount, payment_status FROM tax_record WHERE NID = ?");
mysqli_stmt_bind_param($stmt_tax, "s", $nid);
mysqli_stmt_execute($stmt_tax);
$result_tax = mysqli_stmt_get_result($stmt_tax);
while ($row = mysqli_fetch_assoc($result_tax)) {
    $tax_records[] = $row;
}
mysqli_stmt_close($stmt_tax);

// Fetch Employment Records
$employment_records = [];
$stmt_employment = mysqli_prepare($conn, "SELECT company_name, job_title, start_date, end_date, salary FROM employment_record WHERE NID = ?");
mysqli_stmt_bind_param($stmt_employment, "s", $nid);
mysqli_stmt_execute($stmt_employment);
$result_employment = mysqli_stmt_get_result($stmt_employment);
while ($row = mysqli_fetch_assoc($result_employment)) {
    $employment_records[] = $row;
}
mysqli_stmt_close($stmt_employment);

// Fetch Criminal Records
$criminal_records = [];
$stmt_criminal = mysqli_prepare($conn, "SELECT date_of_offence, case_type, case_status, penalty, description FROM criminal_record WHERE NID = ?");
mysqli_stmt_bind_param($stmt_criminal, "s", $nid);
mysqli_stmt_execute($stmt_criminal);
$result_criminal = mysqli_stmt_get_result($stmt_criminal);
while ($row = mysqli_fetch_assoc($result_criminal)) {
    $criminal_records[] = $row;
}
mysqli_stmt_close($stmt_criminal);

// Fetch Passport Applications
$passport_applications = [];
$stmt_passport_apps = mysqli_prepare($conn, "SELECT application_id, application_date, status, verification_date, rejection_reason FROM passport_application WHERE NID = ? ORDER BY created_date DESC");
mysqli_stmt_bind_param($stmt_passport_apps, "s", $nid);
mysqli_stmt_execute($stmt_passport_apps);
$result_passport_apps = mysqli_stmt_get_result($stmt_passport_apps);
while ($row = mysqli_fetch_assoc($result_passport_apps)) {
    $passport_applications[] = $row;
}
mysqli_stmt_close($stmt_passport_apps);

// Fetch Driving License Applications
$license_applications = [];
$stmt_license_apps = mysqli_prepare($conn, "SELECT application_id, application_date, status, verification_date, rejection_reason FROM license_application WHERE NID = ? ORDER BY created_date DESC");
mysqli_stmt_bind_param($stmt_license_apps, "s", $nid);
mysqli_stmt_execute($stmt_license_apps);
$result_license_apps = mysqli_stmt_get_result($stmt_license_apps);
while ($row = mysqli_fetch_assoc($result_license_apps)) {
    $license_applications[] = $row;
}
mysqli_stmt_close($stmt_license_apps);

// Fetch Passport Status
$passport_status = null;
$stmt_passport = mysqli_prepare($conn, "SELECT passport_number, issue_date, expiry_date, status FROM passport WHERE NID = ?");
mysqli_stmt_bind_param($stmt_passport, "s", $nid);
mysqli_stmt_execute($stmt_passport);
$result_passport = mysqli_stmt_get_result($stmt_passport);
$passport_status = mysqli_fetch_assoc($result_passport);
mysqli_stmt_close($stmt_passport);

// Fetch Driving License Status
$license_status = null;
$stmt_license = mysqli_prepare($conn, "SELECT license_number, issue_date, expiry_date, status FROM driving_license WHERE NID = ?");
mysqli_stmt_bind_param($stmt_license, "s", $nid);
mysqli_stmt_execute($stmt_license);
$result_license = mysqli_stmt_get_result($stmt_license);
$license_status = mysqli_fetch_assoc($result_license);
mysqli_stmt_close($stmt_license);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard - CiviTrack</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Welcome, Citizen <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>This is your personal dashboard. You can view and update your records here.</p>
        
        <div class="section">
            <h3>Your Personal Information</h3>
            <ul>
                <li><strong>NID:</strong> <?php echo htmlspecialchars($citizen_details['NID']); ?></li>
                <li><strong>Full Name:</strong> <?php echo htmlspecialchars($citizen_details['first_name'] . ' ' . $citizen_details['last_name']); ?></li>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($citizen_details['email']); ?></li>
                <li><strong>Address:</strong> <?php echo htmlspecialchars($citizen_details['address']); ?></li>
                <li><strong>Phone:</strong> <?php echo htmlspecialchars($citizen_details['phone']); ?></li>
                <li><strong>Gender:</strong> <?php echo htmlspecialchars($citizen_details['gender']); ?></li>
            </ul>
        </div>

        <div class="section update-form">
            <h3>Update Personal Information</h3>
            <?php echo $update_message; ?>
            <form action="citizen_dashboard.php" method="post">
                <input type="hidden" name="update_profile" value="1">
                <input type="hidden" name="nid" value="<?php echo htmlspecialchars($citizen_details['NID']); ?>">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($citizen_details['first_name']); ?>" required>
                
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($citizen_details['last_name']); ?>" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($citizen_details['email']); ?>">
                
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($citizen_details['address']); ?>">
                
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($citizen_details['phone']); ?>">
                
                <button type="submit">Update Profile</button>
            </form>
        </div>

        <div class="section update-form">
            <h3>Apply for a Document</h3>
            <?php echo $application_message; ?>
            <form action="citizen_dashboard.php" method="post">
                <input type="hidden" name="apply_document" value="1">
                <label for="document_type">Document Type:</label>
                <select id="document_type" name="document_type" required>
                    <option value="Passport">Passport</option>
                    <option value="Driving License">Driving License</option>
                </select>
                <button type="submit">Submit Application</button>
            </form>
        </div>

        <div class="section">
            <h3>Document Status</h3>
            
            <h4>Passport Status</h4>
            <?php if ($passport_status): ?>
                <ul>
                    <li><strong>Passport Number:</strong> <?php echo htmlspecialchars($passport_status['passport_number'] ?? 'N/A'); ?></li>
                    <li><strong>Issue Date:</strong> <?php echo htmlspecialchars($passport_status['issue_date'] ?? 'N/A'); ?></li>
                    <li><strong>Expiry Date:</strong> <?php echo htmlspecialchars($passport_status['expiry_date'] ?? 'N/A'); ?></li>
                    <li><strong>Status:</strong> <?php echo htmlspecialchars($passport_status['status'] ?? 'N/A'); ?></li>
                </ul>
            <?php else: ?>
                <p>No passport issued yet.</p>
            <?php endif; ?>

            <h4>Driving License Status</h4>
            <?php if ($license_status): ?>
                <ul>
                    <li><strong>License Number:</strong> <?php echo htmlspecialchars($license_status['license_number'] ?? 'N/A'); ?></li>
                    <li><strong>Issue Date:</strong> <?php echo htmlspecialchars($license_status['issue_date'] ?? 'N/A'); ?></li>
                    <li><strong>Expiry Date:</strong> <?php echo htmlspecialchars($license_status['expiry_date'] ?? 'N/A'); ?></li>
                    <li><strong>Status:</strong> <?php echo htmlspecialchars($license_status['status'] ?? 'N/A'); ?></li>
                </ul>
            <?php else: ?>
                <p>No driving license issued yet.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Your Applications</h3>
            
            <h4>Passport Applications</h4>
            <?php if (count($passport_applications) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th>Verification Date</th>
                            <th>Rejection Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($passport_applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['application_date']); ?></td>
                                <td><?php echo htmlspecialchars($app['status']); ?></td>
                                <td><?php echo htmlspecialchars($app['verification_date'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($app['rejection_reason'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No passport applications submitted yet.</p>
            <?php endif; ?>

            <h4>Driving License Applications</h4>
            <?php if (count($license_applications) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th>Verification Date</th>
                            <th>Rejection Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($license_applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['application_date']); ?></td>
                                <td><?php echo htmlspecialchars($app['status']); ?></td>
                                <td><?php echo htmlspecialchars($app['verification_date'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($app['rejection_reason'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No driving license applications submitted yet.</p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>Tax Records</h3>
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
                <p>No tax records found.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Employment Records</h3>
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
                <p>No employment records found.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Criminal Records</h3>
            <?php if (count($criminal_records) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date of Offence</th>
                            <th>Case Type</th>
                            <th>Case Status</th>
                            <th>Penalty</th>
                            <th>Description</th>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No criminal records found.</p>
            <?php endif; ?>
        </div>
        
        <div class="logout-link">
            <a href="logout.php">Log Out</a>
        </div>
    </div>
</body>
</html>
<?php
// Close the database connection
mysqli_close($conn);
?>
