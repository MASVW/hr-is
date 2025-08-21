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
        Schema::create('recruitment_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['pending', 'approved']);
            $table->string('title');
            $table->uuid('department_id');
            $table->uuid('requested_by')->nullable();
            $table->uuid('approval_id')->nullable();
            $table->json('form_data')->nullable();
            $table->uuid('pic_id')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('requested_by')->references('id')->on('users');
            $table->foreign('approval_id')->references('id')->on('approvals');
            $table->foreign('pic_id')->references('id')->on('users');

            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_requests');
    }
};
