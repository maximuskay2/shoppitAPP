<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->string('participant_type', 16)->index(); // admin, user
            $table->uuid('participant_id');
            $table->string('role', 16)->index(); // admin, driver, customer, vendor
            $table->timestamps();

            $table->unique(['conversation_id', 'participant_type', 'participant_id'], 'conv_part_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
