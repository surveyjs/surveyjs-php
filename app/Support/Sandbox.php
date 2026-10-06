<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Demo only: a private copy of the seeded SQLite database and an uploads folder per visitor.
 * Used by the DemoSandbox middleware, the relays and demo:prune. Step code never sees it.
 */
final class Sandbox
{
    /** 128 random bits as 32 lowercase hex characters; nothing else ever reaches a file path. */
    public static function isValidId(mixed $id): bool
    {
        return is_string($id) && preg_match('/^[0-9a-f]{32}$/', $id) === 1;
    }

    public static function newId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function exists(string $id): bool
    {
        return self::isValidId($id) && is_file(self::databasePath($id));
    }

    public static function databasePath(string $id): string
    {
        return config('surveyjs.demo.sandbox_path').DIRECTORY_SEPARATOR.$id.'.sqlite';
    }

    public static function uploadsPath(string $id): string
    {
        return config('surveyjs.demo.sandbox_path').DIRECTORY_SEPARATOR.$id;
    }

    /**
     * Point the "sqlite" connection and the "uploads" disk at the visitor's copy,
     * creating it from the seeded database on first use. The middleware and the relays both call this.
     */
    public static function activate(string $id): void
    {
        if (! self::isValidId($id)) {
            throw new \InvalidArgumentException('Invalid sandbox id');
        }

        $database = self::databasePath($id);
        if (! is_file($database)) {
            File::ensureDirectoryExists(dirname($database));
            copy(config('surveyjs.demo.seed_database'), $database);
        }
        touch($database);   // last used: demo:prune removes sandboxes unused for 24 hours

        if (config('database.connections.sqlite.database') !== $database) {
            config(['database.connections.sqlite.database' => $database]);
            DB::purge('sqlite');
        }

        config(['filesystems.disks.uploads.root' => self::uploadsPath($id)]);
        Storage::forgetDisk('uploads');
    }

    /** Remove sandboxes (and relay snapshots) unused for the configured number of hours. */
    public static function prune(): int
    {
        $cutoff = time() - config('surveyjs.demo.sandbox_ttl_hours') * 3600;
        $removed = 0;

        foreach (File::glob(config('surveyjs.demo.sandbox_path').'/*.sqlite') as $database) {
            $id = basename($database, '.sqlite');
            if (self::isValidId($id) && filemtime($database) < $cutoff) {
                File::delete($database);
                File::deleteDirectory(self::uploadsPath($id));
                $removed++;
            }
        }

        foreach (File::glob(storage_path('app/relay').'/*.json') as $snapshot) {
            if (filemtime($snapshot) < $cutoff) {
                File::delete($snapshot);
            }
        }

        return $removed;
    }
}
