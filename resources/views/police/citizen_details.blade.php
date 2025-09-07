<!DOCTYPE html>
<html lang="en">
<head>
    <title>Citizen Details</title>
</head>
<body>
    <h2>Citizen Details</h2>

    @if(isset($citizen))
        <h3>Personal Information</h3>
        <p><strong>Name:</strong> {{ $citizen->f_name }} {{ $citizen->l_name }}</p>
        <p><strong>NID:</strong> {{ $citizen->nid }}</p>
        <p><strong>Address:</strong> {{ $citizen->address }}</p>
        <p><strong>Phone:</strong> {{ $citizen->phone }}</p>

        <h3>Criminal Records</h3>
        @if($citizen->criminalRecords->isEmpty())
            <p>No criminal records found.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Case Type</th>
                        <th>Status</th>
                        <th>Penalty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($citizen->criminalRecords as $record)
                    <tr>
                        <td>{{ $record->case_type }}</td>
                        <td>{{ $record->case_status }}</td>
                        <td>{{ $record->penalty }}</td>
                        <td>
                            <a href="#">Update</a> </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <p>Citizen not found.</p>
    @endif
</body>
</html>