<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique(); // openai, gemini, anthropic, groq, deepseek, openrouter, mistral
            $table->string('label'); // Display name
            $table->text('api_key')->nullable(); // encrypted
            $table->string('base_url')->nullable(); // custom endpoint (for openrouter etc)
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('available_models')->nullable(); // fetch result: [{id,label}]
            $table->json('enabled_models')->nullable(); // admin checklist: ["gpt-4o-mini", "gemini-1.5-flash"]
            $table->json('config')->nullable(); // extra: temperature, max_tokens default
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ai_providers'); }
};
