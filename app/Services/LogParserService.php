<?php

namespace App\Services;

use SplFileObject;

class LogParserService
{
    /**
     * PM2 timestamp regex pattern
     * Matches: 2025-11-18 05:13 +00:00:
     */
    private const TIMESTAMP_PATTERN = '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2} [+-]\d{2}:\d{2}):\s*(.*)/';

    /**
     * ANSI escape code pattern
     * Matches: \033[0m, \033[33m, etc.
     */
    private const ANSI_PATTERN = '/\x1b\[[0-9;]*m/';

    /**
     * Parse a single log line
     *
     * @param string $line Raw log line
     * @param bool $isErrorFile Whether this is an error log file
     * @return array Parsed log entry
     */
    public function parseLogLine(string $line, bool $isErrorFile = false): array
    {
        $timestamp = null;
        $message = $line;

        // Try to match PM2 timestamp pattern
        if (preg_match(self::TIMESTAMP_PATTERN, $line, $matches)) {
            $timestamp = $matches[1];
            $message = $matches[2];
        }

        // Strip ANSI escape codes from message
        $message = preg_replace(self::ANSI_PATTERN, '', $message);
        $message = trim($message);

        // Detect log level
        $level = $this->detectLevel($message, $isErrorFile);

        return [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'raw' => $line,
        ];
    }

    /**
     * Detect log level from message content
     *
     * @param string $message Log message
     * @param bool $isErrorFile Whether this is an error log file
     * @return string Log level (ERROR, HTTP, INFO)
     */
    private function detectLevel(string $message, bool $isErrorFile): string
    {
        // Check for error indicators
        if ($isErrorFile || 
            stripos($message, 'error') !== false || 
            stripos($message, 'exception') !== false ||
            stripos($message, 'fatal') !== false ||
            stripos($message, 'failed') !== false ||
            preg_match('/TypeError|ReferenceError|SyntaxError/', $message)) {
            return 'ERROR';
        }

        // Check for HTTP request logs
        if (preg_match('/^(GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS)\s+/', $message)) {
            // Check HTTP status code for errors
            if (preg_match('/\s(4\d{2}|5\d{2})\s/', $message)) {
                return 'HTTP_ERROR';
            }
            return 'HTTP';
        }

        // Check for warning indicators
        if (stripos($message, 'warning') !== false || 
            stripos($message, 'warn') !== false ||
            stripos($message, 'deprecated') !== false) {
            return 'WARNING';
        }

        // Check for debug/trace indicators
        if (stripos($message, 'debug') !== false) {
            return 'DEBUG';
        }

        return 'INFO';
    }

    /**
     * Read log file with pagination (newest entries first)
     *
     * @param string $filePath Path to log file
     * @param int $limit Number of lines per page
     * @param int $page Page number (1-indexed)
     * @return array Parsed logs with pagination info
     */
    /**
     * Read log file with pagination (newest entries first)
     *
     * @param string $filePath Path to log file
     * @param int $limit Number of lines per page
     * @param int $page Page number (1-indexed)
     * @return array Parsed logs with pagination info
     */
    public function readLogFile(string $filePath, int $limit = 200, int $page = 1): array
    {
        if (!file_exists($filePath)) {
            return [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $limit,
                'total' => 0,
                'prev_page_url' => null,
                'next_page_url' => null,
                'file_size' => 0,
                'links' => [],
            ];
        }

        $isErrorFile = stripos(basename($filePath), 'error') !== false;
        $fileSize = filesize($filePath);

        // Count total lines using a generator or efficient method
        $totalLines = 0;
        $handle = fopen($filePath, "r");
        while(!feof($handle)){
            $line = fgets($handle);
            if($line !== false) $totalLines++;
        }
        fclose($handle);

        // Calculate pagination parameters
        $totalPages = max(1, (int) ceil($totalLines / $limit));
        $page = max(1, min($page, $totalPages));

        // Calculate line range
        // We want to show "newest" first if viewing logs generally implies tailing
        // But the previous implementation did "reverse to show newest first"
        // Let's stick to standard reading but mapping "Page 1" to "Last N lines"
        
        $offset = max(0, $totalLines - ($page * $limit));
        $linesToRead = $limit;
        
        // Adjust if we are on the very last page (which corresponds to the top of the file)
        if ($offset == 0) {
             $linesToRead = $totalLines - (($page - 1) * $limit);
        }

        // However, standard Laravel pagination usually implies:
        // Page 1: Items 1-10
        // But for LOGS, user expects Page 1 to be the LATEST logs.
        // So User's "Page 1" = File Lines [Total-Limit .. Total]
        // User's "Page 2" = File Lines [Total-2*Limit .. Total-Limit]
        
        // This math is:
        // Start Reading at: max(0, TotalLines - (Page * Limit))
        // Number of lines: Limit (or less if at start of file)
        
        $startLine = max(0, $totalLines - ($page * $limit)); // 0-indexed line number
        // If startLine is 0, we read up to ($limit) lines or until ($totalLines - ($page-1)*$limit)
        
        // Wait, let's simplify.
        // File Content: [Line 1, Line 2, ... Line 100]
        // Limit: 10
        // Page 1 (Newest): Should show Lines 91-100
        // Page 2: Lines 81-90
        // ...
        // Page 10: Lines 1-10
        
        // Logic:
        // $startLine = $totalLines - ($page * $limit);
        // if $startLine < 0, it means we are asking for more than exists at the top.
        // Real Start = max(0, $totalLines - ($page * $limit))
        // Lines to read = $limit.
        // But if real start was clamped to 0, we only read ($totalLines % $limit) or similar.
        // Actually, we can just read $limit lines starting from $startLine.
        // If $page * $limit > $totalLines + $limit (way out of bounds), empty.
        
        $file = new SplFileObject($filePath, 'r');
        $file->seek($startLine); // Seek to the determined start line
        
        $logs = [];
        $count = 0;
        
        // Read lines
        while (!$file->eof() && $count < $limit) {
            $line = $file->current();
            if (trim($line) !== '') {
                 $parsed = $this->parseLogLine($line, $isErrorFile);
                 $parsed['line_number'] = $startLine + $count + 1;
                 $logs[] = $parsed;
            }
            $file->next();
            $count++;
        }

        // Reverse to show newest at top of the page list
        $logs = array_reverse($logs);

        // Generate URL helpers (mocking Laravel Pagination)
        $buildUrl = function($p) { return "?page={$p}"; };
        
        $links = $this->generateLinks($page, $totalPages, $buildUrl);

        return [
            'data' => $logs,
            'current_page' => $page,
            'last_page' => $totalPages,
            'per_page' => $limit,
            'total' => $totalLines,
            'prev_page_url' => $page > 1 ? $buildUrl($page - 1) : null,
            'next_page_url' => $page < $totalPages ? $buildUrl($page + 1) : null,
            'links' => $links,
            'file_size' => $fileSize,
        ];
    }

    /**
     * Generate pagination links array similar to Laravel's
     */
    private function generateLinks(int $page, int $totalPages, callable $urlGenerator): array
    {
        $links = [];
        
        // Previous
        $links[] = [
            'url' => $page > 1 ? $urlGenerator($page - 1) : null,
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        // Pages
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)) {
                 $links[] = [
                    'url' => $urlGenerator($i),
                    'label' => (string)$i,
                    'active' => $i == $page,
                 ];
            } elseif (count($links) > 0 && $links[count($links)-1]['label'] !== '...') {
                 $links[] = ['url' => null, 'label' => '...', 'active' => false];
            }
        }

        // Next
        $links[] = [
            'url' => $page < $totalPages ? $urlGenerator($page + 1) : null,
            'label' => 'Next &raquo;',
            'active' => false,
        ];
        
        return $links;
    }

    /**
     * Read log file with stack trace grouping for Advance view
     * Groups consecutive lines without timestamps as part of previous entry's stack trace
     *
     * @param string $filePath Path to log file
     * @param int $limit Number of entries per page
     * @param int $page Page number (1-indexed)
     * @param string|null $query Search filter
     * @return array Parsed and grouped logs
     */
    public function readLogFileAdvance(string $filePath, int $limit = 100, int $page = 1, ?string $query = null): array
    {
        if (!file_exists($filePath)) {
            return [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $limit,
                'total' => 0,
                'prev_page_url' => null,
                'next_page_url' => null,
                'links' => [],
                'file_size' => 0,
            ];
        }

        $isErrorFile = stripos(basename($filePath), 'error') !== false;
        $fileSize = filesize($filePath);

        // Read all lines and group by entries
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::DROP_NEW_LINE);

        $entries = [];
        $currentEntry = null;
        $lineNum = 0;

        foreach ($file as $line) {
            $lineNum++;
            if (trim($line) === '') continue;

            // Check if this line starts with a timestamp
            if (preg_match(self::TIMESTAMP_PATTERN, $line, $matches)) {
                // Save previous entry if exists
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                }

                // Strip ANSI from message
                $message = preg_replace(self::ANSI_PATTERN, '', $matches[2]);
                $message = trim($message);

                $currentEntry = [
                    'timestamp' => $matches[1],
                    'message' => $message,
                    'level' => $this->detectLevel($message, $isErrorFile),
                    'stack_trace' => [],
                    'line_number' => $lineNum,
                ];
            } else {
                // This is a continuation line (stack trace)
                if ($currentEntry !== null) {
                    // Clean the line
                    $cleanLine = preg_replace(self::ANSI_PATTERN, '', $line);
                    $cleanLine = trim($cleanLine);
                    if (!empty($cleanLine)) {
                        $currentEntry['stack_trace'][] = $cleanLine;
                        // If stack trace contains error keywords, upgrade level
                        if ($currentEntry['level'] !== 'ERROR' && 
                            (stripos($cleanLine, 'Error') !== false || stripos($cleanLine, 'Exception') !== false)) {
                            $currentEntry['level'] = 'ERROR';
                        }
                    }
                }
            }
        }

        // Don't forget the last entry
        if ($currentEntry !== null) {
            $entries[] = $currentEntry;
        }

        // Apply search filter if provided
        if ($query && trim($query) !== '') {
            $queryLower = strtolower($query);
            $entries = array_filter($entries, function($entry) use ($queryLower) {
                // Search in message
                if (stripos($entry['message'], $queryLower) !== false) {
                    return true;
                }
                // Search in stack trace
                foreach ($entry['stack_trace'] as $traceLine) {
                    if (stripos($traceLine, $queryLower) !== false) {
                        return true;
                    }
                }
                return false;
            });
            $entries = array_values($entries); // Re-index
        }

        // Reverse to show newest first
        $entries = array_reverse($entries);

        // Calculate pagination
        $totalEntries = count($entries);
        $totalPages = max(1, (int) ceil($totalEntries / $limit));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        // Slice for current page
        $pagedEntries = array_slice($entries, $offset, $limit);

        // Build URL generator
        $buildUrl = function($p) use ($query) { 
            $url = "?page={$p}";
            if ($query) {
                $url .= "&query=" . urlencode($query);
            }
            return $url;
        };

        return [
            'data' => $pagedEntries,
            'current_page' => $page,
            'last_page' => $totalPages,
            'per_page' => $limit,
            'total' => $totalEntries,
            'prev_page_url' => $page > 1 ? $buildUrl($page - 1) : null,
            'next_page_url' => $page < $totalPages ? $buildUrl($page + 1) : null,
            'links' => $this->generateLinks($page, $totalPages, $buildUrl),
            'file_size' => $fileSize,
            'file_size_formatted' => $this->formatFileSize($fileSize),
        ];
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
