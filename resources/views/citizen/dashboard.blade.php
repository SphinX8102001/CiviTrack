<!DOCTYPE html>
<html lang="en">
<head>
    <title>Citizen Dashboard</title>
</head>
<body>
    <h2>Welcome, {{ Auth::user()->citizen->f_name }}</h2>
    <p>NID: {{ Auth::user()->citizen->nid }}</p>

    <h3>Tax Records</h3>
    <table>
        <thead>
            <tr>
                <th>Year</th>
                <th>Tax Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach(Auth::user()->citizen->taxRecords as $record)
                <tr>
                    <td>{{ $record->year }}</td>
                    <td>{{ $record->tax_amount }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Employment Records</h3>
    <h3>Criminal Records</h3>
    </body>
</html>