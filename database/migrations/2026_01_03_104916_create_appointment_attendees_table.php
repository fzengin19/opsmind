<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // AttendeeStatus enum
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['appointment_id', 'user_id']);
            $table->unique(['appointment_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_attendees');
    }
};
