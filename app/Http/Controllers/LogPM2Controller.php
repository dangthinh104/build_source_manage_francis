<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class LogPM2Controller extends Controller
{
    protected string $basePath;
    
    public function __construct() {
        // Admin can view, Super Admin has full access
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Forbidden. Only Admin or Super Admin can view PM2 logs.');
        }
        
        $this->basePath = env('LOG_PM2_PATH', '/var/www/html/log_pm2');
    }

    // Display the list of log folders and files
    public function index($subfolder = '')
    {
        $directoryPath = $this->basePath . ($subfolder ? '/' . $subfolder : '');

        if (!File::exists($directoryPath)) {
            abort(404, "Directory not found");
        }

        // Get folders and files in the current directory
        $folders = array_map('basename', File::directories($directoryPath));
        $files = array_map('basename', File::files($directoryPath));

        return Inertia::render('Logs/Index', [
            'folders' => $folders,
            'files' => $files,
            'subfolder' => $subfolder
        ]);
    }

    // View the contents of a log file
    public function view($subfolder, $filename)
    {
        $filePath = $this->basePath . '/' . $subfolder . '/' . $filename;

        if (File::exists($filePath)) {
            $content = File::get($filePath);
            return Inertia::render('Logs/View', [
                'filename' => $filename,
                'content' => $content,
                'subfolder' => $subfolder
            ]);
        } else {
            abort(404, "File not found");
        }
    }

    // Download the log file
    public function download($subfolder, $filename)
    {
        $filePath = $this->basePath . '/' . $subfolder . '/' . $filename;

        if (File::exists($filePath)) {
            $content = File::get($filePath);
            // Step 2: Create the log file
            $filePathTemp = public_path('build/' . $filename);

            file_put_contents($filePathTemp, $content);
            return response()->download($filePathTemp)->deleteFileAfterSend();
        } else {
            abort(404, "File not found");
        }
    }
}
