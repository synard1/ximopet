<!DOCTYPE html>
<html>

<head>
    <title>Info Export</title>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fffbe6;
            color: #333;
            padding: 40px;
        }

        .info-box {
            max-width: 500px;
            margin: 60px auto;
            background: #fffbe6;
            border: 1px solid #ffe082;
            border-radius: 8px;
            box-shadow: 0 2px 8px #ffe08233;
            padding: 32px;
            text-align: center;
        }

        .info-box h2 {
            color: #f39c12;
            margin-bottom: 16px;
        }

        .info-box p {
            font-size: 1.2em;
        }
    </style>
</head>

<body>
    <div class="info-box">
        <h2>Info Export</h2>
        <p>{{ $message }}</p>
    </div>
</body>

</html>