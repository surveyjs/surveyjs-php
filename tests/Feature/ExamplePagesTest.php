<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Demo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExamplePagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public static function steps(): array
    {
        $manifest = json_decode((string) file_get_contents(__DIR__.'/../../surveyjs-integration.json'), true);

        return collect($manifest['steps'])->mapWithKeys(fn ($step, $id) => [$id => [$id, $step['slug']]])->all();
    }

    public function test_the_catalog_lists_every_step(): void
    {
        $response = $this->get('/')->assertOk();

        foreach (Demo::manifest()['steps'] as $step) {
            $response->assertSee($step['title'])->assertSee('/examples/'.$step['slug'], false);
        }
    }

    #[DataProvider('steps')]
    public function test_every_step_page_renders_and_loads_its_client_module(string $id, string $slug): void
    {
        $manifestPath = public_path('build/manifest.json');
        if (! is_file($manifestPath)) {
            $this->markTestSkipped('Run `npm run build` first: the page loads its module through Vite.');
        }
        $client = Demo::manifest()['steps'][$id]['files']['client'];
        $built = json_decode((string) file_get_contents($manifestPath), true)[$client]['file'] ?? null;

        $this->assertNotNull($built, "{$client} is not a Vite entry point");
        $this->get("/examples/{$slug}")
            ->assertOk()
            ->assertSee('/build/'.$built, false)
            ->assertSee(str_replace('"', '\\'.'u0022', '"slug":"'.$slug.'"'), false)   // window.SURVEYJS_PAGE, escaped by Js::from()
            ->assertSee("sjs:{$id}.", false);   // the code panel shows the step's regions
    }

    public function test_an_unknown_step_is_not_found(): void
    {
        $this->get('/examples/nothing-here')->assertNotFound();
    }

    #[DataProvider('steps')]
    public function test_the_stored_panel_answers_for_every_step(string $id, string $slug): void
    {
        $this->getJson("/demo/stored/{$slug}")->assertOk();
    }
}
