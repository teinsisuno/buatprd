<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('project_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step'); // 1-8
            $table->string('title');
            $table->longText('content')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->text('ai_prompt')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'step']);
        });
    }
    public function down(): void { Schema::dropIfExists('project_sections'); }
};
