<?php

namespace App\Support;

class PythonExecutable
{
    /**
     * @var array<string, string|null>
     */
    private static array $resolved = [];

    public static function resolve(?string $configured = null): ?string
    {
        $configured = trim((string) $configured);
        $cacheKey = $configured;

        if (array_key_exists($cacheKey, self::$resolved)) {
            return self::$resolved[$cacheKey];
        }

        $candidates = [];
        if ($configured !== '') {
            $candidates[] = $configured;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates[] = 'python';
            $candidates[] = 'py';
        } else {
            $candidates[] = 'python3';
            $candidates[] = 'python';
        }

        $candidates = array_values(array_unique($candidates));
        foreach ($candidates as $candidate) {
            if (self::canExecute($candidate)) {
                self::$resolved[$cacheKey] = $candidate;

                return $candidate;
            }
        }

        self::$resolved[$cacheKey] = null;

        return null;
    }

    private static function canExecute(string $binary): bool
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes = [];
        $process = @proc_open([$binary, '--version'], $descriptorSpec, $pipes, base_path(), null, ['bypass_shell' => true]);
        if (! is_resource($process)) {
            return false;
        }

        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return $exitCode === 0;
    }
}
