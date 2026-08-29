<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete();
            $table->string('type')->default('subscription'); // subscription, topup
            $table->unsignedInteger('amount');
            $table->string('payment_method')->default('transfer');
            $table->string('proof_path')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('admin_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'type']);
        });
    }
    public function down(): void { Schema::dropIfExists('transactions'); }
};
