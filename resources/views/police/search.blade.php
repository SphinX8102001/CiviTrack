<!DOCTYPE html>
<html lang="en">
<head>
    <title>Police Search</title>
</head>
<body>
    <h2>Search Citizen by NID</h2>
    <form method="GET" action="{{ route('police.citizen_details') }}">
        <label for="nid">Enter NID:</label>
        <input type="text" id="nid" name="nid" required>
        <button type="submit">Search</button>
    </form>
</body>
</html>