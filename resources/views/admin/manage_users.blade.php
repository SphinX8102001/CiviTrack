<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Users</title>
</head>
<body>
    <h2>Manage Users</h2>
    <table>
        <thead>
            <tr>
                <th>NID</th>
                <th>Name</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->citizen->nid }}</td>
                <td>{{ $user->citizen->f_name }} {{ $user->citizen->l_name }}</td>
                <td>{{ $user->role }}</td>
                <td>
                    <a href="#">Edit</a>
                    <form action="/admin/users/{{ $user->id }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>