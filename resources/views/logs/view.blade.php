<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viewing Log File: {{ $filename }}</title>
</head>
<body>
    <h1>Log File: {{ $filename }}</h1>
    <pre>{{ $content }}</pre>
    <a href="{{ route('logs.download', ['subfolder' => $subfolder, 'filename' => $filename]) }}">Download</a>
</body>
</html>
