<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - CiviTrack</title>
</head>
<body>
    <h2>Register as a Citizen</h2>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div>
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        <div>
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>
        <div>
            <label for="nid">National ID (NID)</label>
            <input type="text" id="nid" name="nid" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit">Register</button>
    </form>
</body>
</html>