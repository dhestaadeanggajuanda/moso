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
        Schema::create('e_sakip', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('document_type', 32);
            $table->string('document_title');
            // Disimpan di disk s3_private; diunduh lewat route aplikasi (temporaryUrl).
            $table->string('file_path_s3');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_published', 'year', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_sakip');
    }
};
