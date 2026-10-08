<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreFilesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_an_uploaded_html_file_never_runs_as_this_site(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::where('email', 'alice@example.com')->firstOrFail());
        $html = UploadedFile::fake()->createWithContent('page.html', '<!doctype html><script>alert(document.cookie)</script>');
        [$id] = $this->post('/api/files', ['files' => [$html]])->assertOk()->json();

        $this->get("/api/files/{$id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', 'sandbox');
    }
}
