<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds the demo users and every step's data from shared/seed/*.json,
 * the same files the other platform repositories seed from.
 */
class DatabaseSeeder extends Seeder
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    public function run(): void
    {
        if (DB::table('forms')->exists()) {
            $this->command?->info('Already seeded: run `php artisan migrate:fresh --seed` to start over.');

            return;
        }

        // Demo only: switch between them in the page header (see App\Http\Middleware\DemoUser).
        // No factory: the Docker image installs without dev dependencies, so there is no Faker.
        User::create(['name' => 'Alice Martin', 'email' => 'alice@example.com', 'password' => Hash::make(Str::random(32)), 'plan' => 'premium', 'is_editor' => true]);
        User::create(['name' => 'Bob Fischer', 'email' => 'bob@example.com', 'password' => Hash::make(Str::random(32)), 'plan' => 'basic', 'is_editor' => false]);

        foreach ($this->seed('forms.json', true) as $key => $file) {
            $definition = json_decode((string) file_get_contents(base_path('shared/definitions/'.$file)), flags: JSON_THROW_ON_ERROR);
            DB::table('forms')->insert(['key' => $key, 'json' => json_encode($definition, self::JSON_FLAGS)]);
        }

        DB::table('offices')->insert($this->seed('offices.json', true));
        DB::table('customers')->insert(array_map(fn (string $email) => ['email' => $email], $this->seed('customers.json', true)));
        DB::table('shipping_rates')->insert($this->seed('shipping-rates.json', true));

        foreach ($this->seed('claims.json') as $claim) {
            $responseId = $this->response('claim', $claim->data, $claim->daysAgo, $claim->definitionVersion);
            DB::table('claims')->insert([
                'response_id' => $responseId,
                'customer_email' => $claim->data->customer_email,
                'amount' => $claim->data->amount,
            ]);
        }

        foreach ($this->seed('job-sheets.json') as $sheet) {
            $this->response('job-sheet', $sheet->data, $sheet->daysAgo);
        }

        foreach (array_chunk($this->seed('feedback-responses.json'), 100) as $chunk) {
            DB::transaction(fn () => array_map(fn ($row) => $this->response('feedback', $row->data, $row->daysAgo), $chunk));
        }

        foreach ($this->seed('variable-presets.json') as $formId => $presets) {
            DB::table('variable_presets')->insert(['key' => $formId, 'json' => json_encode($presets, self::JSON_FLAGS)]);
        }
    }

    private function seed(string $file, bool $associative = false): mixed
    {
        return json_decode((string) file_get_contents(base_path('shared/seed/'.$file)), $associative, flags: JSON_THROW_ON_ERROR);
    }

    private function response(string $formId, object $data, int $daysAgo, ?string $version = null): int
    {
        $createdAt = now('UTC')->subDays($daysAgo)->setTime(9 + $daysAgo % 9, ($daysAgo * 7) % 60);

        return DB::table('responses')->insertGetId([
            'form_id' => $formId,
            'definition_version' => $version,
            'data' => json_encode($data, self::JSON_FLAGS),
            'created_at' => $createdAt->toIso8601ZuluString(),
        ]);
    }
}
