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

        return collect($manifest['steps'])->mapWithKeys(fn ($step) => [$step['slug'] => [$step['slug']]])->all();
    }

    public function test_the_catalog_lists_every_step(): void
    {
        $response = $this->get('/')->assertOk();

        foreach (Demo::manifest()['steps'] as $step) {
            $response->assertSee($step['title'])->assertSee('/examples/'.$step['slug'], false);
        }
    }

    public function test_the_catalog_hero_links_to_the_repository_and_every_section(): void
    {
        $response = $this->withoutVite()->get('/')->assertOk()
            ->assertSee('href="'.config('surveyjs.github').'" target="_blank">', false)
            ->assertSee('View on GitHub')
            ->assertDontSee('View examples')
            ->assertDontSee('SURVEYJS_LICENSE_KEY');

        $html = (string) $response->getContent();
        preg_match_all('~<a [^>]*href="#([^"]+)"~', $html, $links);

        $this->assertSame(
            ['run-forms', 'analyze-results-with-dashboard', 'edit-forms-in-survey-creator', 'run-server-side-workflows'],
            $links[1],
        );
        foreach ($links[1] as $id) {
            $this->assertStringContainsString('<section id="'.$id.'"', $html);
        }
    }

    public function test_every_catalog_card_shows_its_licence_and_no_step_number(): void
    {
        $html = (string) $this->withoutVite()->get('/')->assertOk()->getContent();
        preg_match_all('~<article.*?</article>~s', $html, $cards);
        $steps = Demo::manifest()['steps'];

        $this->assertCount(count($steps), $cards[0]);

        foreach ($cards[0] as $card) {
            $this->assertMatchesRegularExpression('~>(MIT|Commercial)</span>~', $card);
            $this->assertDoesNotMatchRegularExpression('~>\d(\.\d)?</span>~', $card);
        }
        $this->assertSame(
            collect($steps)->filter(fn ($step) => str_contains($step['licence'], 'needs an AI provider'))->count(),
            substr_count($html, '>Needs an AI provider</span>'),
        );
        // The licence moved from the section headers to the cards
        $this->assertSame(count($steps), preg_match_all('~>(MIT|Commercial)</span>~', $html));
    }

    #[DataProvider('steps')]
    public function test_every_step_page_renders_and_loads_its_client_module(string $slug): void
    {
        $manifestPath = public_path('build/manifest.json');
        if (! is_file($manifestPath)) {
            $this->markTestSkipped('Run `npm run build` first: the page loads its module through Vite.');
        }
        $client = Demo::step($slug)['files']['client'];
        $built = json_decode((string) file_get_contents($manifestPath), true)[$client]['file'] ?? null;

        $this->assertNotNull($built, "{$client} is not a Vite entry point");
        $this->get("/examples/{$slug}")
            ->assertOk()
            ->assertSee('/build/'.$built, false)
            ->assertSee(str_replace('"', '\\'.'u0022', '"slug":"'.$slug.'"'), false)   // window.SURVEYJS_PAGE, escaped by Js::from()
            ->assertSee("sjs:{$slug}.", false);   // the code panel shows the step's regions
    }

    #[DataProvider('steps')]
    public function test_every_step_page_goes_by_its_title_alone(string $slug): void
    {
        $title = e(Demo::step($slug)['title']);
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();

        $this->assertStringContainsString("<title>{$title} · SurveyJS + Laravel</title>", $html);
        $this->assertMatchesRegularExpression('~<h1[^>]*>'.preg_quote($title, '~').'</h1>~', $html);
        $this->assertDoesNotMatchRegularExpression(
            '~(?<![\w.])[1-4]\.[1-8](?![\w.])~',
            strip_tags($this->between($html, 'id="panel-description"', 'id="panel-requests"')),
        );
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
    public function test_the_demo_user_switch_is_only_on_pages_that_show_an_authorization_decision(string $slug): void
    {
        $response = $this->withoutVite()->get("/examples/{$slug}")->assertOk();

        if (array_key_exists($slug, DemoUser::PAGES)) {
            $response->assertSee('id="demo-user"', false);
        } else {
            $response->assertDontSee('id="demo-user"', false);
        }
    }

    #[DataProvider('steps')]
    public function test_every_step_page_links_to_its_docs_section_once(string $slug): void
    {
        $docs = Demo::docsUrl(Demo::step($slug));
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'href="'.e($docs).'"'), "{$slug} links to {$docs} once");
        $this->assertSame(1, substr_count($html, 'href="'.e(config('surveyjs.docs'))), "{$slug} has no other docs link");
    }

    #[DataProvider('steps')]
    public function test_every_step_page_has_the_side_panel_and_the_main_tabs(string $slug): void
    {
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<aside id="side-panel"~', $html);
        $this->assertMatchesRegularExpression('~<button type="button" id="panel-toggle" aria-controls="side-panel" aria-expanded="true"~', $html);
        $this->assertStringContainsString('id="panel-close"', $html);
        $this->assertStringContainsString('id="panel-tabs" role="tablist" aria-label="Description, requests and stored data"', $html);
        foreach (['description', 'requests', 'stored'] as $tab) {
            $this->assertMatchesRegularExpression('~<button type="button" role="tab" id="panel-tab-'.$tab.'"[^>]*aria-controls="panel-'.$tab.'"~', $html);
            $this->assertMatchesRegularExpression('~id="panel-'.$tab.'" role="tabpanel" aria-labelledby="panel-tab-'.$tab.'"~', $html);
        }
        $this->assertStringContainsString('id="page-tabs" role="tablist" aria-label="Step views"', $html);
        foreach (['demo', 'code'] as $tab) {
            $this->assertMatchesRegularExpression('~<button type="button" role="tab" id="tab-'.$tab.'"[^>]*aria-controls="panel-'.$tab.'"~', $html);
            $this->assertMatchesRegularExpression('~<div id="panel-'.$tab.'" role="tabpanel" aria-labelledby="tab-'.$tab.'"~', $html);
        }
        foreach (['id="requests"', 'id="stored"', 'id="notes"', 'id="hood"', '<template id="request-row">', '<template id="stored-section">', '<template id="note">'] as $kept) {
            $this->assertStringContainsString($kept, $html, "{$slug} has no {$kept}");
        }
        $this->assertStringContainsString('id="hood"', $this->between($html, '<aside id="side-panel"', '</aside>'));
        foreach (['id="live-links"', 'id="live-more"', 'data-live=', 'id="panel-hood"', 'id="hood-badge"', '>Endpoint<', '>Endpoints<', '>Files<', 'Under the hood',
            'data-request', 'data-stored', 'data-dock-open', 'id="dock"', 'dock.js'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, "{$slug} still has {$gone}");
        }
    }

    #[DataProvider('steps')]
    public function test_description_holds_try_this_and_demo_holds_the_component(string $slug): void
    {
        $html = $this->withoutVite()->get("/examples/{$slug}")->assertOk()->getContent();
        $panel = $this->between($html, '<aside id="side-panel"', '</aside>');
        $description = $this->between($panel, 'id="panel-description"', 'id="panel-requests"');
        $requests = $this->between($panel, 'id="panel-requests"', 'id="panel-stored"');
        $demo = $this->between($html, 'id="panel-demo"', 'id="panel-code"');

        $this->assertStringContainsString('id="component"', $demo);
        $this->assertStringNotContainsString('id="try"', $demo);
        $this->assertStringNotContainsString('id="notes"', $demo);
        $this->assertStringContainsString('id="try"', $description);
        $this->assertStringContainsString('<li', $this->between($description, 'id="try"', '</ol>'));
        $this->assertStringNotContainsString('data-open-tab="demo"', $description);   // the demo is beside the panel
        $this->assertStringContainsString('id="notes"', $requests);
        $this->assertStringContainsString('id="readouts"', $requests);

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

    /** Every Form Library example has Start over except fill-together. */
    private const START_OVER = ['save-response', 'load-definition-variables-data', 'resume-progress', 'store-files', 'choices-from-web',
        'async-functions', 'relational-storage', 'validate-response', 'extract-from-paper'];

    #[DataProvider('steps')]
    public function test_start_over_is_on_every_form_library_page_except_fill_together(string $slug): void
    {
        $client = (string) file_get_contents(base_path(Demo::step($slug)['files']['client']));
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
    public function test_the_stored_panel_answers_for_every_step(string $slug): void
    {
        $this->getJson("/demo/stored/{$slug}")->assertOk();
    }
}
