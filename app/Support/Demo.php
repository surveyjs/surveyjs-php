<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Middleware\DemoUser;
use App\Relay\RoomKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Demo only: what the step pages show around the form. The manifest, the code panel's regions
 * and the "What the server stored" panel. Nothing here is part of an integration.
 */
final class Demo
{
    private static ?array $manifest = null;

    public static function manifest(): array
    {
        return self::$manifest ??= json_decode((string) file_get_contents(base_path('surveyjs-integration.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array{0: string, 1: array}|null  [step id, step] for a slug from the manifest */
    public static function step(string $slug): ?array
    {
        foreach (self::manifest()['steps'] as $id => $step) {
            if ($step['slug'] === $slug) {
                return [$id, $step];
            }
        }

        return null;
    }

    /** window.SURVEYJS_PAGE: what shared/client/host.js and the step modules read. */
    public static function pageConfig(string $stepId, array $step): array
    {
        $user = Auth::user();
        $definitions = collect($step['files'])
            ->filter(fn (string $path, string $key) => str_starts_with($key, 'definition'))
            ->map(fn (string $path) => json_decode((string) file_get_contents(base_path($path)), flags: JSON_THROW_ON_ERROR));

        return [
            'step' => $stepId,
            'slug' => $step['slug'],
            'version' => self::manifest()['surveyjsVersion'],
            'licenseKey' => config('surveyjs.license_key'),
            'user' => $user ? [
                'key' => array_search($user->email, DemoUser::USERS, true) ?: null,
                'name' => $user->name,
                'plan' => $user->plan,
                'isEditor' => $user->is_editor,
            ] : null,
            'relay' => [
                'fill' => ['url' => config('surveyjs.relay.fill_url'), 'port' => config('surveyjs.relay.fill_port')],
                'edit' => ['url' => config('surveyjs.relay.edit_url'), 'port' => config('surveyjs.relay.edit_port')],
            ],
            'demoMode' => config('surveyjs.demo_mode'),
            'sandboxId' => request()->attributes->get('demo_sid'),
            'switches' => [
                'validateResponses' => config('surveyjs.validate_responses'),
                'lintDefinitions' => config('surveyjs.lint_definitions'),
            ],
            ...$definitions->all(),
        ];
    }

    /**
     * Find every `#region sjs:<id>` in a file.
     *
     * @return array<string, array{start: int, end: int, code: string}> start/end are 1-based lines of the code inside the markers
     */
    public static function regionsIn(string $path): array
    {
        $lines = preg_split('/\R/', (string) file_get_contents(base_path($path)));
        $regions = [];
        $open = null;

        foreach ($lines as $i => $line) {
            if (preg_match('~^\s*//\s*#region\s+sjs:(\S+)~', $line, $m)) {
                $open = ['id' => $m[1], 'from' => $i + 1];
            } elseif ($open && preg_match('~^\s*//\s*#endregion~', $line)) {
                $body = array_slice($lines, $open['from'], $i - $open['from']);
                $regions[$open['id']][] = ['start' => $open['from'] + 1, 'end' => $i, 'code' => self::dedent($body)];
                $open = null;
            }
        }

        return array_map(fn (array $found) => count($found) === 1 ? $found[0] : ['duplicate' => count($found)] + $found[0], $regions);
    }

    /** The regions a step lists in the manifest, ready for the code panel. */
    public static function codePanel(array $step): array
    {
        $panel = [];
        foreach (['client', 'server'] as $role) {
            $path = $step['files'][$role] ?? null;
            if ($path === null || ! is_file(base_path($path))) {
                continue;
            }
            foreach (self::regionsIn($path) as $id => $region) {
                if (in_array($id, $step['regions'], true)) {
                    $panel[] = [
                        'id' => $id,
                        'role' => $role,
                        'path' => $path,
                        'language' => str_ends_with($path, '.php') ? 'php' : 'javascript',
                        'code' => $region['code'],
                        'url' => sprintf('%s/blob/%s/%s#L%d-L%d', config('surveyjs.github'), self::manifest()['branch'], $path, $region['start'], $region['end']),
                    ];
                }
            }
        }

        return $panel;
    }

    private static function dedent(array $lines): string
    {
        $indent = min(array_map(
            fn (string $line) => strlen($line) - strlen(ltrim($line)),
            array_filter($lines, fn (string $line) => trim($line) !== '') ?: ['']
        ));

        return implode("\n", array_map(fn (string $line) => substr($line, $indent), $lines));
    }

    /**
     * GET /demo/stored/{slug}: a fixed, whitelisted read per step. Never arbitrary SQL.
     */
    public static function stored(string $slug, Request $request): ?array
    {
        $id = filter_var($request->query('id'), FILTER_VALIDATE_INT) ?: null;

        return match ($slug) {
            'save-response' => ['responses' => self::responses($id ? null : 'contact', $id)],
            'load-definition-variables-data' => ['responses' => self::responses('claim', $id)],
            'resume-progress' => ['progress' => self::documents('progress', $request->query('key', 'onboarding'))],
            'store-files' => [
                'responses' => self::responses('expenses', $id, 3),
                'files' => DB::table('files')->orderByDesc('rowid')->limit(10)->get(['id', 'name', 'type']),
            ],
            'choices-from-web' => [
                'offices' => DB::table('offices')->orderBy('region')->orderBy('name')->get(),
                'countries cache' => Cache::has('countries') ? 'cached for 24 hours' : 'empty: the next request fetches restcountries.com',
            ],
            'async-functions' => [
                'customers' => DB::table('customers')->pluck('email'),
                'shipping_rates' => DB::table('shipping_rates')->orderBy('postcode_prefix')->get(),
            ],
            'relational-storage' => self::claims($id),
            'fill-together' => [
                'relay room' => self::relaySnapshot($request, 'fill'),
                'responses' => self::responses('job-sheet', $id, 3),
            ],
            'dashboard' => [
                'responses' => DB::table('responses')->where('form_id', 'feedback')->count(),
                'since from' => DB::table('responses')->where('form_id', 'feedback')
                    ->where('created_at', '>=', (string) $request->query('from', '1970-01-01'))->count(),
            ],
            'creator-load-save', 'ai-translation', 'lint-definition' => ['forms' => self::documents('forms', $request->query('key', 'support'))],
            'creator-uploads' => [
                'logo in the definition' => json_decode((string) DB::table('forms')->where('key', 'support')->value('json'))->logo ?? null,
                'files' => DB::table('files')->orderByDesc('rowid')->limit(5)->get(['id', 'name', 'type']),
            ],
            'variable-presets' => ['variable_presets' => self::documents('variable_presets', $request->query('key', 'support'))],
            'edit-together' => [
                'relay room' => self::relaySnapshot($request, 'edit'),
                'forms' => self::documents('forms', 'support'),
            ],
            'validate-response' => ['responses' => self::responses('claim', $id)],
            'server-pdf' => self::claims(null, 10),
            'extract-from-paper' => ['row counts' => self::rowCounts()],
            default => null,
        };
    }

    /** Stored responses to pick from on a step page: [id => "#id · label"]. */
    public static function records(string $formId, string $labelKey, int $limit = 10): array
    {
        return DB::table('responses')->where('form_id', $formId)->orderBy('id')->limit($limit)->get(['id', 'data'])
            ->mapWithKeys(fn ($row) => [$row->id => "#{$row->id} · ".(json_decode($row->data)->{$labelKey} ?? '')])
            ->all();
    }

    private static function responses(?string $formId, ?int $id, int $limit = 5): array
    {
        return DB::table('responses')
            ->when($formId, fn ($q) => $q->where('form_id', $formId))
            ->when($id, fn ($q) => $q->where('id', $id))
            ->orderByDesc('id')->limit($limit)
            ->get()
            ->map(fn ($row) => ['data' => json_decode($row->data)] + (array) $row)
            ->all();
    }

    private static function documents(string $table, mixed $key): ?object
    {
        $json = is_string($key) ? DB::table($table)->where('key', $key)->value('json') : null;

        return $json === null ? null : (object) ['key' => $key, 'json' => json_decode($json)];
    }

    private static function claims(?int $id, int $limit = 3): array
    {
        $rows = DB::table('claims')->join('responses', 'responses.id', '=', 'claims.response_id')
            ->when($id, fn ($q) => $q->where('responses.id', $id))
            ->orderByDesc('claims.id')->limit($limit)
            ->get(['claims.id as claim_id', 'claims.response_id', 'claims.customer_email', 'claims.amount',
                'responses.form_id', 'responses.definition_version', 'responses.created_at', 'responses.data']);

        return [
            'claims' => $rows->map(fn ($row) => array_diff_key((array) $row, ['form_id' => 1, 'definition_version' => 1, 'created_at' => 1, 'data' => 1]))->all(),
            'responses' => $rows->map(fn ($row) => [
                'id' => $row->response_id,
                'form_id' => $row->form_id,
                'definition_version' => $row->definition_version,
                'created_at' => $row->created_at,
                'data' => json_decode($row->data),
            ])->all(),
        ];
    }

    private static function relaySnapshot(Request $request, string $relay): mixed
    {
        $id = (string) $request->query('room', '');
        if (! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) {
            return null;
        }
        if (! config('surveyjs.demo_mode')) {
            return 'The relay writes room snapshots in demo mode only (DEMO_MODE=true)';
        }
        $path = RoomKey::snapshotPath(RoomKey::for($request->attributes->get('demo_sid'), $id), $relay);

        return is_file($path) ? json_decode((string) file_get_contents($path)) : 'No snapshot yet: nobody has joined the room';
    }

    private static function rowCounts(): array
    {
        $counts = [];
        foreach (['forms', 'responses', 'progress', 'files', 'claims', 'variable_presets'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }
}
