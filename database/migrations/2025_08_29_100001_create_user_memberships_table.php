<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('user_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->constrained('membership_tiers')->cascadeOnDelete();
            $table->string('status')->default('active'); // active, expired, grace
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->unsignedInteger('ai_quota_total')->default(0);
            $table->unsignedInteger('ai_quota_used')->default(0);
            $table->unsignedInteger('credit_balance')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('user_memberships'); }
};
