<?php

namespace Nathan\LaravelMcp\Tools;

use Nathan\LaravelMcp\Security\SecurityManager;

class ConfigTool
{
    public function __construct(
        private readonly SecurityManager $security,
    ) {}

    public function get(string $key): string
    {
        try {
            $error = $this->security->validateConfigKey($key);
            if ($error) {
                $this->security->log('config_get', ['key' => $key], 'denied', $error);
                return "ERROR: {$error}";
            }

            $value = config($key);

            if ($value === null) {
                return "Config key '{$key}' not found.";
            }

            $result = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                is_array($value) => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                default => (string) $value,
            };

            $this->security->log('config_get', ['key' => $key]);

            return $result;
        } catch (\Throwable $e) {
            $this->security->log('config_get', ['key' => $key], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }
}
