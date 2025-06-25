<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }

        .alert-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .alert-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }

        .alert-level {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .level-critical {
            background-color: #dc3545;
            color: white;
        }

        .level-error {
            background-color: #fd7e14;
            color: white;
        }

        .level-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .level-info {
            background-color: #17a2b8;
            color: white;
        }

        .alert-message {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }

        .data-section {
            margin: 20px 0;
        }

        .data-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .data-table th,
        .data-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }

        .data-table tr:hover {
            background-color: #f5f5f5;
        }

        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #eee;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }

        .timestamp {
            color: #666;
            font-size: 14px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="alert-icon">
                @switch($level)
                @case('critical')
                🚨
                @break
                @case('error')
                ❌
                @break
                @case('warning')
                ⚠️
                @break
                @case('info')
                ℹ️
                @break
                @default
                🔔
                @endswitch
            </div>
            <h1 class="alert-title">{{ $title }}</h1>
            <span class="alert-level level-{{ $level }}">{{ strtoupper($level) }}</span>
        </div>

        <!-- Message -->
        <div class="alert-message">
            <p>{{ $message }}</p>
        </div>

        <!-- Timestamp -->
        <div class="timestamp">
            <strong>Alert Time:</strong> {{ $timestamp }}
        </div>

        <!-- Data Section -->
        @if(!empty($data))
        <div class="data-section">
            <h2 class="data-title">Alert Details</h2>

            @if(is_array($data))
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $key => $value)
                    <tr>
                        <td><strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong></td>
                        <td>
                            @if(is_array($value))
                            <pre
                                style="font-size: 12px; background: #f8f9fa; padding: 8px; border-radius: 4px; margin: 0;">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                            @elseif(is_bool($value))
                            {{ $value ? 'Yes' : 'No' }}
                            @elseif(is_numeric($value) && str_contains($key, 'cost'))
                            Rp {{ number_format($value, 0, ',', '.') }}
                            @elseif(is_numeric($value) && str_contains($key, 'quantity'))
                            {{ number_format($value, 2) }} kg
                            @else
                            {{ $value }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p>{{ $data }}</p>
            @endif
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated alert from the Feed Management System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>

</html>