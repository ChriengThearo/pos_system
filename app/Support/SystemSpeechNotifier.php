<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class SystemSpeechNotifier
{
    public static function speakPayment(float $amount, string $currencyCode, float $debt = 0): bool
    {
        if (! filter_var((string) env('SYSTEM_SPEECH_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $script = base_path('python/system_speech/khmer_tts.py');
        if (! is_file($script)) {
            Log::warning('System payment speech script not found.', ['script' => $script]);

            return false;
        }

        $pythonBin = PythonExecutable::resolve((string) env('SYSTEM_SPEECH_PYTHON_BIN', 'python'));
        if ($pythonBin === null) {
            Log::warning('Python runtime is not available for system payment speech.');

            return false;
        }

        $command = [
            $pythonBin,
            $script,
            self::formatAmount($amount),
            self::normalizeCurrency($currencyCode),
            self::formatAmount($debt),
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $parts = array_map(static fn (string $part): string => escapeshellarg($part), $command);
            $process = @popen('start /B "" '.implode(' ', $parts), 'r');
            if (! is_resource($process)) {
                Log::warning('Failed to start system payment speech process.', [
                    'python' => $pythonBin,
                    'script' => $script,
                ]);

                return false;
            }

            pclose($process);

            return true;
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', '/dev/null', 'a'],
            2 => ['file', '/dev/null', 'a'],
        ];
        $pipes = [];
        $process = @proc_open($command, $descriptorSpec, $pipes, base_path(), null, ['bypass_shell' => true]);
        if (! is_resource($process)) {
            Log::warning('Failed to start system payment speech process.', [
                'python' => $pythonBin,
                'script' => $script,
            ]);

            return false;
        }

        fclose($pipes[0]);

        return true;
    }

    private static function normalizeCurrency(string $currencyCode): string
    {
        $currencyCode = mb_strtoupper(trim($currencyCode));

        return $currencyCode === 'KHR' ? 'KHR' : 'USD';
    }

    private static function formatAmount(float $amount): string
    {
        return number_format(max(0, $amount), 2, '.', '');
    }
}
