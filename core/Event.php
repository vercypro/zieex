<?php
declare(strict_types=1);

namespace Zieex;

class Event
{
    private static array $listeners = [];

    public static function on(string $event, callable $listener): void
    {
        self::$listeners[$event][] = $listener;
    }

    public static function emit(string $event, mixed ...$args): void
    {
        foreach (self::$listeners[$event] ?? [] as $listener) {
            $listener(...$args);
        }
    }

    public static function once(string $event, callable $listener): void
    {
        $wrapper = null;
        $wrapper = function () use ($event, $listener, &$wrapper) {
            $listener(...func_get_args());
            self::off($event, $wrapper);
        };
        self::on($event, $wrapper);
    }

    public static function off(string $event, ?callable $listener = null): void
    {
        if ($listener === null) {
            unset(self::$listeners[$event]);
            return;
        }

        self::$listeners[$event] = array_filter(
            self::$listeners[$event] ?? [],
            fn($l) => $l !== $listener
        );
    }

    public static function listeners(string $event): array
    {
        return self::$listeners[$event] ?? [];
    }
}
