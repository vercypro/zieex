<?php
declare(strict_types=1);

namespace Zieex\Auth;

class CSRF
{
    private static string $key = '_csrf_token';

    public static function generate(): string
    {
        if (empty($_SESSION[self::$key])) {
            $_SESSION[self::$key] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$key];
    }

    public static function token(): string
    {
        return self::generate();
    }

    public static function verify(?string $token = null): bool
    {
        $token  ??= $_POST[self::$key] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $stored   = $_SESSION[self::$key] ?? null;

        if (!$token || !$stored) return false;

        return hash_equals($stored, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="' . self::$key . '" value="' . self::token() . '">';
    }

    public static function meta(): string
    {
        return '<meta name="csrf-token" content="' . self::token() . '">';
    }

    public static function rotate(): void
    {
        $_SESSION[self::$key] = bin2hex(random_bytes(32));
    }
}
