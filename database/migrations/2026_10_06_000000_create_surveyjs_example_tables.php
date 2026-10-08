<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The tables the Server Integration page names. Definitions, responses, progress and presets
// are single JSON documents kept as text, exactly as they arrived.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('json');
        });

        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->string('form_id')->index();
            $table->string('definition_version')->nullable();
            $table->longText('data');
            $table->string('created_at')->index();   // ISO-8601 UTC, so `created_at >= from` is a string comparison
        });

        Schema::create('progress', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('json');
        });

        Schema::create('files', function (Blueprint $table) {
            $table->string('id')->primary();         // the bytes live on the "uploads" disk, named by this id
            $table->string('name');
            $table->string('type');
        });

        Schema::create('offices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('region')->index();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->string('email')->primary();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->string('postcode_prefix')->primary();
            $table->decimal('price', 8, 2);
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('responses');
            $table->string('customer_email')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
        });

        Schema::create('variable_presets', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('json');
        });

        // Demo users: {user.plan} in the forms, and who may edit them
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->default('basic');
            $table->boolean('is_editor')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan', 'is_editor']);
        });

        foreach (['variable_presets', 'claims', 'shipping_rates', 'customers', 'offices', 'files', 'progress', 'responses', 'forms'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
