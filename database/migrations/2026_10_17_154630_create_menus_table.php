<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menu bertingkat memakai adjacency list (parent_id). Tree dirakit di PHP
     * karena jumlah item menu kecil, jadi tidak perlu paket nested set.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('location', 32)->default('header');
            $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();
            $table->string('label', 100);
            // Tautan ke halaman internal ATAU URL kustom (salah satu wajib diisi).
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['location', 'parent_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
