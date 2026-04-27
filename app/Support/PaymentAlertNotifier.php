<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class PaymentAlertNotifier
{
    /**
     * Run the Telegram payment alert sender once.
     *
     * Returns process exit code (0 on success).
     *
     * @param  array{
     *   customer_name?: string,
     *   paid_by?: string,
     *   total?: float|int|string,
     *   paid?: float|int|string,
     *   debt?: float|int|string,
     *   currency_code?: string
     * }  $payload
     */
    public static function notifyPayment(array $payload, bool $dryRun = false): int
    {
        if (! filter_var((string) env('TELEGRAM_PAYMENT_ALERT_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return 0;
        }

        $script = base_path('python/telegram/payment_alert_sender.py');
        if (! is_file($script)) {
            Log::warning('Telegram payment alert sender script not found.', ['script' => $script]);

            return 1;
        }

        $customerName = trim((string) ($payload['customer_name'] ?? 'Walk-in Customer'));
        if ($customerName === '') {
            $customerName = 'Walk-in Customer';
        }

        $paidBy = strtolower(trim((string) ($payload['paid_by'] ?? 'cash')));
        if (! in_array($paidBy, ['cash', 'qr'], true)) {
            $paidBy = 'cash';
        }

        $total = self::normalizeAmount($payload['total'] ?? 0);
        $paid = self::normalizeAmount($payload['paid'] ?? 0);
        $debt = self::normalizeAmount($payload['debt'] ?? 0);
        $currencyCode = strtoupper(trim((string) ($payload['currency_code'] ?? 'USD')));
        if ($currencyCode === '') {
            $currencyCode = 'USD';
        }

        $pythonBin = trim((string) env('TELEGRAM_PYTHON_BIN', 'python'));
        if ($pythonBin === '') {
            $pythonBin = 'python';
        }

        $command = [
            $pythonBin,
            $script,
            '--once',
            '--customer-name',
            $customerName,
            '--paid-by',
            $paidBy,
            '--total',
            self::formatArgAmount($total),
            '--paid',
            self::formatArgAmount($paid),
            '--debt',
            self::formatArgAmount($debt),
            '--currency',
            $currencyCode,
        ];

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
            Log::warning('Failed to start Telegram payment alert sender process.', [
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
            Log::warning('Telegram payment alert sender failed.', [
                'exit_code' => $exitCode,
                'stdout' => trim($stdout),
                'stderr' => trim($stderr),
                'customer_name' => $customerName,
                'paid_by' => $paidBy,
                'currency_code' => $currencyCode,
            ]);
        }

        return (int) $exitCode;
    }

    private static function normalizeAmount(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return round(max(0, (float) $value), 2);
    }

    private static function formatArgAmount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
