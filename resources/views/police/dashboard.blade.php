<!DOCTYPE html>
<html lang="en">
<head>
    <title>Police Dashboard</title>
</head>
<body>
    <h2>Police Dashboard</h2>
    <form method="GET" action="{{ route('police.search') }}">
        <label for="search_nid">Search Citizen by NID:</label>
        <input type="text" id="search_nid" name="search_nid" placeholder="Enter NID" required>
        <button type="submit">Search</button>
    </form>

    @if(isset($citizen))
        <h3>Citizen Information for {{ $citizen->f_name }}</h3>
        <p>NID: {{ $citizen->nid }}</p>
        <h4>Criminal Records</h4>
        @if($citizen->criminalRecords->isEmpty())
            <p>No criminal records found.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Case Type</th>
                        <th>Status</th>
                        <th>Penalty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($citizen->criminalRecords as $record)
                        <tr>
                            <td>{{ $record->case_type }}</td>
                            <td>{{ $record->case_status }}</td>
                            <td>{{ $record->penalty }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif
</body>
</html>