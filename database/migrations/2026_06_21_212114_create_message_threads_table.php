<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 150);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['coach_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
