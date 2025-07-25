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
        Schema::create('approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['approved', 'rejected', 'NA']);
            $table->boolean('hrd_approval')->default(false); //apakah sudah di approve dari sisi hrd
            $table->boolean('chairman_approval')->default(false); //apakah sudah di approve dari sisi stakeholder/direksi
            $table->boolean('is_closed')->default(false); //apakah direksi ataupun HR Manager memiliki revisi
            $table->datetime('approved_at')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
