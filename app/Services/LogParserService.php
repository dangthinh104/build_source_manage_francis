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
    public function readLogFile(string $filePath, int $limit = 200, int $page = 1): array
    {
        if (!file_exists($filePath)) {
            return [
                'logs' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $limit,
                    'total_lines' => 0,
                    'total_pages' => 0,
                ],
                'file_size' => 0,
            ];
        }

        $isErrorFile = stripos(basename($filePath), 'error') !== false;
        $fileSize = filesize($filePath);

        // Use SplFileObject for efficient file reading
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY);

        // Count total lines
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        $file->rewind();

        $totalPages = max(1, ceil($totalLines / $limit));
        $page = max(1, min($page, $totalPages));

        // Calculate offset for reading (showing newest first)
        $offset = max(0, $totalLines - ($page * $limit));
        $endLine = $totalLines - (($page - 1) * $limit);

        $logs = [];
        $lineNumber = 0;

        foreach ($file as $line) {
            $lineNumber++;
            
            // Skip lines before our range
            if ($lineNumber <= $offset) {
                continue;
            }
            
            // Stop if we've read enough
            if ($lineNumber > $endLine) {
                break;
            }

            $parsed = $this->parseLogLine($line, $isErrorFile);
            $parsed['line_number'] = $lineNumber;
            $logs[] = $parsed;
        }

        // Reverse to show newest first
        $logs = array_reverse($logs);

        return [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_lines' => $totalLines,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
                'has_previous' => $page > 1,
            ],
            'file_size' => $fileSize,
            'file_size_formatted' => $this->formatFileSize($fileSize),
        ];
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
                'logs' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $limit,
                    'total_entries' => 0,
                    'total_pages' => 0,
                ],
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
        $totalPages = max(1, ceil($totalEntries / $limit));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        // Slice for current page
        $pagedEntries = array_slice($entries, $offset, $limit);

        return [
            'logs' => $pagedEntries,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_entries' => $totalEntries,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
                'has_previous' => $page > 1,
            ],
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
