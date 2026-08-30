<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->boolean('is_template')->default(false);
            $table->foreignId('template_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'slug']);
        });
    }
    public function down(): void { Schema::dropIfExists('projects'); }
};
