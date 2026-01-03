<?php

declare(strict_types=1);

use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\AppointmentAttendee;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Appointment Model', function () {
    it('can be created with factory', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($appointment)
            ->toBeInstanceOf(Appointment::class)
            ->id->toBeInt()
            ->title->toBeString();
    });

    it('casts type to AppointmentType enum', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->meeting()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($appointment->type)
            ->toBeInstanceOf(AppointmentType::class)
            ->toBe(AppointmentType::Meeting);
    });

    it('casts start_at and end_at to Carbon', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($appointment->start_at)->toBeInstanceOf(Carbon::class);
        expect($appointment->end_at)->toBeInstanceOf(Carbon::class);
    });

    it('casts all_day to boolean', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->allDay()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        expect($appointment->all_day)->toBeTrue();
    });

    it('has attendees relationship', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        AppointmentAttendee::create([
            'appointment_id' => $appointment->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        expect($appointment->fresh()->attendees)->toHaveCount(1);
    });
});
