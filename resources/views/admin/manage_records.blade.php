<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Records</title>
</head>
<body>
    <h2>Manage Records</h2>

    <h3>Add Criminal Record</h3>
    <form action="/admin/records/criminal" method="POST">
        @csrf
        <div>
            <label for="nid">Citizen NID</label>
            <input type="text" id="nid" name="nid" required>
        </div>
        <div>
            <label for="case_type">Case Type</label>
            <input type="text" id="case_type" name="case_type" required>
        </div>
        <div>
            <label for="penalty">Penalty</label>
            <input type="text" id="penalty" name="penalty">
        </div>
        <button type="submit">Add Record</button>
    </form>
    
    </body>
</html>