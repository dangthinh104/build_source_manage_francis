<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Parameter;
use App\Repositories\Interfaces\MySiteRepositoryInterface;
use App\Services\LogParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * LogPM2Controller
 * 
 * Handles PM2 log file viewing and downloading.
 * Read-only operations - no database writes.
 */
class LogPM2Controller extends BaseController
{
    protected string $basePath;
    
    /**
     * Constructor - inject dependencies
     */
    public function __construct(
        protected LogParserService $logParser,
        protected MySiteRepositoryInterface $mySiteRepository
    ) {
        // Authorization handled by middleware in routes
        $this->basePath = Parameter::getValue('LOG_PM2_PATH', env('LOG_PM2_PATH', '/var/www/html/log_pm2'));
    }

    /**
     * Display the list of log folders and files
     */
    public function index(string $subfolder = ''): InertiaResponse
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
        // Use repository to get site mappings
        $sitesList = $this->mySiteRepository->all(['id', 'site_name', 'port_pm2']);
        $sites = collect($sitesList)->whereIn('site_name', $folderNames)->keyBy('site_name');

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

    /**
     * Raw Log Viewer - displays file content with pagination
     */
    public function raw(Request $request, string $subfolder, string $filename): InertiaResponse
    {
        // Validate path to prevent directory traversal
        $this->validatePath($subfolder, $filename);
        
        $filePath = $this->basePath . '/' . $subfolder . '/' . $filename;

        if (!File::exists($filePath)) {
            abort(404, "File not found: $filename");
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(50, (int) $request->query('limit', 200));

        // Use the paginated reader
        $result = $this->logParser->readLogFile($filePath, $limit, $page);

        return Inertia::render('Logs/Raw', [
            'filename' => $filename,
            'subfolder' => $subfolder,
            'logData' => $result, // Pass the entire result (data, links, meta)
        ]);
    }

    /**
     * Advance Log Viewer - parses, strips ANSI, groups stack traces
     */
    public function advance(Request $request, string $subfolder, string $filename): InertiaResponse
    {
        // Validate path to prevent directory traversal
        $this->validatePath($subfolder, $filename);
        
        $filePath = $this->basePath . '/' . $subfolder . '/' . $filename;

        if (!File::exists($filePath)) {
            abort(404, "File not found");
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = min(200, max(20, (int) $request->query('limit', 100)));
        $query = $request->query('query', null);

        // Parse log file with stack trace grouping
        $result = $this->logParser->readLogFileAdvance($filePath, $limit, $page, $query);

        // Get available files in this folder for file selector
        $folderPath = $this->basePath . '/' . $subfolder;
        $availableFiles = [];
        if (File::exists($folderPath) && File::isDirectory($folderPath)) {
            $availableFiles = array_map('basename', File::files($folderPath));
            sort($availableFiles);
        }

        return Inertia::render('Logs/Advance', [
            'filename' => $filename,
            'subfolder' => $subfolder,
            'logData' => $result, // Contains data, links, meta
            'availableFiles' => $availableFiles,
            'currentQuery' => $query,
        ]);
    }

    /**
     * Legacy view method - redirect to advance
     */
    public function view(Request $request, $subfolder, $filename)
    {
        return redirect()->route('logs.advance', [
            'subfolder' => $subfolder,
            'filename' => $filename,
        ]);
    }

    /**
     * Download the log file
     */
    public function download(string $subfolder, string $filename): StreamedResponse
    {
        // Validate path to prevent directory traversal
        $this->validatePath($subfolder, $filename);
        
        $filePath = $this->basePath . '/' . $subfolder . '/' . $filename;

        if (!File::exists($filePath)) {
            abort(404, "File not found");
        }

        // Use streamDownload to avoid creating temp files
        return response()->streamDownload(function() use ($filePath) {
            echo File::get($filePath);
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Validate path to prevent directory traversal attacks
     */
    private function validatePath(string $subfolder, string $filename): void
    {
        if (str_contains($subfolder, '..') || str_contains($filename, '..')) {
            abort(400, 'Invalid path');
        }
        
        if (str_contains($subfolder, '/') || str_contains($filename, '/')) {
            abort(400, 'Invalid path');
        }
    }
    
    /**
     * Format file size in human-readable format
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'TB');
    }
}
