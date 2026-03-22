<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('status', ['active', 'pending', 'blocked','desactivated'])->default('pending');
            $table->timestamp('email_verified_at')->nullable()->default(null);
            $table->timestamps();
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignUuid('blocked_by')->nullable()->constrained('accounts')->nullOnDelete();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
