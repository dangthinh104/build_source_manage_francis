<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Files</title>
</head>
<body>
    <h1>Log Files in {{ $subfolder ? $subfolder : '/var/www/html/log_pm2' }}</h1>

    @if ($subfolder)
        <a href="{{ route('logs.index', ['subfolder' => dirname($subfolder)]) }}">Back</a>
    @endif

    <h2>Folders:</h2>
    <ul>
        @foreach ($folders as $folder)
            <li>
                <a href="{{ route('logs.index', ['subfolder' => $subfolder ? $subfolder . '/' . basename($folder) : basename($folder)]) }}">
                    {{ basename($folder) }}
                </a>
            </li>
        @endforeach
    </ul>

    <h2>Files:</h2>
    <ul>
        @foreach ($files as $file)
            <li>
                <a href="{{ route('logs.view', ['subfolder' => $subfolder, 'filename' => $file->getFilename()]) }}"> {{ $file->getFilename() }}</a>
                <a href="{{ route('logs.view', ['subfolder' => $subfolder, 'filename' => $file->getFilename()]) }}">View</a> |
                <a href="{{ route('logs.download', ['subfolder' => $subfolder, 'filename' => $file->getFilename()]) }}">Download</a>
            </li>
        @endforeach
    </ul>
</body>
</html>
