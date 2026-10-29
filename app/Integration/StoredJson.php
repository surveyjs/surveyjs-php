<?php

declare(strict_types=1);

namespace App\Integration;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Stores SurveyJS JSON (responses, definitions, saved progress, variable presets) exactly as it
 * arrived, and returns it the same way. Used by 1.1, 1.3, 1.7, 3.1, 3.4, 4.1 and 4.2 to store,
 * and by 1.2 to answer. A response or a definition is one JSON document: store it as text, never as
 * columns of a PHP array. When you copy one of those steps, copy this file too.
 */
final class StoredJson
{
    /**
     * - JSON_UNESCAPED_UNICODE: "naïve — 東京" is stored as it is, not as \u escapes
     * - JSON_UNESCAPED_SLASHES: "/api/files/1" is stored as it is, not as "\/api\/files\/1"
     * - JSON_PRESERVE_ZERO_FRACTION: 1.0 stays 1.0 (PHP would write 1)
     * - JSON_THROW_ON_ERROR: a value that can't be encoded throws instead of storing false
     */
    public const FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    public static function encode(mixed $value): string
    {
        return json_encode($value, self::FLAGS);
    }

    /** A JSON answer with the same flags. JsonResponse reports encoding errors itself, so it gets them without JSON_THROW_ON_ERROR. */
    public static function response(mixed $value, int $status = 200): JsonResponse
    {
        return response()->json($value, $status, options: self::FLAGS & ~JSON_THROW_ON_ERROR);
    }

    /**
     * Insert one response: the answers as one JSON document, plus any extra columns (1.7 adds
     * definition_version). $formId and $data are taken as sent: each route does its own validation.
     */
    public static function insertResponse(mixed $formId, mixed $data, array $columns = []): int
    {
        return DB::table('responses')->insertGetId([
            'form_id' => $formId,
            ...$columns,
            'data' => self::encode($data),
            'created_at' => now('UTC')->toIso8601ZuluString(),   // ISO 8601 UTC strings sort and compare as dates (2 filters on them)
        ]);
    }

    /** Insert or replace the JSON document stored under a key (forms, progress, variable_presets). */
    public static function put(string $table, string $key, mixed $document): void
    {
        DB::table($table)->updateOrInsert(['key' => $key], ['json' => self::encode($document)]);
    }
}
