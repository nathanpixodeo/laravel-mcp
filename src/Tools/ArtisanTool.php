<?php

namespace Nathan\LaravelMcp\Tools;

use Illuminate\Support\Facades\Artisan;
use Nathan\LaravelMcp\Security\SecurityManager;

class ArtisanTool
{
    public function __construct(
        private readonly SecurityManager $security,
    ) {}

    public function run(string $command, array $parameters = []): string
    {
        try {
            $error = $this->security->checkArtisanCommand($command);
            if ($error) {
                $this->security->log('artisan', ['command' => $command, 'parameters' => $parameters], 'denied', $error);
                return "ERROR: {$error}";
            }

            $exitCode = Artisan::call($command, $parameters);
            $output = Artisan::output();

            $this->security->log('artisan', ['command' => $command, 'parameters' => $parameters, 'exit_code' => $exitCode]);

            return sprintf("Exit code: %d\n\n%s", $exitCode, $output ?: '(no output)');
        } catch (\Throwable $e) {
            $this->security->log('artisan', ['command' => $command, 'parameters' => $parameters], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }
}
