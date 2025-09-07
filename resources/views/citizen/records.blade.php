<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Records</title>
</head>
<body>
    <h2>My Records</h2>

    <h3>Tax Records</h3>
    <table>
        <thead>
            <tr>
                <th>Year</th>
                <th>Amount</th>
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
    <table>
        <thead>
            <tr>
                <th>Company</th>
                <th>Job Title</th>
                <th>Start Date</th>
                <th>End Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach(Auth::user()->citizen->employmentRecords as $record)
            <tr>
                <td>{{ $record->company_name }}</td>
                <td>{{ $record->job_title }}</td>
                <td>{{ $record->start_date }}</td>
                <td>{{ $record->end_date }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <h3>Criminal Records</h3>
    <table>
        <thead>
            <tr>
                <th>Case Type</th>
                <th>Status</th>
                <th>Penalty</th>
            </tr>
        </thead>
        <tbody>
            @foreach(Auth::user()->citizen->criminalRecords as $record)
            <tr>
                <td>{{ $record->case_type }}</td>
                <td>{{ $record->case_status }}</td>
                <td>{{ $record->penalty }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>