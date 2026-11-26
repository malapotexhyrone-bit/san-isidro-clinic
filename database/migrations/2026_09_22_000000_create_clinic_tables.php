<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('birth_date');
            $table->enum('sex', ['Male', 'Female']);
            $table->string('contact_number', 30);
            $table->text('address');
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_contact_number', 30)->nullable();
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('physicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('specialization');
            $table->string('contact_number', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('physician_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physician_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 Sunday - 6 Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_duration')->default(60);
            $table->boolean('is_available')->default(true);
            $table->string('unavailability_reason')->nullable();
            $table->timestamps();
            $table->unique(['physician_id', 'day_of_week', 'start_time'], 'physician_schedule_unique');
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('physician_id')->constrained()->restrictOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->text('reason');
            $table->enum('status', ['Scheduled', 'Arrived', 'Completed', 'Cancelled', 'Rescheduled', 'No-show', 'Walk-in'])->default('Scheduled');
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['physician_id', 'appointment_date', 'appointment_time'], 'appointment_slot_index');
        });

        Schema::create('appointment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->date('previous_date');
            $table->time('previous_time');
            $table->date('new_date');
            $table->time('new_time');
            $table->string('reason')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_number')->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('physician_id')->constrained()->restrictOnDelete();
            $table->dateTime('arrived_at');
            $table->enum('visit_type', ['Scheduled', 'Walk-in']);
            $table->enum('status', ['Waiting', 'Vital Signs Recorded', 'In Consultation', 'Completed'])->default('Waiting');
            $table->text('reason')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('blood_pressure', 20);
            $table->decimal('temperature', 4, 1);
            $table->unsignedSmallInteger('pulse');
            $table->unsignedSmallInteger('respiratory_rate');
            $table->decimal('weight', 6, 2);
            $table->decimal('height', 6, 2);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('physician_id')->constrained()->restrictOnDelete();
            $table->text('chief_complaint');
            $table->text('assessment');
            $table->text('notes')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('vital_signs');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('appointment_histories');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('physician_schedules');
        Schema::dropIfExists('physicians');
        Schema::dropIfExists('patients');
    }
};
