<?php

/**
 * IDE stubs for spatie/laravel-permission and PHP 8.4 PDO classes.
 * This file exists solely for static analysis / IDE type resolution.
 * It is NOT loaded at runtime.
 */

// ─── Spatie Permission ────────────────────────────────────────────────────────

namespace Spatie\Permission\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public string $name;
    public string $guard_name;

    public function __construct(array $attributes = []) { parent::__construct($attributes); }

    /** @param array<string,mixed> $attributes @return static */
    public static function create(array $attributes = []) {}

    /** @param array<string,mixed> $attributes @param array<string,mixed> $values @return static */
    public static function firstOrCreate(array $attributes = [], array $values = []) {}

    /** @param array<string,mixed> $attributes @return static */
    public static function findByName(string $name, ?string $guardName = null) {}
}

class Permission extends Model
{
    public string $name;
    public string $guard_name;

    public function __construct(array $attributes = []) { parent::__construct($attributes); }

    /** @param array<string,mixed> $attributes @return static */
    public static function create(array $attributes = []) {}

    /** @param array<string,mixed> $attributes @param array<string,mixed> $values @return static */
    public static function firstOrCreate(array $attributes = [], array $values = []) {}

    /** @param array<string,mixed> $attributes @return static */
    public static function findByName(string $name, ?string $guardName = null) {}
}

namespace Spatie\Permission;

class PermissionRegistrar
{
    public function forgetCachedPermissions(): void {}
}

// ─── PHP 8.4 PDO subclasses ───────────────────────────────────────────────────

namespace Pdo;

/**
 * PHP 8.4+ MySQL PDO driver class.
 * @see https://www.php.net/manual/en/class.pdo.mysql.php
 */
class Mysql extends \PDO
{
    public const ATTR_USE_BUFFERED_QUERY     = 1000;
    public const ATTR_LOCAL_INFILE           = 1001;
    public const ATTR_INIT_COMMAND           = 1002;
    public const ATTR_READ_DEFAULT_FILE      = 1003;
    public const ATTR_READ_DEFAULT_GROUP     = 1004;
    public const ATTR_MAX_BUFFER_SIZE        = 1005;
    public const ATTR_DIRECT_QUERY           = 1006;
    public const ATTR_FOUND_ROWS             = 1007;
    public const ATTR_IGNORE_SPACE           = 1008;
    public const ATTR_SSL_KEY                = 1009;
    public const ATTR_SSL_CERT               = 1010;
    public const ATTR_SSL_CA                 = 1011;
    public const ATTR_SSL_CAPATH             = 1012;
    public const ATTR_SSL_CIPHER             = 1013;
    public const ATTR_SSL_VERIFY_SERVER_CERT = 1014;
    public const ATTR_MULTI_STATEMENTS       = 1015;
    public const ATTR_SERVER_PUBLIC_KEY      = 1016;
}

/**
 * PHP 8.4+ SQLite PDO driver class.
 */
class Sqlite extends \PDO {}

/**
 * PHP 8.4+ PostgreSQL PDO driver class.
 */
class Pgsql extends \PDO {}
