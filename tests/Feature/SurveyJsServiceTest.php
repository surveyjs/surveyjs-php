<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every row of the SurveyJS service status mapping (the meta prompt's "Section IV"), faked with
 * Http::fake(). It fails closed: nothing is saved or passed on unless the answer is a recognized success.
 */
class SurveyJsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const SERVICE = 'http://surveyjs.test:3000';

    /** routes/examples.php picks the IV.1 and IV.2 route files at boot, so the switches are set before routes load. */
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->afterBootstrapping(LoadConfiguration::class, fn ($app) => $app['config']->set([
            'surveyjs.validate_responses' => true,
            'surveyjs.lint_definitions' => true,
            'surveyjs.service_url' => self::SERVICE,
        ]));
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('email', 'alice@example.com')->firstOrFail());
    }

    private const CLAIM = ['customer_email' => 'ann@example.com', 'amount' => 10, 'incident' => 'theft'];

    /** Rows shared by every step: anything unrecognized, a timeout and a refused connection. */
    public static function failures(): array
    {
        return [
            '200 { error } (a field is missing)' => [fn () => Http::response(['error' => 'schema is required'], 200), 502, 'SurveyJS service error'],
            '500 HTML page' => [fn () => Http::response('<html>Internal Server Error</html>', 500, ['Content-Type' => 'text/html']), 502, 'SurveyJS service error'],
            '200 malformed JSON' => [fn () => Http::response('{"errors": [', 200, ['Content-Type' => 'application/json']), 502, 'SurveyJS service error'],
            '400 INVALID_JSON' => [fn () => Http::response(['error' => 'INVALID_JSON'], 400), 502, 'SurveyJS service error'],
            '404 Express page' => [fn () => Http::response('Cannot POST /x', 404, ['Content-Type' => 'text/html']), 502, 'SurveyJS service error'],
            'timeout' => [fn () => throw new ConnectionException('cURL error 28: Operation timed out after 10001 milliseconds'), 504, 'SurveyJS service timed out'],
            'connection refused' => [fn () => throw new ConnectionException('cURL error 7: Failed to connect to surveyjs.test port 3000'), 503, 'SurveyJS service is not running at '.self::SERVICE],
        ];
    }

    // ---------------------------------------------------------------------------------------------
    // IV.1 POST /api/responses

    public function test_iv1_valid_answers_are_saved(): void
    {
        Http::fake([self::SERVICE.'/response' => Http::response('{}', 200, ['Content-Type' => 'application/json'])]);
        $before = DB::table('responses')->count();

        $this->postJson('/api/responses', ['formId' => 'claim', 'data' => self::CLAIM])->assertCreated()->assertJsonStructure(['id']);

        $this->assertSame($before + 1, DB::table('responses')->count());
        Http::assertSent(fn ($request) => json_decode($request->body(), true)['schema']['elements'][0]['name'] === 'q_email'
            && json_decode($request->body(), true)['response'] === self::CLAIM);
    }

    public function test_iv1_answers_that_dont_fit_are_refused_with_the_errors(): void
    {
        $errors = [['type' => 'invalidChoiceValue', 'path' => 'incident', 'value' => 'meteor']];
        Http::fake([self::SERVICE.'/response' => Http::response(['errors' => $errors], 422)]);

        $this->assertNothingSaved('responses', fn () => $this->postJson('/api/responses', ['formId' => 'claim', 'data' => self::CLAIM])
            ->assertStatus(400)->assertExactJson(['errors' => $errors]));
    }

    public function test_iv1_a_stored_definition_with_errors_is_a_server_error(): void
    {
        Http::fake([self::SERVICE.'/response' => Http::response(['errors' => [['ruleId' => 'reference/unknown']], 'warnings' => []], 422)]);

        $this->assertNothingSaved('responses', fn () => $this->postJson('/api/responses', ['formId' => 'claim', 'data' => self::CLAIM])
            ->assertStatus(502)->assertExactJson(['error' => 'The stored definition has errors']));
    }

    public function test_iv1_a_422_without_errors_is_not_recognized(): void
    {
        Http::fake([self::SERVICE.'/response' => Http::response(['message' => 'unprocessable'], 422)]);

        $this->assertNothingSaved('responses', fn () => $this->postJson('/api/responses', ['formId' => 'claim', 'data' => self::CLAIM])
            ->assertStatus(502)->assertExactJson(['error' => 'SurveyJS service error']));
    }

    #[DataProvider('failures')]
    public function test_iv1_fails_closed(\Closure $answer, int $status, string $error): void
    {
        Http::fake([self::SERVICE.'/*' => $answer]);

        $this->assertNothingSaved('responses', fn () => $this->postJson('/api/responses', ['formId' => 'claim', 'data' => self::CLAIM])
            ->assertStatus($status)->assertExactJson(['error' => $error]));
    }

    // ---------------------------------------------------------------------------------------------
    // IV.2 PUT /api/forms/{id}

    private const DEFINITION = ['elements' => [['type' => 'text', 'name' => 'q1']]];

    public function test_iv2_a_clean_definition_is_saved_with_204(): void
    {
        Http::fake([self::SERVICE.'/schema' => Http::response('{}', 200, ['Content-Type' => 'application/json'])]);

        $this->putJson('/api/forms/lint-test', self::DEFINITION)->assertNoContent();

        $this->assertSame(self::DEFINITION, json_decode(DB::table('forms')->where('key', 'lint-test')->value('json'), true));
    }

    public function test_iv2_warnings_only_are_saved_and_returned(): void
    {
        $warnings = [['ruleId' => 'property/unknown', 'message' => '"someproperty" is not a property of "q1"']];
        Http::fake([self::SERVICE.'/schema' => Http::response(['warnings' => $warnings], 200)]);

        $this->putJson('/api/forms/lint-test', self::DEFINITION)->assertOk()->assertExactJson(['warnings' => $warnings]);

        $this->assertTrue(DB::table('forms')->where('key', 'lint-test')->exists());
    }

    public function test_iv2_errors_are_refused_and_reported_as_they_are(): void
    {
        $answer = ['errors' => [['ruleId' => 'reference/unknown', 'message' => '"nosuch" is not found']], 'warnings' => []];
        Http::fake([self::SERVICE.'/schema' => Http::response($answer, 422)]);

        $this->assertNothingSaved('forms', fn () => $this->putJson('/api/forms/lint-test', self::DEFINITION)
            ->assertStatus(422)->assertExactJson($answer));
    }

    public function test_iv2_warnings_next_to_another_key_are_not_recognized(): void
    {
        Http::fake([self::SERVICE.'/schema' => Http::response(['warnings' => [], 'error' => 'odd'], 200)]);

        $this->assertNothingSaved('forms', fn () => $this->putJson('/api/forms/lint-test', self::DEFINITION)
            ->assertStatus(502)->assertExactJson(['error' => 'SurveyJS service error']));
    }

    #[DataProvider('failures')]
    public function test_iv2_fails_closed(\Closure $answer, int $status, string $error): void
    {
        Http::fake([self::SERVICE.'/*' => $answer]);

        $this->assertNothingSaved('forms', fn () => $this->putJson('/api/forms/lint-test', self::DEFINITION)
            ->assertStatus($status)->assertExactJson(['error' => $error]));
    }

    public function test_iv2_is_still_an_editor_only_action(): void
    {
        Http::fake();
        $this->actingAs(User::where('email', 'bob@example.com')->firstOrFail());

        $this->putJson('/api/forms/lint-test', self::DEFINITION)->assertForbidden()->assertJsonStructure(['error']);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------------------------------------
    // IV.3 GET /api/claims/{id}/pdf

    private function claimId(): int
    {
        return (int) DB::table('responses')->where('form_id', 'claim')->value('id');
    }

    public function test_iv3_passes_the_pdf_through(): void
    {
        Http::fake([self::SERVICE.'/pdf' => Http::response('%PDF-1.7 fake', 200, ['Content-Type' => 'application/pdf'])]);

        $response = $this->get("/api/claims/{$this->claimId()}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('%PDF-1.7 fake', $response->getContent());
    }

    public function test_iv3_a_200_with_the_wrong_content_type_is_not_a_pdf(): void
    {
        Http::fake([self::SERVICE.'/pdf' => Http::response(['error' => 'schema is required'], 200)]);

        $this->getJson("/api/claims/{$this->claimId()}/pdf")->assertStatus(502)->assertExactJson(['error' => 'SurveyJS service error']);
    }

    public function test_iv3_schema_errors_are_a_server_error(): void
    {
        Http::fake([self::SERVICE.'/pdf' => Http::response(['errors' => [], 'warnings' => []], 422)]);

        $this->getJson("/api/claims/{$this->claimId()}/pdf")->assertStatus(502)->assertExactJson(['error' => 'SurveyJS service error']);
    }

    #[DataProvider('failures')]
    public function test_iv3_fails_closed(\Closure $answer, int $status, string $error): void
    {
        Http::fake([self::SERVICE.'/*' => $answer]);

        $this->getJson("/api/claims/{$this->claimId()}/pdf")->assertStatus($status)->assertExactJson(['error' => $error]);
    }

    public function test_iv3_an_unknown_claim_is_404_without_calling_the_service(): void
    {
        Http::fake();

        $this->getJson('/api/claims/999999/pdf')->assertNotFound()->assertJsonStructure(['error']);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------------------------------------
    // IV.4 POST /api/work-orders/extract

    private function extract()
    {
        return $this->post('/api/work-orders/extract', ['scan' => UploadedFile::fake()->createWithContent('scan.png', 'PNG bytes')], ['Accept' => 'application/json']);
    }

    public function test_iv4_returns_answers_in_the_forms_shape_and_saves_nothing(): void
    {
        $confidence = [['fieldName' => 'customer_name', 'value' => 'Ann', 'confidence' => 0.9, 'flagged' => false]];
        Http::fake([self::SERVICE.'/extract' => Http::response(['data' => ['customer_name' => 'Ann'], 'uniqueId' => 'WO-1', 'confidence' => $confidence], 200)]);

        $this->assertNothingSaved('responses', fn () => $this->extract()->assertOk()
            ->assertExactJson(['answers' => ['customer_name' => 'Ann'], 'confidence' => $confidence, 'uniqueId' => 'WO-1']));
        Http::assertSent(fn ($request) => json_decode($request->body(), true)['document'] === base64_encode('PNG bytes')
            && json_decode($request->body(), true)['schema']['title'] === 'Work order'
            && str_contains($request->header('Content-Type')[0] ?? '', 'application/json'));
    }

    public static function extractionRows(): array
    {
        return [
            '400 INVALID_DOCUMENT' => [400, ['error' => 'INVALID_DOCUMENT'], 400, 'The file is not a PDF, PNG, JPEG, WebP or GIF document'],
            '503 AI_NOT_CONFIGURED' => [503, ['error' => 'AI_NOT_CONFIGURED', 'message' => 'AI provider is not configured'], 503, 'The SurveyJS service has no AI provider configured'],
            '502 EXTRACTION_FAILED' => [502, ['error' => 'EXTRACTION_FAILED'], 502, 'Could not extract the response from the document'],
            '422 schema errors' => [422, ['errors' => [], 'warnings' => []], 502, 'SurveyJS service error'],
            '200 data is not an object' => [200, ['data' => ['a', 'b'], 'confidence' => []], 502, 'SurveyJS service error'],
            '200 without data' => [200, ['uniqueId' => null], 502, 'SurveyJS service error'],
        ];
    }

    #[DataProvider('extractionRows')]
    public function test_iv4_maps_the_service_answers(int $serviceStatus, array $body, int $status, string $error): void
    {
        Http::fake([self::SERVICE.'/extract' => Http::response($body, $serviceStatus)]);

        $this->assertNothingSaved('responses', fn () => $this->extract()->assertStatus($status)->assertExactJson(['error' => $error]));
    }

    #[DataProvider('failures')]
    public function test_iv4_fails_closed(\Closure $answer, int $status, string $error): void
    {
        Http::fake([self::SERVICE.'/*' => $answer]);

        $this->assertNothingSaved('responses', fn () => $this->extract()->assertStatus($status)->assertExactJson(['error' => $error]));
    }

    public function test_iv4_refuses_a_scan_over_5_mb(): void
    {
        Http::fake();

        $this->post('/api/work-orders/extract', ['scan' => UploadedFile::fake()->create('big.png', 5 * 1024 + 1)], ['Accept' => 'application/json'])
            ->assertStatus(413)->assertJsonStructure(['error']);
        Http::assertNothingSent();
    }

    private function assertNothingSaved(string $table, \Closure $request): void
    {
        $before = DB::table($table)->count();
        $request();
        $this->assertSame($before, DB::table($table)->count(), "Nothing may be saved in {$table}");
    }
}
