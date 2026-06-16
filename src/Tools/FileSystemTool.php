<?php

namespace Nathan\LaravelMcp\Tools;

use Nathan\LaravelMcp\Security\SecurityManager;

class FileSystemTool
{
    public function __construct(
        private readonly SecurityManager $security,
    ) {}

    public function read(string $path): string
    {
        try {
            $fullPath = $this->resolve($path);
            $error = $this->security->validateFilePath($fullPath);
            if ($error) {
                $this->security->log('file_read', ['path' => $path], 'denied', $error);
                return "ERROR: {$error}";
            }

            if (!file_exists($fullPath)) {
                return "ERROR: File not found: {$path}";
            }

            if (!is_readable($fullPath)) {
                return "ERROR: File not readable: {$path}";
            }

            if (is_dir($fullPath)) {
                return "ERROR: Path is a directory, not a file: {$path}";
            }

            $content = file_get_contents($fullPath);
            if ($content === false) {
                return "ERROR: Failed to read file: {$path}";
            }

            $this->security->log('file_read', ['path' => $path, 'size' => strlen($content)]);

            return $content;
        } catch (\Throwable $e) {
            $this->security->log('file_read', ['path' => $path], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }

    public function write(string $path, string $content): string
    {
        try {
            $error = $this->security->checkFilesystemReadonly();
            if ($error) {
                $this->security->log('file_write', ['path' => $path, 'size' => strlen($content)], 'denied', $error);
                return "ERROR: {$error}";
            }

            $fullPath = $this->resolve($path);
            $error = $this->security->validateFilePath($fullPath);
            if ($error) {
                $this->security->log('file_write', ['path' => $path, 'size' => strlen($content)], 'denied', $error);
                return "ERROR: {$error}";
            }

            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $bytes = file_put_contents($fullPath, $content);
            if ($bytes === false) {
                return "ERROR: Failed to write file: {$path}";
            }

            $this->security->log('file_write', ['path' => $path, 'size' => $bytes]);

            return "Written {$bytes} bytes to {$path}";
        } catch (\Throwable $e) {
            $this->security->log('file_write', ['path' => $path, 'size' => strlen($content) ?? 0], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }

    public function list(string $path = ''): string
    {
        try {
            $fullPath = $this->resolve($path);
            $error = $this->security->validateFilePath($fullPath);
            if ($error) {
                $this->security->log('file_list', ['path' => $path], 'denied', $error);
                return "ERROR: {$error}";
            }

            if (!is_dir($fullPath)) {
                return "ERROR: Directory not found: {$path}";
            }

            if (!is_readable($fullPath)) {
                return "ERROR: Directory not readable: {$path}";
            }

            $items = scandir($fullPath);
            if ($items === false) {
                return "ERROR: Failed to read directory: {$path}";
            }

            $result = [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $itemPath = $fullPath . '/' . $item;
                $type = is_dir($itemPath) ? 'DIR' : 'FILE';
                $size = is_file($itemPath) ? $this->formatSize(filesize($itemPath)) : '';
                $result[] = "{$type}\t{$item}" . ($size ? " ({$size})" : '');
            }

            $this->security->log('file_list', ['path' => $path, 'items' => count($result)]);

            return implode("\n", $result) ?: '(empty directory)';
        } catch (\Throwable $e) {
            $this->security->log('file_list', ['path' => $path], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }

    public function search(string $pattern, string $path = ''): string
    {
        try {
            $fullPath = $this->resolve($path);
            $error = $this->security->validateFilePath($fullPath);
            if ($error) {
                $this->security->log('file_search', ['pattern' => $pattern, 'path' => $path], 'denied', $error);
                return "ERROR: {$error}";
            }

            if (!is_dir($fullPath)) {
                return "ERROR: Directory not found: {$path}";
            }

            $output = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            $count = 0;
            $limit = 100;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relPath = str_replace(base_path() . '/', '', $file->getPathname());
                    if (fnmatch($pattern, $file->getFilename()) || fnmatch($pattern, $relPath)) {
                        $output[] = $relPath;
                        $count++;
                        if ($count >= $limit) {
                            $output[] = "\n... (limited to {$limit} results)";
                            break;
                        }
                    }
                }
            }

            $this->security->log('file_search', ['pattern' => $pattern, 'path' => $path, 'count' => $count]);

            if (empty($output)) {
                return "No files matching '{$pattern}'.";
            }

            return implode("\n", $output);
        } catch (\Throwable $e) {
            $this->security->log('file_search', ['pattern' => $pattern, 'path' => $path], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }

    private function resolve(string $path): string
    {
        if ($path === '' || $path === null) {
            return base_path();
        }
        if (str_starts_with($path, '/')) {
            return $path;
        }
        return base_path($path);
    }

    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
