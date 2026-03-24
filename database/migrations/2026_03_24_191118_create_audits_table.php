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
       Schema::create('audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('target_type')->nullable();
            $table->uuid('target_id')->nullable();
            $table->string('device')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->boolean('success')->default(true);
            $table->json('error')->nullable();
            $table->timestamps();

            $table->index(['account_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['created_at']);
            $table->index(['action']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
