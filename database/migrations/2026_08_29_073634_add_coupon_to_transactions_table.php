<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('tier_id')->constrained('coupons')->nullOnDelete();
            $table->unsignedInteger('discount_amount')->default(0)->after('amount');
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->string('proof_original_name')->nullable()->after('proof_path');
        });
    }
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['discount_amount','coupon_code','proof_original_name']);
        });
    }
};
