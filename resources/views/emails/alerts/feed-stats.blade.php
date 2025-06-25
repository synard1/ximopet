<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $alertTitle }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .header {
            background-color: {
                    {
                    $levelColor
                }
            }

            ;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .content {
            padding: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .data-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $alertTitle }}</h1>
        <p>{{ $priorityBadge }}</p>
    </div>

    <div class="content">
        <h3>Alert Summary</h3>
        <p><strong>Type:</strong> {{ $alertType }}</p>
        <p><strong>Level:</strong> {{ $alertLevel }}</p>
        <p><strong>Message:</strong> {{ $alertMessage }}</p>
        <p><strong>Timestamp:</strong> {{ $timestamp }}</p>

        @if(!empty($formattedData))
        @foreach($formattedData as $sectionTitle => $sectionData)
        <h4>{{ $sectionTitle }}</h4>
        @if(is_array($sectionData))
        <table class="data-table">
            @foreach($sectionData as $key => $value)
            <tr>
                <th>{{ $key }}</th>
                <td>
                    @if(is_array($value))
                    {{ json_encode($value) }}
                    @else
                    {{ $value }}
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
        @else
        <p>{{ $sectionData }}</p>
        @endif
        @endforeach
        @endif

        <hr>
        <p><em>This is an automated alert from the Feed Management System.</em></p>
        <p><em>Generated at {{ $timestamp }}</em></p>
    </div>
</body>

</html>