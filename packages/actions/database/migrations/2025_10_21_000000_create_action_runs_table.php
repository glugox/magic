<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_runs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('status')->default('pending'); // pending, running, done, failed
            $table->unsignedInteger('progress')->default(0);
            $table->string('message')->nullable();
            $table->json('params')->nullable();
            $table->json('targets')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_runs');
    }
};
