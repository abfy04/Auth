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
        Schema::create('sessions', function (Blueprint $table) {
            
            $table->uuid('id')->primary();

            $table->foreignUuid('account_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
             $table->timestamp('revoked_at')->nullable();

            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index('account_id');
            $table->index(['account_id','revoked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
