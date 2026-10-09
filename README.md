# SurveyJS + PHP: the Server Integration examples on Laravel

This is the PHP code behind [Integrate SurveyJS with your backend](https://surveyjs.io/backend-integration/examples), as a Laravel application you can run step by step. Each step of that page has a page here that runs the real SurveyJS component against these routes, with a short "Try this" list, a live request log, a "What the server stored" panel and the code that just ran. SurveyJS has no backend of its own, so treat these as starting points for your own app.

- Live catalog: [surveyjs-php.demos.surveyjs.io](https://surveyjs-php.demos.surveyjs.io)
- The page: [surveyjs.io/backend-integration/examples](https://surveyjs.io/backend-integration/examples)
- SurveyJS demos: [app.demos.surveyjs.io](https://app.demos.surveyjs.io)
- The same examples for other platforms: [Node.js](https://github.com/surveyjs/surveyjs-nodejs), [ASP.NET Core](https://github.com/surveyjs/surveyjs-aspnet-mvc), [Python](https://github.com/surveyjs/surveyjs-python-flask), [Next.js](https://github.com/surveyjs/surveyjs-nextjs)

## Run it

You need PHP 8.3 or later (with `pdo_sqlite` and `fileinfo`), Composer, and Node.js 22 or later.

```bash
git clone https://github.com/surveyjs/surveyjs-php.git
cd surveyjs-php
composer setup     # install, .env, app key, SQLite database with demo data, npm install, npm run build
composer start     # http://localhost:8000, serving the built assets
```

While you work on the code, run `composer dev` instead of `composer start`: it starts the server, Vite with hot reload and both relays.

- **I.8 and III.5** (fill and edit together) need the WebSocket relays, two long-running processes: `composer relay` (port 8081) and `composer edit-relay` (port 8082). `composer dev` starts them for you.
- **Section IV** (validate, lint, PDF, extract) calls the SurveyJS service, which runs in Docker: `docker compose up -d surveyjs` publishes it on `http://localhost:3010`. Then set `VALIDATE_RESPONSES=true` and `LINT_DEFINITIONS=true` in `.env` to switch IV.1 and IV.2 on.
- Everything in containers: `docker compose up --build` runs the app on port 8000, both relays, the scheduler and the service (see [Docker](#docker)).

`composer start` runs PHP's built-in server with upload limits that fit 5 MB files (`php artisan serve` starts a child process that doesn't inherit `-d` flags). See `server.php`.

## Docker

The image runs with `APP_ENV=production`, `APP_DEBUG=false` and logs to `docker compose logs` (`LOG_CHANNEL=stderr`). These are environment variables, so they win over the `.env` the container copies from `.env.example`. PHP's built-in server runs 4 workers (`PHP_CLI_SERVER_WORKERS`, read from the process environment, not from `.env`), so a long IV.4 extraction doesn't hold up other visitors. That is enough for the demo; for your own production, serve Laravel with FrankenPHP or nginx + php-fpm. The `scheduler` service runs `php artisan schedule:work` for `demo:prune`.

### Behind a proxy

The app trusts `X-Forwarded-*` headers, so behind a TLS proxy (Traefik, nginx, Cloudflare) its URLs, assets and cookies use `https`. To serve the relays on the same host, route their paths to them and set `RELAY_URL` and `EDIT_RELAY_URL` to that origin. For Traefik, next to your entry point and TLS labels:

```yaml
services:
    app:
        labels:
            - traefik.http.routers.surveyjs.rule=Host(`example.com`)
            - traefik.http.services.surveyjs.loadbalancer.server.port=8000
    relay:
        labels:
            - traefik.http.routers.surveyjs-relay.rule=Host(`example.com`) && PathPrefix(`/ws/rooms/`)
            - traefik.http.services.surveyjs-relay.loadbalancer.server.port=8081
    edit-relay:
        labels:
            - traefik.http.routers.surveyjs-edit-relay.rule=Host(`example.com`) && PathPrefix(`/ws/forms/`)
            - traefik.http.services.surveyjs-edit-relay.loadbalancer.server.port=8082
```

```bash
RELAY_URL=wss://example.com EDIT_RELAY_URL=wss://example.com docker compose up -d --build
```

## Configuration

Copy `.env.example` to `.env` (`composer setup` does it). Besides Laravel's own settings:

| Variable | Default | What it does |
|---|---|---|
| `SURVEYJS_LICENSE_KEY` | empty | Calls `setLicenseKey()` on every page. Without it, the commercial components (Dashboard, Creator) show a licence banner. |
| `SURVEYJS_SERVICE_URL` | `http://localhost:3010` | The SurveyJS service for Section IV. Docker Compose sets `http://surveyjs:3000`. |
| `VALIDATE_RESPONSES` | `false` | IV.1: `routes/examples/validate-response.php` replaces `save-response.php`. |
| `LINT_DEFINITIONS` | `false` | IV.2: `routes/examples/lint-definition.php` replaces the PUT in `creator-load-save.php`. |
| `SURVEYJS_AI_PROVIDER`, `SURVEYJS_AI_MODEL`, `SURVEYJS_OLLAMA_BASE_URL`, `OPENAI_API_KEY`, `ANTHROPIC_API_KEY` | empty | IV.4: the AI provider the service extracts with (`openai`, `anthropic` or `ollama`). Docker Compose passes them to the service. |
| `AI_BASE_URL`, `AI_API_KEY`, `AI_MODEL` | OpenAI, empty, `gpt-4o-mini` | III.3: any OpenAI-compatible `/chat/completions` endpoint for machine translation. Without a key, `/api/translate` answers `501`. |
| `RELAY_PORT`, `EDIT_RELAY_PORT` | `8081`, `8082` | Where the relays listen. |
| `RELAY_URL`, `EDIT_RELAY_URL` | empty | The relay origin the pages use when a proxy serves the relays, for example `wss://example.com`: the pages add `/ws/rooms/<id>` and `/ws/forms/<id>`. Empty means this host on the ports above. |
| `DEMO_MODE` | `false` | The hosted demo's visitor sandboxes (below). |

Code reads these through `config/surveyjs.php`, never with `env()`, so `php artisan config:cache` works.

## The steps

<!-- steps:start (generated by `composer readme` from surveyjs-integration.json) -->
| Id | Step | Run | Server | Client | Page | Licence |
|---|---|---|---|---|---|---|
| I.1 | [Save the response](https://surveyjs.io/backend-integration/examples#save) | [/examples/save-response](https://surveyjs-php.demos.surveyjs.io/examples/save-response) | [`routes/examples/save-response.php`](routes/examples/save-response.php) | [`shared/client/save-response.js`](shared/client/save-response.js) | [`resources/views/examples/save-response.blade.php`](resources/views/examples/save-response.blade.php) | MIT |
| I.2 | [Load the definition, variables and previous answers](https://surveyjs.io/backend-integration/examples#load) | [/examples/load-definition-variables-data](https://surveyjs-php.demos.surveyjs.io/examples/load-definition-variables-data) | [`routes/examples/load-definition-variables-data.php`](routes/examples/load-definition-variables-data.php) | [`shared/client/load-definition-variables-data.js`](shared/client/load-definition-variables-data.js) | [`resources/views/examples/load-definition-variables-data.blade.php`](resources/views/examples/load-definition-variables-data.blade.php) | MIT |
| I.3 | [Resume where the user left off](https://surveyjs.io/backend-integration/examples#resume) | [/examples/resume-progress](https://surveyjs-php.demos.surveyjs.io/examples/resume-progress) | [`routes/examples/resume-progress.php`](routes/examples/resume-progress.php) | [`shared/client/resume-progress.js`](shared/client/resume-progress.js) | [`resources/views/examples/resume-progress.blade.php`](resources/views/examples/resume-progress.blade.php) | MIT |
| I.4 | [Store files outside the response](https://surveyjs.io/backend-integration/examples#files) | [/examples/store-files](https://surveyjs-php.demos.surveyjs.io/examples/store-files) | [`routes/examples/store-files.php`](routes/examples/store-files.php) | [`shared/client/store-files.js`](shared/client/store-files.js) | [`resources/views/examples/store-files.blade.php`](resources/views/examples/store-files.blade.php) | MIT |
| I.5 | [Choices from the web](https://surveyjs.io/backend-integration/examples#choices) | [/examples/choices-from-web](https://surveyjs-php.demos.surveyjs.io/examples/choices-from-web) | [`routes/examples/choices-from-web.php`](routes/examples/choices-from-web.php) | [`shared/client/choices-from-web.js`](shared/client/choices-from-web.js) | [`resources/views/examples/choices-from-web.blade.php`](resources/views/examples/choices-from-web.blade.php) | MIT |
| I.6 | [Async functions: calculations and validation](https://surveyjs.io/backend-integration/examples#async) | [/examples/async-functions](https://surveyjs-php.demos.surveyjs.io/examples/async-functions) | [`routes/examples/async-functions.php`](routes/examples/async-functions.php) | [`shared/client/async-functions.js`](shared/client/async-functions.js) | [`resources/views/examples/async-functions.blade.php`](resources/views/examples/async-functions.blade.php) | MIT |
| I.7 | [Store responses in a relational database](https://surveyjs.io/backend-integration/examples#relational) | [/examples/relational-storage](https://surveyjs-php.demos.surveyjs.io/examples/relational-storage) | [`routes/examples/relational-storage.php`](routes/examples/relational-storage.php) | [`shared/client/relational-storage.js`](shared/client/relational-storage.js) | [`resources/views/examples/relational-storage.blade.php`](resources/views/examples/relational-storage.blade.php) | MIT |
| I.8 | [Fill one form together](https://surveyjs.io/backend-integration/examples#fill-together) | [/examples/fill-together](https://surveyjs-php.demos.surveyjs.io/examples/fill-together) | [`app/Relay/FillRelay.php`](app/Relay/FillRelay.php) | [`shared/client/fill-together.js`](shared/client/fill-together.js) | [`resources/views/examples/fill-together.blade.php`](resources/views/examples/fill-together.blade.php) | MIT |
| II | [See results in a dashboard](https://surveyjs.io/backend-integration/examples#dashboard) | [/examples/dashboard](https://surveyjs-php.demos.surveyjs.io/examples/dashboard) | [`routes/examples/dashboard.php`](routes/examples/dashboard.php) | [`shared/client/dashboard.js`](shared/client/dashboard.js) | [`resources/views/examples/dashboard.blade.php`](resources/views/examples/dashboard.blade.php) | Commercial client |
| III.1 | [Load and save definitions](https://surveyjs.io/backend-integration/examples#creator-save) | [/examples/creator-load-save](https://surveyjs-php.demos.surveyjs.io/examples/creator-load-save) | [`routes/examples/creator-load-save.php`](routes/examples/creator-load-save.php) | [`shared/client/creator-load-save.js`](shared/client/creator-load-save.js) | [`resources/views/examples/creator-load-save.blade.php`](resources/views/examples/creator-load-save.blade.php) | Commercial client |
| III.2 | [Upload images and files from Creator](https://surveyjs.io/backend-integration/examples#creator-files) | [/examples/creator-uploads](https://surveyjs-php.demos.surveyjs.io/examples/creator-uploads) | — | [`shared/client/creator-uploads.js`](shared/client/creator-uploads.js) | [`resources/views/examples/creator-uploads.blade.php`](resources/views/examples/creator-uploads.blade.php) | Commercial client |
| III.3 | [Translate strings with AI](https://surveyjs.io/backend-integration/examples#creator-translate) | [/examples/ai-translation](https://surveyjs-php.demos.surveyjs.io/examples/ai-translation) | [`routes/examples/ai-translation.php`](routes/examples/ai-translation.php) | [`shared/client/ai-translation.js`](shared/client/ai-translation.js) | [`resources/views/examples/ai-translation.blade.php`](resources/views/examples/ai-translation.blade.php) | Commercial client, needs an AI provider |
| III.4 | [Variable presets](https://surveyjs.io/backend-integration/examples#creator-presets) | [/examples/variable-presets](https://surveyjs-php.demos.surveyjs.io/examples/variable-presets) | [`routes/examples/variable-presets.php`](routes/examples/variable-presets.php) | [`shared/client/variable-presets.js`](shared/client/variable-presets.js) | [`resources/views/examples/variable-presets.blade.php`](resources/views/examples/variable-presets.blade.php) | Commercial client |
| III.5 | [Edit one form together](https://surveyjs.io/backend-integration/examples#edit-together) | [/examples/edit-together](https://surveyjs-php.demos.surveyjs.io/examples/edit-together) | [`app/Relay/EditRelay.php`](app/Relay/EditRelay.php) | [`shared/client/edit-together.js`](shared/client/edit-together.js) | [`resources/views/examples/edit-together.blade.php`](resources/views/examples/edit-together.blade.php) | Commercial client |
| IV.1 | [Validate the response against the definition](https://surveyjs.io/backend-integration/examples#validate) | [/examples/validate-response](https://surveyjs-php.demos.surveyjs.io/examples/validate-response) | [`routes/examples/validate-response.php`](routes/examples/validate-response.php) | [`shared/client/validate-response.js`](shared/client/validate-response.js) | [`resources/views/examples/validate-response.blade.php`](resources/views/examples/validate-response.blade.php) | MIT |
| IV.2 | [Lint definitions before saving](https://surveyjs.io/backend-integration/examples#lint) | [/examples/lint-definition](https://surveyjs-php.demos.surveyjs.io/examples/lint-definition) | [`routes/examples/lint-definition.php`](routes/examples/lint-definition.php) | [`shared/client/lint-definition.js`](shared/client/lint-definition.js) | [`resources/views/examples/lint-definition.blade.php`](resources/views/examples/lint-definition.blade.php) | MIT |
| IV.3 | [Export forms to PDF on the server](https://surveyjs.io/backend-integration/examples#pdf) | [/examples/server-pdf](https://surveyjs-php.demos.surveyjs.io/examples/server-pdf) | [`routes/examples/server-pdf.php`](routes/examples/server-pdf.php) | [`shared/client/server-pdf.js`](shared/client/server-pdf.js) | [`resources/views/examples/server-pdf.blade.php`](resources/views/examples/server-pdf.blade.php) | Commercial |
| IV.4 | [Turn paper, PDF and images into responses](https://surveyjs.io/backend-integration/examples#extract) | [/examples/extract-from-paper](https://surveyjs-php.demos.surveyjs.io/examples/extract-from-paper) | [`routes/examples/extract-from-paper.php`](routes/examples/extract-from-paper.php) | [`shared/client/extract-from-paper.js`](shared/client/extract-from-paper.js) | [`resources/views/examples/extract-from-paper.blade.php`](resources/views/examples/extract-from-paper.blade.php) | MIT, needs an AI provider |
<!-- steps:end -->

## Where each step lives

Each step is two or three files you can read on their own:

- `routes/examples/<slug>.php`: the step's routes, the server code the page shows. `routes/examples.php` loads them in page order. They run in the stateless `api` group, so `fetch()` needs no CSRF token.
- `shared/client/<slug>.js`: the step's client code, a plain ES module. Vite bundles it as an entry point (`vite.config.js`).
- `resources/views/examples/<slug>.blade.php`: the step page: the description, the "Try this" list and the form.
- The relays are `app/Relay/FillRelay.php` (I.8) and `app/Relay/EditRelay.php` (III.5), started by `php artisan relay:fill` and `relay:edit`.

The code the Server Integration page shows is cut from these files between `// #region sjs:<step>.server` (or `.client`) and `// #endregion`. `surveyjs-integration.json` lists every step's files and regions; `composer check` verifies them, and `composer contract` runs the shared contract test (`shared/contract-tests/run.mjs`) against a fresh database, the app and both relays.

### Storing JSON exactly as it arrives

A response, a definition, saved progress and variable presets are each one JSON document, stored as text and returned as stored. Laravel changes request bodies in two ways by default, and the examples turn both off:

- The global `TrimStrings` and `ConvertEmptyStringsToNull` middleware skip `/api/*` (`bootstrap/app.php`), so `"  a "` and `""` survive.
- The routes decode the raw body with `json_decode($request->getContent())`, which keeps `{}` as an object. `$request->input()` would turn it into a PHP array and save `[]`.

The step tables use the query builder (`DB::table()`), so "a response is one JSON document" stays visible. An Eloquent model with `protected $casts = ['data' => 'array']` works as well, with the same `{}` caveat (cast to `'object'` to keep it).

## How data works in the demo

Locally, everyone shares one SQLite database, `database/database.sqlite` (`php artisan migrate:fresh --seed` resets it).

On the hosted demo (`DEMO_MODE=true`), each visitor gets a private copy of the seeded database and uploads folder, chosen by a `demo_sid` cookie, and kept until it has been unused for 24 hours (`php artisan demo:prune`, scheduled hourly). The "Open a second window" link in I.8 and III.5 carries that id (`?join=`), so whoever opens it works in the same copy and the same relay rooms. This is demo-only: none of it is in the step code.

## Not on Laravel?

The routes only use the request, the database and the response, so they port in a few lines. I.1 in a Symfony controller:

```php
#[Route('/api/responses', methods: ['POST'])]
public function saveResponse(Request $request, Connection $db): JsonResponse
{
    $body = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);   // keeps {} as {}
    $db->insert('responses', [
        'form_id' => $body->formId,
        'data' => json_encode($body->data, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
        'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ]);

    return new JsonResponse(['id' => (int) $db->lastInsertId()], 201);
}
```

And in Slim:

```php
$app->post('/api/responses', function (Request $request, Response $response) use ($pdo) {
    $body = json_decode((string) $request->getBody(), flags: JSON_THROW_ON_ERROR);   // not getParsedBody(): keeps {} as {}
    $pdo->prepare('INSERT INTO responses (form_id, data, created_at) VALUES (?, ?, ?)')
        ->execute([$body->formId, json_encode($body->data, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION), gmdate('Y-m-d\TH:i:s\Z')]);
    $response->getBody()->write(json_encode(['id' => (int) $pdo->lastInsertId()]));

    return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
});
```

## Front end

Tailwind CSS and Vite, as in any new Laravel app; the step pages are Blade views styled with Tailwind utility classes. SurveyJS comes from npm (`survey-core`, `survey-js-ui`, `survey-creator-js`, `survey-analytics`…, pinned to the version in `shared/surveyjs-version.json`), and the forms and Creator keep SurveyJS's own themes. The step modules in `shared/client/` are plain ES modules with no Laravel or Tailwind in them, so they also work in Livewire, Inertia or a page that isn't Laravel at all; the other platform repositories load the same files through an import map.

## Disclaimer

These examples must not be used as a real service as they are. They don't cover authentication, authorization, user management, access levels and other security aspects of a real survey service; your framework's documentation covers those. The places where real checks go are marked:

- `Gate::define('edit-forms')` and `Gate::define('read-file')` in `app/Providers/AppServiceProvider.php`: who may save definitions and presets, and who may read an uploaded file.
- `app/Http/Middleware/DemoUser.php`: the demo-user switch stands in for your session guard or Sanctum. The relays check the same cookie in `FillRelay::user()` and `EditRelay::editor()`.

When you copy a step into your app, leave out the demo-only files: `app/Http/Middleware/DemoUser.php`, `DemoSandbox.php` and `DemoCacheHeader.php` (and their lines in `bootstrap/app.php`), `app/Support/Demo.php`, `app/Support/Sandbox.php`, `app/Relay/RoomKey.php`'s sandbox helpers, `app/Console/Commands/DemoPrune.php`, `routes/web.php`, `resources/views/`, and `shared/client/host.js` (its `mountSurvey()` is one line: `renderSurvey(survey, element)` from `survey-js-ui`).
