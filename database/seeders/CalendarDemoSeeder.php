<?php

namespace Database\Seeders;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CalendarDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $user = User::first();

        if (! $company || ! $user) {
            $this->command->warn('Company veya User bulunamadı. Önce temel seeder çalıştırın.');

            return;
        }

        // Bu hafta için randevular
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $appointments = [
            // Pazartesi
            ['title' => 'Sprint Planning', 'day' => 0, 'hour' => 9, 'duration' => 90, 'type' => AppointmentType::Meeting],
            ['title' => 'Müşteri Araması', 'day' => 0, 'hour' => 14, 'duration' => 30, 'type' => AppointmentType::Call],
            // Salı
            ['title' => '1-1 Görüşme', 'day' => 1, 'hour' => 10, 'duration' => 60, 'type' => AppointmentType::Meeting],
            ['title' => 'Deep Work', 'day' => 1, 'hour' => 14, 'duration' => 120, 'type' => AppointmentType::Focus],
            // Çarşamba
            ['title' => 'Demo Sunumu', 'day' => 2, 'hour' => 11, 'duration' => 60, 'type' => AppointmentType::Meeting],
            ['title' => 'Takım Toplantısı', 'day' => 2, 'hour' => 15, 'duration' => 45, 'type' => AppointmentType::Meeting],
            // Perşembe
            ['title' => 'Partner Call', 'day' => 3, 'hour' => 9, 'duration' => 45, 'type' => AppointmentType::Call],
            ['title' => 'Öğle Molası', 'day' => 3, 'hour' => 12, 'duration' => 60, 'type' => AppointmentType::Break],
            // Cuma
            ['title' => 'Weekly Retro', 'day' => 4, 'hour' => 16, 'duration' => 60, 'type' => AppointmentType::Meeting],
        ];

        foreach ($appointments as $apt) {
            $startAt = $weekStart->copy()->addDays($apt['day'])->setHour($apt['hour'])->setMinute(0)->setSecond(0);

            Appointment::create([
                'company_id' => $company->id,
                'created_by' => $user->id,
                'title' => $apt['title'],
                'type' => $apt['type'],
                'start_at' => $startAt,
                'end_at' => $startAt->copy()->addMinutes($apt['duration']),
                'all_day' => false,
                'color' => $apt['type']->color(),
            ]);
        }

        $this->command->info('✅ '.count($appointments).' demo randevu oluşturuldu.');
    }
}
