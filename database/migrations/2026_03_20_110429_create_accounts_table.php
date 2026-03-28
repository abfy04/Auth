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
            $table->enum('status', ['active','blocked','inactive'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('email_changed_at')->nullable();
            $table->timestamps();
            $table->timestamp('blocked_at')->nullable();
            $table->string('block_reason')->nullable();
            $table->index('status');
            $table->index('email_verified_at');
            $table->index('blocked_at');
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignUuid('blocked_by')->nullable()->constrained('accounts')->nullOnDelete();
          
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
