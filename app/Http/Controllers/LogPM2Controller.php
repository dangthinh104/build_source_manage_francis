<?php
namespace App\Http\Controllers;

use App\Models\Parameter;
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
        
        $this->basePath = Parameter::getValue('LOG_PM2_PATH', env('LOG_PM2_PATH', '/var/www/html/log_pm2'));
    }

    // Display the list of log folders and files
    public function index($subfolder = '')
    {
        $directoryPath = $this->basePath . ($subfolder ? '/' . $subfolder : '');

        $notFound = false;
        $requestedFolder = $subfolder;
        $basePathError = null;

        // Check if base path exists first
        if (!File::exists($this->basePath) || !File::isDirectory($this->basePath)) {
            return Inertia::render('Logs/Index', [
                'folders' => [],
                'files' => [],
                'subfolder' => '',
                'notFound' => false,
                'requestedFolder' => '',
                'basePathError' => "Log directory does not exist: {$this->basePath}. Please check LOG_PM2_PATH parameter.",
            ]);
        }

        // If directory doesn't exist, show root folders with 404 warning
        if (!File::exists($directoryPath)) {
            $notFound = true;
            $directoryPath = $this->basePath;
            $subfolder = '';
        }

        // Get folder names
        $folderNames = array_map('basename', File::directories($directoryPath));
        $files = array_map('basename', File::files($directoryPath));
        
        // Sort folders alphabetically for better UX
        sort($folderNames);

        // Get site mappings (site_name matches folder name)
        $sites = \App\Models\MySite::whereIn('site_name', $folderNames)
            ->get()
            ->keyBy('site_name');

        // Build detailed folder info
        $folders = [];
        foreach ($folderNames as $folderName) {
            $folderPath = $directoryPath . '/' . $folderName;
            $folderFiles = File::files($folderPath);
            
            $errorFiles = [];
            $outFiles = [];
            $latestErrorFile = null;
            $latestErrorTime = 0;
            
            foreach ($folderFiles as $file) {
                $fileName = $file->getFilename();
                $modifiedTime = $file->getMTime();
                
                if (str_contains($fileName, 'error')) {
                    $errorFiles[] = $fileName;
                    if ($modifiedTime > $latestErrorTime) {
                        $latestErrorTime = $modifiedTime;
                        $latestErrorFile = $fileName;
                    }
                } elseif (str_contains($fileName, 'out')) {
                    $outFiles[] = $fileName;
                }
            }
            
            $site = $sites->get($folderName);
            
            $folders[] = [
                'name' => $folderName,
                'error_count' => count($errorFiles),
                'out_count' => count($outFiles),
                'total_files' => count($folderFiles),
                'latest_error_file' => $latestErrorFile,
                'latest_error_time' => $latestErrorTime ? date('Y-m-d H:i:s', $latestErrorTime) : null,
                'site' => $site ? [
                    'id' => $site->id,
                    'site_name' => $site->site_name,
                    'port_pm2' => $site->port_pm2,
                ] : null,
            ];
        }

        return Inertia::render('Logs/Index', [
            'folders' => $folders,
            'files' => $files,
            'subfolder' => $subfolder,
            'notFound' => $notFound,
            'requestedFolder' => $requestedFolder,
            'basePathError' => null,
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
