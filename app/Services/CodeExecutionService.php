<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class CodeExecutionService
{
    /**
     * Execute student code inside an isolated Docker container.
     *
     * Supported languages:
     * - Python
     * - JavaScript
     * - C
     */
    public function execute(
        string $code,
        string $language
    ): array {
        $language = strtolower(trim($language));

        if (blank($code)) {
            throw new RuntimeException('No code was provided.');
        }

        if (! in_array($language, [
            'python',
            'javascript',
            'c',
        ], true)) {
            throw new RuntimeException(
                'Unsupported programming language.'
            );
        }

        $tempDirectory = storage_path(
            'app/code-execution/' . Str::uuid()
        );

        if (! is_dir($tempDirectory)) {
            mkdir(
                $tempDirectory,
                0755,
                true
            );
        }

        try {
            $filename = $this->getFilename($language);

            $filePath = $tempDirectory . DIRECTORY_SEPARATOR . $filename;

            file_put_contents(
                $filePath,
                $code
            );

            return $this->runDockerContainer(
                $tempDirectory,
                $filename,
                $language
            );
        } finally {
            $this->deleteDirectory($tempDirectory);
        }
    }

    /**
     * Get the filename used inside the container.
     */
    private function getFilename(string $language): string
    {
        return match ($language) {
            'python' => 'main.py',
            'javascript' => 'main.js',
            'c' => 'main.c',
            default => 'main.txt',
        };
    }

    /**
     * Run the code inside Docker.
     */
    private function runDockerContainer(
        string $directory,
        string $filename,
        string $language
    ): array {
        $image = match ($language) {
            'python' => 'python:3.12-alpine',
            'javascript' => 'node:22-alpine',
            'c' => 'gcc:14',
            default => throw new RuntimeException(
                'Unsupported programming language.'
            ),
        };

        $command = match ($language) {
            'python' =>
                'python /workspace/main.py',

            'javascript' =>
                'node /workspace/main.js',

            'c' =>
                'sh -c "gcc /workspace/main.c -o /tmp/program && /tmp/program"',

            default =>
                throw new RuntimeException(
                    'Unsupported programming language.'
                ),
        };

        $dockerPath = str_replace(
            '\\',
            '/',
            $directory
        );

        /*
         * Docker is intentionally restricted:
         *
         * --rm
         *     Delete the container after execution.
         *
         * --network none
         *     Student code cannot access the internet.
         *
         * --memory 128m
         *     Limit memory usage.
         *
         * --cpus 0.5
         *     Limit CPU usage.
         *
         * --pids-limit 64
         *     Prevent excessive process creation.
         *
         * --read-only
         *     Container filesystem is read-only.
         *
         * --cap-drop ALL
         *     Remove Linux capabilities.
         *
         * --security-opt no-new-privileges
         *     Prevent privilege escalation.
         *
         * -v ...: /workspace:ro
         *     Student code is mounted read-only.
         */

        $dockerCommand = [
            'C:\\Users\\NIKKA D. BERDIN\\AppData\\Local\\Programs\\DockerDesktop\\resources\\bin\\docker.exe',
            'run',
            '--rm',
            '--network',
            'none',
            '--memory',
            '128m',
            '--cpus',
            '0.5',
            '--pids-limit',
            '64',
            '--read-only',
            '--cap-drop',
            'ALL',
            '--security-opt',
            'no-new-privileges',
            '-v',
            $dockerPath . ':/workspace:ro',
            $image,
            'sh',
            '-c',
            $command,
        ];

        $process = proc_open(
            $dockerCommand,
            [
                0 => [
                    'pipe',
                    'r',
                ],
                1 => [
                    'pipe',
                    'w',
                ],
                2 => [
                    'pipe',
                    'w',
                ],
            ],
            $pipes
        );

        if (! is_resource($process)) {
            throw new RuntimeException(
                'Unable to start Docker.'
            );
        }

        fclose($pipes[0]);

        $output = stream_get_contents(
            $pipes[1]
        );

        $error = stream_get_contents(
            $pipes[2]
        );

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close(
            $process
        );

        return [
            'status' => $exitCode === 0
                ? 'success'
                : 'error',

            'output' => trim($output),

            'error' => trim($error),

            'exit_code' => $exitCode,
        ];
    }

    /**
     * Delete a temporary directory recursively.
     */
    private function deleteDirectory(
        string $directory
    ): void {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        foreach ($items as $item) {
            if (
                $item === '.'
                || $item === '..'
            ) {
                continue;
            }

            $path =
                $directory
                . DIRECTORY_SEPARATOR
                . $item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}