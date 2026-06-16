<?php

namespace Nathan\LaravelMcp\Tools;

use Nathan\LaravelMcp\Security\SecurityManager;

class LogViewerTool
{
    public function __construct(
        private readonly SecurityManager $security,
    ) {}

    public function logs(?int $lines = 50, ?string $level = null): string
    {
        try {
            $logPath = storage_path('logs/laravel.log');

            if (!file_exists($logPath)) {
                $logPath = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
            }

            if (!file_exists($logPath)) {
                $files = glob(storage_path('logs/*.log'));
                if (empty($files)) {
                    return "No log files found in " . storage_path('logs');
                }
                $logPath = end($files);
            }

            $lines = min(max((int) $lines, 1), 5000);

            $content = file_get_contents($logPath);
            if ($content === false) {
                return "ERROR: Failed to read log file.";
            }

            if ($level) {
                $pattern = '/\[' . preg_quote(strtoupper($level), '/') . '\]/';
                $matched = [];
                foreach (explode("\n", $content) as $line) {
                    if (preg_match($pattern, $line)) {
                        $matched[] = $line;
                    }
                }
                $content = implode("\n", $matched);
            }

            $allLines = explode("\n", $content);
            $recent = array_slice($allLines, -$lines);

            $this->security->log('logs', ['lines' => $lines, 'level' => $level ?? 'all']);

            return implode("\n", $recent) ?: '(empty log)';
        } catch (\Throwable $e) {
            $this->security->log('logs', ['lines' => $lines, 'level' => $level ?? 'all'], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }
}
