<?php

/**
 * Global class aliases that Laravel registers via config/app.php 'aliases'.
 * Intelephense does not process config/app.php alias registration, so these
 * stubs declare each alias in the global namespace for IDE type resolution.
 *
 * NOT loaded at runtime — static analysis only.
 */

// ── Eloquent base alias ───────────────────────────────────────────────────────

/**
 * Global alias for \Illuminate\Database\Eloquent\Model.
 * Used by _ide_helper_models.php: "class Foo extends \Eloquent"
 *
 * @see \Illuminate\Database\Eloquent\Model
 */
class Eloquent extends \Illuminate\Database\Eloquent\Model {}

// ── Routing ──────────────────────────────────────────────────────────────────

/** @see \Illuminate\Support\Facades\Route */
class Route extends \Illuminate\Support\Facades\Route {}

// ── Auth / Session ────────────────────────────────────────────────────────────

/** @see \Illuminate\Support\Facades\Auth */
class Auth extends \Illuminate\Support\Facades\Facade
{
    /** @return \App\Models\User|null */
    public static function user(): ?\App\Models\User { return null; }
    public static function id(): ?string { return null; }
    public static function check(): bool { return false; }
    public static function guest(): bool { return true; }
    public static function guard(?string $name = null): \Illuminate\Contracts\Auth\Guard { return app('auth')->guard($name); }
}

// ── Database ──────────────────────────────────────────────────────────────────

/** @see \Illuminate\Support\Facades\DB */
class DB extends \Illuminate\Support\Facades\Facade
{
    public static function table(string $table, ?string $as = null): \Illuminate\Database\Query\Builder { return app('db')->table($table); }
    public static function select(string $query, array $bindings = []): array { return []; }
    public static function statement(string $query, array $bindings = []): bool { return true; }
    /** @param callable $callback @return mixed */
    public static function transaction(callable $callback, int $attempts = 1): mixed { return $callback(); }
    public static function beginTransaction(): void {}
    public static function commit(): void {}
    public static function rollBack(): void {}
}

// ── Hashing ───────────────────────────────────────────────────────────────────

/** @see \Illuminate\Support\Facades\Hash */
class Hash extends \Illuminate\Support\Facades\Facade
{
    public static function make(string $value, array $options = []): string { return ''; }
    public static function check(string $value, string $hashedValue, array $options = []): bool { return false; }
    public static function needsRehash(string $hashedValue, array $options = []): bool { return false; }
}

// ── Logging ───────────────────────────────────────────────────────────────────

/** @see \Illuminate\Support\Facades\Log */
class Log extends \Illuminate\Support\Facades\Facade
{
    public static function info(string $message, array $context = []): void {}
    public static function error(string $message, array $context = []): void {}
    public static function warning(string $message, array $context = []): void {}
    public static function debug(string $message, array $context = []): void {}
    public static function critical(string $message, array $context = []): void {}
}

// ── Cache ─────────────────────────────────────────────────────────────────────

/** @see \Illuminate\Support\Facades\Cache */
class Cache extends \Illuminate\Support\Facades\Facade
{
    public static function get(string $key, mixed $default = null): mixed { return $default; }
    public static function put(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null): bool { return true; }
    public static function forget(string $key): bool { return true; }
    public static function has(string $key): bool { return false; }
}

// ── Config ────────────────────────────────────────────────────────────────────

/** @see \Illuminate\Support\Facades\Config */
class Config extends \Illuminate\Support\Facades\Facade
{
    public static function get(string $key, mixed $default = null): mixed { return $default; }
    public static function set(array|string $key, mixed $value = null): void {}
    public static function has(string $key): bool { return false; }
}
