<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ex: LAUNCH50
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('discount_percent')->default(10); // 1-100
            $table->unsignedInteger('max_uses')->nullable(); // null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('max_uses_per_user')->default(1);
            $table->foreignId('applicable_tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete(); // null = all
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('coupons'); }
};
