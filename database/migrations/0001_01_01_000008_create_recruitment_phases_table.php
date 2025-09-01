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
        Schema::create('recruitment_phases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['progress', 'finish', 'rejected']);
            $table->datetime('started_at')->nullable();
            $table->datetime('finish_at')->nullable();
            $table->json('form_data')->nullable();
            $table->timestamps();

            $table->foreignUuid('request_id')->references('id')->on('recruitment_requests');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_phases');
    }
};
