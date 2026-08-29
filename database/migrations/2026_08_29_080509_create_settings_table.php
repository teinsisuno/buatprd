<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // ai.cost_per_1k, payment.bank_name, etc
            $table->text('value')->nullable(); // encrypted if is_encrypted
            $table->string('group')->default('general'); // general, payment, ai, security
            $table->string('label')->nullable();
            $table->string('type')->default('text'); // text, textarea, select, number, encrypted, json
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('settings'); }
};
