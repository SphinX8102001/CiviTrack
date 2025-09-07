<?php
// Start a session
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>This is the Admin Dashboard. You have full access to all data.</p>
    <p>This page is currently under construction.</p>
    <p><a href="logout.php">Log Out</a></p>
</body>
</html>
<?php
// Start a session
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
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
$all_citizens = [];

// Handle search by NID
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_nid'])) {
    $search_nid = $_POST['nid'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM citizen WHERE NID = ?");
    mysqli_stmt_bind_param($stmt, "s", $search_nid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $citizen_details = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($citizen_details) {
        $search_message = "<p style='color:green;'>Citizen found by NID.</p>";
        // Fetch all records for the found citizen
        $stmt_tax = mysqli_prepare($conn, "SELECT * FROM tax_record WHERE NID = ?");
        mysqli_stmt_bind_param($stmt_tax, "s", $search_nid);
        mysqli_stmt_execute($stmt_tax);
        $result_tax = mysqli_stmt_get_result($stmt_tax);
        while ($row = mysqli_fetch_assoc($result_tax)) {
            $tax_records[] = $row;
        }
        mysqli_stmt_close($stmt_tax);

        $stmt_employment = mysqli_prepare($conn, "SELECT * FROM employment_record WHERE NID = ?");
        mysqli_stmt_bind_param($stmt_employment, "s", $search_nid);
        mysqli_stmt_execute($stmt_employment);
        $result_employment = mysqli_stmt_get_result($stmt_employment);
        while ($row = mysqli_fetch_assoc($result_employment)) {
            $employment_records[] = $row;
        }
        mysqli_stmt_close($stmt_employment);

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

// Handle search by Name and Address
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_name_address'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $address = $_POST['address'];

    $stmt_search_all = mysqli_prepare($conn, "SELECT * FROM citizen WHERE first_name = ? AND last_name = ? AND address = ?");
    mysqli_stmt_bind_param($stmt_search_all, "sss", $first_name, $last_name, $address);
    mysqli_stmt_execute($stmt_search_all);
    $result_search_all = mysqli_stmt_get_result($stmt_search_all);

    if (mysqli_num_rows($result_search_all) > 0) {
        $search_message = "<p style='color:green;'>Citizens found with matching name and address.</p>";
        while ($row = mysqli_fetch_assoc($result_search_all)) {
            $all_citizens[] = $row;
        }
    } else {
        $search_message = "<p style='color:red;'>No citizens found with that name and address.</p>";
    }
    mysqli_stmt_close($stmt_search_all);
}

// Handle deleting a record
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_record'])) {
    $record_id = $_POST['record_id'];
    $table = $_POST['table_name'];
    $id_column = '';

    switch ($table) {
        case 'tax_record':
            $id_column = 'tax_record_id';
            break;
        case 'employment_record':
            $id_column = 'employment_record_id';
            break;
        case 'criminal_record':
            $id_column = 'criminal_record_id';
            break;
    }

    $stmt_delete = mysqli_prepare($conn, "DELETE FROM {$table} WHERE {$id_column} = ?");
    mysqli_stmt_bind_param($stmt_delete, "i", $record_id);
    mysqli_stmt_execute($stmt_delete);
    mysqli_stmt_close($stmt_delete);
    header("Location: admin_dashboard.php");
    exit();
}

// Handle adding new records
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_record'])) {
    $record_type = $_POST['record_type'];
    $nid = $_POST['nid'];
    
    if ($record_type === 'tax') {
        $year = $_POST['year'];
        $yearly_income = $_POST['yearly_income'];
        $tax_amount = $_POST['tax_amount'];
        $payment_status = $_POST['payment_status'];
        
        $stmt_add = mysqli_prepare($conn, "INSERT INTO tax_record (NID, year, yearly_income, tax_amount, payment_status) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_add, "sidds", $nid, $year, $yearly_income, $tax_amount, $payment_status);
        mysqli_stmt_execute($stmt_add);
        mysqli_stmt_close($stmt_add);
        
    } elseif ($record_type === 'employment') {
        $company_name = $_POST['company_name'];
        $job_title = $_POST['job_title'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'] ?: null;
        $salary = $_POST['salary'] ?: null;
        
        $stmt_add = mysqli_prepare($conn, "INSERT INTO employment_record (NID, company_name, job_title, start_date, end_date, salary) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_add, "sssssd", $nid, $company_name, $job_title, $start_date, $end_date, $salary);
        mysqli_stmt_execute($stmt_add);
        mysqli_stmt_close($stmt_add);
        
    } elseif ($record_type === 'criminal') {
        $case_type = $_POST['case_type'];
        $date_of_offence = $_POST['date_of_offence'];
        $case_status = $_POST['case_status'];
        $penalty = $_POST['penalty'] ?: null;
        $description = $_POST['description'] ?: null;
        
        $stmt_add = mysqli_prepare($conn, "INSERT INTO criminal_record (NID, case_type, date_of_offence, case_status, penalty, description) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_add, "ssssss", $nid, $case_type, $date_of_offence, $case_status, $penalty, $description);
        mysqli_stmt_execute($stmt_add);
        mysqli_stmt_close($stmt_add);
    }
    
    header("Location: admin_dashboard.php?nid=" . urlencode($nid));
    exit();
}

// Handle marking a citizen as deceased
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_deceased'])) {
    $nid_to_delete = $_POST['nid_to_delete'];
    $death_cert = $_POST['death_certificate_number'];

    // Start a transaction
    mysqli_begin_transaction($conn);

    try {
        // Fetch citizen details
        $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM citizen WHERE NID = ?");
        mysqli_stmt_bind_param($stmt_fetch, "s", $nid_to_delete);
        mysqli_stmt_execute($stmt_fetch);
        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
        $citizen_to_move = mysqli_fetch_assoc($result_fetch);
        mysqli_stmt_close($stmt_fetch);

        if ($citizen_to_move) {
            // Insert into deceased_citizen table
            $stmt_insert_deceased = mysqli_prepare($conn, "INSERT INTO deceased_citizen (NID, first_name, last_name, email, address, phone, gender, reg_date, death_certificate_number, death_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE)");
            mysqli_stmt_bind_param($stmt_insert_deceased, "sssssssss", $citizen_to_move['NID'], $citizen_to_move['first_name'], $citizen_to_move['last_name'], $citizen_to_move['email'], $citizen_to_move['address'], $citizen_to_move['phone'], $citizen_to_move['gender'], $citizen_to_move['reg_date'], $death_cert);
            mysqli_stmt_execute($stmt_insert_deceased);
            mysqli_stmt_close($stmt_insert_deceased);

            // Delete from citizen table
            $stmt_delete_citizen = mysqli_prepare($conn, "DELETE FROM citizen WHERE NID = ?");
            mysqli_stmt_bind_param($stmt_delete_citizen, "s", $nid_to_delete);
            mysqli_stmt_execute($stmt_delete_citizen);
            mysqli_stmt_close($stmt_delete_citizen);
        }

        mysqli_commit($conn);
        $search_message = "<p style='color:green;'>Citizen record successfully moved to deceased.</p>";
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);
        $search_message = "<p style='color:red;'>Error marking citizen as deceased: " . $exception->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CiviTrack</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .update-form { display: none; }
        .delete-form { display:inline-block; }
        .search-form { background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .search-form input[type="text"], .search-form button { padding: 10px; margin-right: 10px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>This is your dashboard. You have full control over all citizen records.</p>
        
        <div class="search-form">
            <h3>Search for a Citizen</h3>
            <form action="admin_dashboard.php" method="post">
                <label for="nid">Search by NID:</label>
                <input type="text" id="nid" name="nid">
                <button type="submit" name="search_nid">Search</button>
            </form>
            <br>
            <form action="admin_dashboard.php" method="post">
                <label for="first_name">Search by Name & Address:</label>
                <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                <input type="text" id="address" name="address" placeholder="Address" required>
                <button type="submit" name="search_name_address">Search</button>
            </form>
            <?php echo $search_message; ?>
        </div>

        <?php if ($all_citizens): ?>
            <div class="section">
                <h3>Citizens Found (Name & Address Search)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>NID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_citizens as $citizen): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($citizen['NID']); ?></td>
                                <td><?php echo htmlspecialchars($citizen['first_name'] . ' ' . $citizen['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($citizen['email']); ?></td>
                                <td><?php echo htmlspecialchars($citizen['address']); ?></td>
                                <td>
                                    <form action="admin_dashboard.php" method="post" class="delete-form">
                                        <input type="hidden" name="search_nid" value="1">
                                        <input type="hidden" name="nid" value="<?php echo htmlspecialchars($citizen['NID']); ?>">
                                        <button type="submit">View Details</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($citizen_details): ?>
        <div class="section">
            <h3>Citizen Records for NID: <?php echo htmlspecialchars($citizen_details['NID']); ?></h3>
            
            <h4>Personal Information</h4>
            <ul>
                <li><strong>Full Name:</strong> <?php echo htmlspecialchars($citizen_details['first_name'] . ' ' . $citizen_details['last_name']); ?></li>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($citizen_details['email']); ?></li>
                <li><strong>Address:</strong> <?php echo htmlspecialchars($citizen_details['address']); ?></li>
                <li><strong>Phone:</strong> <?php echo htmlspecialchars($citizen_details['phone']); ?></li>
                <li><strong>Gender:</strong> <?php echo htmlspecialchars($citizen_details['gender']); ?></li>
            </ul>

            <div class="record-form">
                <h4>Add New Record</h4>
                <form action="admin_dashboard.php" method="post">
                    <input type="hidden" name="add_record" value="1">
                    <input type="hidden" name="nid" value="<?php echo htmlspecialchars($citizen_details['NID']); ?>">
                    
                    <label for="record_type">Record Type:</label>
                    <select name="record_type" id="record_type" onchange="showRecordFields()" required>
                        <option value="">Select Record Type</option>
                        <option value="tax">Tax Record</option>
                        <option value="employment">Employment Record</option>
                        <option value="criminal">Criminal Record</option>
                    </select>
                    
                    <div id="tax_fields" style="display:none;">
                        <label for="year">Year:</label>
                        <input type="number" name="year" min="2000" max="2030">
                        <label for="yearly_income">Yearly Income:</label>
                        <input type="number" name="yearly_income" step="0.01">
                        <label for="tax_amount">Tax Amount:</label>
                        <input type="number" name="tax_amount" step="0.01">
                        <label for="payment_status">Payment Status:</label>
                        <select name="payment_status">
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    
                    <div id="employment_fields" style="display:none;">
                        <label for="company_name">Company Name:</label>
                        <input type="text" name="company_name">
                        <label for="job_title">Job Title:</label>
                        <input type="text" name="job_title">
                        <label for="start_date">Start Date:</label>
                        <input type="date" name="start_date">
                        <label for="end_date">End Date (optional):</label>
                        <input type="date" name="end_date">
                        <label for="salary">Salary (optional):</label>
                        <input type="number" name="salary" step="0.01">
                    </div>
                    
                    <div id="criminal_fields" style="display:none;">
                        <label for="case_type">Case Type:</label>
                        <input type="text" name="case_type">
                        <label for="date_of_offence">Date of Offence:</label>
                        <input type="date" name="date_of_offence">
                        <label for="case_status">Case Status:</label>
                        <select name="case_status">
                            <option value="pending">Pending</option>
                            <option value="under_investigation">Under Investigation</option>
                            <option value="closed">Closed</option>
                            <option value="convicted">Convicted</option>
                            <option value="acquitted">Acquitted</option>
                        </select>
                        <label for="penalty">Penalty (optional):</label>
                        <input type="text" name="penalty">
                        <label for="description">Description (optional):</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                    
                    <button type="submit">Add Record</button>
                </form>
            </div>

            <div class="record-form">
                <h4>Mark as Deceased</h4>
                <form action="admin_dashboard.php" method="post">
                    <input type="hidden" name="mark_deceased" value="1">
                    <input type="hidden" name="nid_to_delete" value="<?php echo htmlspecialchars($citizen_details['NID']); ?>">
                    <label for="death_certificate_number">Death Certificate Number:</label>
                    <input type="text" name="death_certificate_number" required>
                    <button type="submit" onclick="return confirm('Are you sure you want to mark this citizen as deceased? This action is irreversible.');">Mark as Deceased</button>
                </form>
            </div>

            <h4>Tax Records</h4>
            <?php if (count($tax_records) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Yearly Income</th>
                            <th>Tax Amount</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tax_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['year']); ?></td>
                                <td><?php echo htmlspecialchars($record['yearly_income']); ?></td>
                                <td><?php echo htmlspecialchars($record['tax_amount']); ?></td>
                                <td><?php echo htmlspecialchars($record['payment_status']); ?></td>
                                <td>
                                    <form action="admin_dashboard.php" method="post" class="delete-form">
                                        <input type="hidden" name="delete_record" value="1">
                                        <input type="hidden" name="record_id" value="<?php echo $record['tax_record_id']; ?>">
                                        <input type="hidden" name="table_name" value="tax_record">
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                                    </form>
                                </td>
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
                            <th>Actions</th>
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
                                <td>
                                    <form action="admin_dashboard.php" method="post" class="delete-form">
                                        <input type="hidden" name="delete_record" value="1">
                                        <input type="hidden" name="record_id" value="<?php echo $record['employment_record_id']; ?>">
                                        <input type="hidden" name="table_name" value="employment_record">
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                                    </form>
                                </td>
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
                                    <form action="admin_dashboard.php" method="post" class="delete-form">
                                        <input type="hidden" name="delete_record" value="1">
                                        <input type="hidden" name="record_id" value="<?php echo $record['criminal_record_id']; ?>">
                                        <input type="hidden" name="table_name" value="criminal_record">
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
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
        
        <p><a href="logout.php">Log Out</a></p>
    </div>

    <script>
        function showRecordFields() {
            var recordType = document.getElementById('record_type').value;
            
            // Hide all field groups
            document.getElementById('tax_fields').style.display = 'none';
            document.getElementById('employment_fields').style.display = 'none';
            document.getElementById('criminal_fields').style.display = 'none';
            
            // Show the selected field group
            if (recordType === 'tax') {
                document.getElementById('tax_fields').style.display = 'block';
            } else if (recordType === 'employment') {
                document.getElementById('employment_fields').style.display = 'block';
            } else if (recordType === 'criminal') {
                document.getElementById('criminal_fields').style.display = 'block';
            }
        }
    </script>
</body>
</html>
<?php
// Close the database connection
mysqli_close($conn);
?>
