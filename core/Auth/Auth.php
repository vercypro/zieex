<?php
declare(strict_types=1);

namespace Zieex\Auth;

use Zieex\Database\DB;

class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = DB::table('users')->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        $_SESSION['auth_user_id'] = $user['id'];
        self::$user = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user_id']);
        self::$user = null;
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        if (!isset($_SESSION['auth_user_id'])) {
            return null;
        }

        $user = DB::table('users')->find($_SESSION['auth_user_id']);
        self::$user = $user ?: null;
        return self::$user;
    }

    public static function id(): int|string|null
    {
        return self::user()['id'] ?? null;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateToken(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function loginById(int|string $id): bool
    {
        $user = DB::table('users')->find($id);
        if (!$user) return false;
        self::login($user);
        return true;
    }
}
