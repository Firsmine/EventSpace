<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnUpdate();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location');
            $table->dateTime('event_date');
            $table->unsignedInteger('capacity');
            $table->enum('status', ['draft', 'published', 'canceled', 'finished'])->default('draft');
            $table->timestamps();

            $table->index(['status', 'event_date']);
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
