<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wizard_prompt_id')->constrained('wizard_prompts')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('system');
            $table->longText('user_template');
            $table->text('json_schema')->nullable();
            $table->string('name', 80);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_note')->nullable();
            $table->timestamps();
            $table->unique(['wizard_prompt_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_prompt_versions');
    }
};
