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
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();

            $table->foreignUuid('session_id')
                ->constrained('sessions')
                ->cascadeOnDelete();

            $table->string('token_hash')->unique();

            $table->boolean('revoked')->default(false);

            $table->text('user_agent')->nullable();
            $table->ipAddress('ip')->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['account_id', 'session_id']);
            $table->index('session_id');
            $table->index('expires_at');
            $table->index('revoked');            
            $table->index('last_used_at');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
