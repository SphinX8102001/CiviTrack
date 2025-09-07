<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - CiviTrack</title>
</head>
<body>
    <h2>Login to CiviTrack</h2>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div>
            <label for="nid">National ID (NID)</label>
            <input type="text" id="nid" name="nid" required autofocus>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>
</body>
</html>