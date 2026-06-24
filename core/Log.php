<?php
declare(strict_types=1);

namespace Zieex;

class Log
{
    private static string $logPath = '';

    private static function path(): string
    {
        if (!self::$logPath) {
            self::$logPath = BASE_PATH . '/storage/logs';
            if (!is_dir(self::$logPath)) {
                mkdir(self::$logPath, 0755, true);
            }
        }
        return self::$logPath;
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (env('APP_ENV', 'local') === 'production') return;
        self::write('DEBUG', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write('CRITICAL', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $date    = date('Y-m-d');
        $time    = date('Y-m-d H:i:s');
        $file    = self::path() . "/{$date}.log";
        $ctx     = !empty($context) ? ' ' . json_encode($context) : '';
        $line    = "[{$time}] [{$level}] {$message}{$ctx}" . PHP_EOL;

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
