<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // ContactType enum
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('company_name', 100)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('tags')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
