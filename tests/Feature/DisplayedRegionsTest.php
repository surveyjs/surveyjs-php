<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Demo;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The server regions call App\Integration for the plumbing, but they keep the comments about the
 * pitfalls that corrupt SurveyJS data, because the docs and the step pages show only the regions.
 */
class DisplayedRegionsTest extends TestCase
{
    private const RAW_BODY = 'The raw body, not $request->input(): PHP arrays would turn {} into [] in the stored JSON';

    private const DECODE = 'json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR)';

    private const AS_IT_ARRIVED = 'stored as it arrived: 1.0 stays 1.0';

    /** region => [file, the texts it must contain] */
    public static function pitfalls(): array
    {
        return [
            'I.1.server' => ['routes/examples/save-response.php', [self::RAW_BODY, self::DECODE, self::AS_IT_ARRIVED, 'TrimStrings', 'ConvertEmptyStringsToNull', 'bootstrap/app.php']],
            'I.2.server' => ['routes/examples/load-definition-variables-data.php', ['previous answers, or {}']],
            'I.3.server' => ['routes/examples/resume-progress.php', [self::RAW_BODY, self::DECODE, self::AS_IT_ARRIVED]],
            'I.4.server' => ['routes/examples/store-files.php', ['field "files[]": PHP keeps only the last of several "files"', '404, not 403', 'nosniff', 'sandbox']],
            'I.7.server' => ['routes/examples/relational-storage.php', [self::RAW_BODY, self::DECODE, self::AS_IT_ARRIVED]],
            'II.server' => ['routes/examples/dashboard.php', ['documents as stored, not re-encoded']],
            'III.1.server' => ['routes/examples/creator-load-save.php', [self::RAW_BODY, self::DECODE, self::AS_IT_ARRIVED]],
            'III.4.server' => ['routes/examples/variable-presets.php', [self::RAW_BODY, self::DECODE, self::AS_IT_ARRIVED]],
            'IV.1.server' => ['routes/examples/validate-response.php', ['the raw body keeps {} as {} (see I.1)', self::DECODE, self::AS_IT_ARRIVED]],
            'IV.2.server' => ['routes/examples/lint-definition.php', ['the raw body keeps {} as {} (see III.1)', self::DECODE, self::AS_IT_ARRIVED]],
        ];
    }

    #[DataProvider('pitfalls')]
    public function test_the_region_keeps_its_pitfall_comments(string $file, array $texts): void
    {
        $id = $this->dataName();
        $region = Demo::regionsIn($file)[$id] ?? null;

        $this->assertNotNull($region, "sjs:{$id} is missing from {$file}");
        foreach ($texts as $text) {
            $this->assertStringContainsString($text, $region['code'], "sjs:{$id} lost: {$text}");
        }
    }
}
