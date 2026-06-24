<?php
declare(strict_types=1);

namespace Zieex\Auth;

class JWT
{
    private static string $algo = 'sha256';

    public static function encode(array $payload, ?string $secret = null, int $ttl = 3600): string
    {
        $secret ??= env('JWT_SECRET', 'changeme');

        $header  = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttl;
        $payload  = self::base64UrlEncode(json_encode($payload));
        $sig      = self::base64UrlEncode(hash_hmac(self::$algo, "{$header}.{$payload}", $secret, true));

        return "{$header}.{$payload}.{$sig}";
    }

    public static function decode(string $token, ?string $secret = null): ?array
    {
        $secret ??= env('JWT_SECRET', 'changeme');
        $parts = explode('.', $token);

        if (count($parts) !== 3) return null;

        [$header, $payload, $sig] = $parts;

        $expectedSig = self::base64UrlEncode(
            hash_hmac(self::$algo, "{$header}.{$payload}", $secret, true)
        );

        if (!hash_equals($expectedSig, $sig)) return null;

        $data = json_decode(self::base64UrlDecode($payload), true);

        if (!$data || (isset($data['exp']) && $data['exp'] < time())) {
            return null;
        }

        return $data;
    }

    public static function validate(string $token, ?string $secret = null): bool
    {
        return self::decode($token, $secret) !== null;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
