<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StockAlertNotifier
{
    private const LAST_COUNT_CACHE_KEY = 'telegram_understock_alert_last_count';
    private const LOCK_CACHE_KEY = 'telegram_understock_alert_lock';

    /**
     * Sync popup context and trigger Telegram once globally per understock-count increase.
     */
    public static function notifyFromPopupContext(int $underStockCount, bool $showUnderStockAlert, bool $dryRun = false): int
    {
        if (! filter_var((string) env('TELEGRAM_STOCK_ALERT_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return 0;
        }

        $runSource = strtolower(trim((string) env('TELEGRAM_STOCK_ALERT_RUN_SOURCE', 'popup')));
        if ($runSource !== 'popup') {
            return 0;
        }

        $underStockCount = max(0, $underStockCount);

        try {
            return (int) Cache::lock(self::LOCK_CACHE_KEY, 5)->block(2, function () use ($underStockCount, $showUnderStockAlert, $dryRun): int {
                if ($underStockCount === 0) {
                    Cache::forget(self::LAST_COUNT_CACHE_KEY);

                    return 0;
                }

                $lastCount = (int) Cache::get(self::LAST_COUNT_CACHE_KEY, 0);

                if ($underStockCount < $lastCount) {
                    // Track downward movement so future increases can trigger again.
                    Cache::forever(self::LAST_COUNT_CACHE_KEY, $underStockCount);

                    return 0;
                }

                if (! $showUnderStockAlert) {
                    if ($lastCount === 0) {
                        // Initialize baseline when first seen without popup.
                        Cache::forever(self::LAST_COUNT_CACHE_KEY, $underStockCount);
                    }

                    return 0;
                }

                if ($underStockCount <= $lastCount) {
                    return 0;
                }

                $exitCode = self::runMonitorOnce($dryRun);
                if ($exitCode === 0) {
                    Cache::forever(self::LAST_COUNT_CACHE_KEY, $underStockCount);
                }

                return $exitCode;
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to process popup-driven Telegram stock alert.', [
                'under_stock_count' => $underStockCount,
                'show_under_stock_alert' => $showUnderStockAlert,
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    /**
     * Run the Telegram stock alert monitor once.
     *
     * Returns process exit code (0 on success).
     */
    public static function runMonitorOnce(bool $dryRun = false): int
    {
        if (! filter_var((string) env('TELEGRAM_STOCK_ALERT_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return 0;
        }

        $script = base_path('python/telegram/stock_alert_monitor.py');
        if (! is_file($script)) {
            Log::warning('Telegram stock alert monitor script not found.', ['script' => $script]);

            return 1;
        }

        $pythonBin = trim((string) env('TELEGRAM_PYTHON_BIN', 'python'));
        if ($pythonBin === '') {
            $pythonBin = 'python';
        }

        $command = [$pythonBin, $script, '--once'];
        if ($dryRun) {
            $command[] = '--dry-run';
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes = [];
        $process = @proc_open($command, $descriptorSpec, $pipes, base_path(), null, ['bypass_shell' => true]);

        if (! is_resource($process)) {
            Log::warning('Failed to start Telegram stock alert monitor process.', [
                'python' => $pythonBin,
                'script' => $script,
            ]);

            return 1;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            Log::warning('Telegram stock alert monitor failed.', [
                'exit_code' => $exitCode,
                'stdout' => trim($stdout),
                'stderr' => trim($stderr),
            ]);
        }

        return (int) $exitCode;
    }
}
