<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile</title>
</head>
<body>
    <h2>My Profile</h2>
    <form method="POST" action="/citizen/profile/update">
        @csrf
        @method('PUT')
        <div>
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="{{ Auth::user()->citizen->f_name }}" required>
        </div>
        <div>
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="{{ Auth::user()->citizen->l_name }}" required>
        </div>
        <div>
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="{{ Auth::user()->citizen->address }}" required>
        </div>
        <div>
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" value="{{ Auth::user()->citizen->phone }}" required>
        </div>
        <button type="submit">Update Profile</button>
    </form>
</body>
</html>