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
        Schema::create('department_user', function (Blueprint $table) {

            $table->uuid('department_id');
            $table->foreign('department_id')->references('id')->on('departments');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->primary(['department_id', 'user_id']);
            $table->unique(['department_id', 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_user');
    }
};
