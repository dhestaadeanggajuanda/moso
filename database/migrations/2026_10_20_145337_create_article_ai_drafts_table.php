<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draf yang diminta ke asisten AI (moso-agentic). `remote_id` adalah ID draf
     * di agentic; browser hanya mengenal `id` lokal. Tautan ke artikel dipakai
     * saat sinkron memori agar agentic bisa belajar dari koreksi editor.
     */
    public function up(): void
    {
        Schema::create('article_ai_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('remote_id')->unique();
            $table->timestamps();

            $table->index(['article_id', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_ai_drafts');
    }
};
