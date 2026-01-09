<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 7)->default('#3b82f6');
            $table->string('type', 20)->default('default');
            $table->string('visibility', 20)->default('company_wide');
            $table->boolean('is_default')->default(false);
            $table->string('google_calendar_id')->nullable()->unique();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
