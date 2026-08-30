<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_prompts', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('step')->unique();
            $table->string('name', 80);
            $table->text('system');
            $table->longText('user_template');
            $table->text('json_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_prompts');
    }
};
