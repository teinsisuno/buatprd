<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // default, basic, standart, premium
            $table->string('slug')->unique();
            $table->unsignedInteger('price')->default(0); // in IDR
            $table->unsignedInteger('duration_days')->default(30);
            $table->json('limits')->nullable(); // {max_projects, max_ai_per_month, can_export_pdf, can_mermaid, can_share}
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('membership_tiers'); }
};
