<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\DemoUser;
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

    public function test_links_follow_the_scheme_of_a_tls_proxy(): void
    {
        $http = url('/examples/');   // http://<APP_URL host>/examples

        $this->withoutVite()->get('/', ['X-Forwarded-Proto' => 'https'])
            ->assertOk()
            ->assertSee('href="'.preg_replace('~^http:~', 'https:', $http), false)
            ->assertDontSee('href="'.$http, false);
    }

    public function test_an_unknown_step_is_not_found(): void
    {
        $this->get('/examples/nothing-here')->assertNotFound();
    }

    #[DataProvider('steps')]
    public function test_the_demo_user_switch_is_only_on_pages_that_show_an_authorization_decision(string $id, string $slug): void
    {
        $response = $this->withoutVite()->get("/examples/{$slug}")->assertOk();

        if (array_key_exists($slug, DemoUser::PAGES)) {
            $response->assertSee('id="demo-user"', false);
        } else {
            $response->assertDontSee('id="demo-user"', false);
        }
    }

    #[DataProvider('steps')]
    public function test_every_step_page_links_to_its_docs_section_once(string $id, string $slug): void
    {
        $docs = Demo::docsUrl(Demo::manifest()['steps'][$id]);
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'href="'.e($docs).'"'), "{$slug} links to {$docs} once");
        $this->assertSame(1, substr_count($html, 'href="'.e(config('surveyjs.docs'))), "{$slug} has no other docs link");
    }

    #[DataProvider('steps')]
    public function test_every_step_page_has_the_three_tabs_and_no_dock(string $id, string $slug): void
    {
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();

        $this->assertStringContainsString('role="tablist" aria-label="Step views"', $html);
        foreach (['demo', 'description', 'hood'] as $tab) {
            $this->assertMatchesRegularExpression('~<button type="button" role="tab" id="tab-'.$tab.'"[^>]*aria-controls="panel-'.$tab.'"~', $html);
            $this->assertMatchesRegularExpression('~<div id="panel-'.$tab.'" role="tabpanel" aria-labelledby="tab-'.$tab.'"~', $html);
        }
        foreach (['code', 'requests', 'stored'] as $section) {
            $this->assertMatchesRegularExpression('~role="tab" id="hood-tab-'.$section.'"[^>]*aria-controls="hood-'.$section.'"~', $html);
            $this->assertStringContainsString('<section id="hood-'.$section.'" role="tabpanel"', $html);
        }
        foreach (['data-request', 'data-stored', 'data-dock-open', 'id="dock"', 'dock.js'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, "{$slug} still has {$gone}");
        }
    }

    #[DataProvider('steps')]
    public function test_the_header_links_each_logged_endpoint_and_stored_section(string $id, string $slug): void
    {
        $step = Demo::manifest()['steps'][$id];
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();
        $nav = $this->between($html, 'id="live-links"', '</nav>');

        $endpoints = array_values(array_filter($step['endpoints'], fn ($e) => preg_match('~^[A-Z]+ /(api|ws)/~', $e)));
        $stored = array_keys($this->getJson("/demo/stored/{$slug}")->json());
        preg_match_all('~data-live="(endpoint|stored)" data-label="([^"]*)"~', $nav, $links, PREG_SET_ORDER);

        $this->assertSame(
            [...array_map(fn ($e) => ['endpoint', $e], $endpoints), ...array_map(fn ($s) => ['stored', $s], $stored)],
            array_map(fn ($m) => [$m[1], html_entity_decode($m[2])], $links),
        );
        $this->assertNotEmpty($links, "{$slug} has no live links");
        if ($slug === 'load-definition-variables-data') {
            $this->assertStringNotContainsString('GET /claim', $nav);   // not under /api/: host.js never logs it
        }
    }

    #[DataProvider('steps')]
    public function test_description_holds_try_this_and_demo_holds_the_component(string $id, string $slug): void
    {
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();
        $demo = $this->between($html, 'id="panel-demo"', 'id="panel-description"');
        $description = $this->between($html, 'id="panel-description"', 'id="panel-hood"');
        $hood = $this->between($html, 'id="panel-hood"', '<template id="request-row">');

        $this->assertStringContainsString('id="component"', $demo);
        $this->assertStringNotContainsString('id="try"', $demo);
        $this->assertStringNotContainsString('id="notes"', $demo);
        $this->assertStringContainsString('id="try"', $description);
        $this->assertStringContainsString('<li', $this->between($description, 'id="try"', '</ol>'));
        $this->assertStringContainsString('id="notes"', $hood);

        // "Try this" names controls; it holds none
        $try = $this->between($description, 'id="try"', '</ol>');
        $this->assertDoesNotMatchRegularExpression('~<(button|input|select)\b|data-invite-link~', $try, "{$slug}: a control in Try this");
    }

    public static function boundIds(): array
    {
        return [
            'save-response' => ['save-response', ['start-over']],
            'load-definition-variables-data' => ['load-definition-variables-data', ['start-over']],
            'resume-progress' => ['resume-progress', ['reload', 'start-over']],
            'store-files' => ['store-files', ['demo-user', 'start-over']],
            'choices-from-web' => ['choices-from-web', ['start-over']],
            'async-functions' => ['async-functions', ['start-over']],
            'relational-storage' => ['relational-storage', ['start-over']],
            'validate-response' => ['validate-response', ['start-over']],
            'extract-from-paper' => ['extract-from-paper', ['sample-scan', 'own-scan', 'start-over']],
            'dashboard' => ['dashboard', ['from']],
            'creator-load-save' => ['creator-load-save', ['demo-user']],
            'variable-presets' => ['variable-presets', ['demo-user']],
        ];
    }

    #[DataProvider('boundIds')]
    public function test_the_ids_the_step_scripts_bind_are_in_the_action_row(string $slug, array $ids): void
    {
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();
        $actions = $this->between($html, 'id="actions"', 'id="component"');

        foreach ($ids as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $actions, "{$slug}: #{$id} is not in the action row");
        }
    }

    /** The Form Library examples (I.1–I.7, IV.1, IV.4) have Start over; I.8 (fill-together) doesn't. */
    private const START_OVER = ['save-response', 'load-definition-variables-data', 'resume-progress', 'store-files', 'choices-from-web',
        'async-functions', 'relational-storage', 'validate-response', 'extract-from-paper'];

    #[DataProvider('steps')]
    public function test_start_over_is_on_every_form_library_page_except_fill_together(string $id, string $slug): void
    {
        $client = (string) file_get_contents(base_path(Demo::manifest()['steps'][$id]['files']['client']));
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();

        if (in_array($slug, self::START_OVER, true)) {
            $this->assertStringContainsString('id="start-over"', $this->between($html, 'id="actions"', 'id="component"'));
            $this->assertStringContainsString('getElementById("start-over")', $client, "{$slug}: the step script doesn't bind #start-over");
        } else {
            $this->assertStringNotContainsString('id="start-over"', $html);
        }
    }

    public function test_the_read_outs_stay_where_they_belong(): void
    {
        $page = fn (string $slug) => $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();

        $this->assertStringContainsString('data-show="saved"', $this->between($page('resume-progress'), 'id="readouts"', 'id="notes"'));
        $this->assertStringContainsString('data-show="sizes"', $this->between($page('store-files'), 'id="readouts"', 'id="notes"'));
        $this->assertStringContainsString('data-show="extract-status"', $this->between($page('extract-from-paper'), 'id="actions"', 'id="component"'));
        $this->assertStringContainsString('data-show="room"', $this->between($page('fill-together'), 'id="actions"', 'id="component"'));
        foreach (['fill-together', 'edit-together'] as $slug) {
            $actions = $this->between($page($slug), 'id="actions"', 'id="component"');
            $this->assertStringContainsString('data-copy-invite', $actions);
            $this->assertMatchesRegularExpression('~<a data-invite-link target="_blank"~', $actions);
        }
        $this->assertStringNotContainsString('data-show="posted"', $page('save-response'));
    }

    public function test_the_request_row_template_has_the_body_slots(): void
    {
        $template = $this->between($this->withoutVite()->get('/examples/save-response')->getContent(), '<template id="request-row">', '</template>');

        foreach (['method', 'url', 'status', 'time', 'headers', 'request-body', 'response-body'] as $slot) {
            $this->assertStringContainsString('data-field="'.$slot.'"', $template);
        }
        $this->assertStringContainsString('data-row-toggle aria-expanded="false"', $template);
    }

    private function between(string $html, string $from, string $to): string
    {
        $start = strpos($html, $from);
        $this->assertNotFalse($start, "{$from} not found");
        $end = strpos($html, $to, $start + strlen($from));
        $this->assertNotFalse($end, "{$to} not found after {$from}");

        return substr($html, $start, $end - $start);
    }

    #[DataProvider('steps')]
    public function test_the_stored_panel_answers_for_every_step(string $id, string $slug): void
    {
        $this->getJson("/demo/stored/{$slug}")->assertOk();
    }
}
