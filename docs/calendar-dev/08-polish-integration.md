# Adım 08: Polish, Seeder & Model Entegrasyonu

## Hedef
Dummy hardcoded eventleri kaldırıp gerçek `Appointment` model'inden veri çekmek. Fake data seeder ile test verisi oluşturmak. Son rötuşları yapmak.

---

## Mevcut Durum

**Eksik:**
- Hardcoded dummy events (index.blade.php satır 76-104)
- Appointment model ile bağlantı yok
- Event renkleri `AppointmentType` enum'dan gelmiyor

**Mevcut Altyapı:**
- ✅ `Appointment` model (title, start_at, end_at, type, color, company_id)
- ✅ `AppointmentType` enum (Meeting, Call, Focus, Break + color())
- ✅ `AppointmentFactory` (Türkçe title'lar, states)
- ✅ `CalendarService` (calculateEventStyle metodu var)

---

## Yapılacaklar

### 8.1 Appointment Seeder Oluştur

```bash
php artisan make:seeder CalendarDemoSeeder
```

```php
// database/seeders/CalendarDemoSeeder.php
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

        if (!$company || !$user) {
            $this->command->warn('Company veya User bulunamadı. Önce temel seeder çalıştırın.');
            return;
        }

        // Bu hafta için randevular
        $weekStart = now()->startOfWeek();

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
            $startAt = $weekStart->copy()->addDays($apt['day'])->setHour($apt['hour'])->setMinute(0);
            
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

        $this->command->info('✅ ' . count($appointments) . ' demo randevu oluşturuldu.');
    }
}
```

### 8.2 CalendarService'e getAppointments Metodu Ekle

```php
// app/Services/CalendarService.php - Yeni metod

use App\Models\Appointment;

public function getAppointmentsForWeek(Carbon $date, int $companyId): Collection
{
    $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
    $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

    return Appointment::where('company_id', $companyId)
        ->whereBetween('start_at', [$weekStart, $weekEnd])
        ->orderBy('start_at')
        ->get()
        ->map(function ($apt) use ($weekStart) {
            $dayIndex = $apt->start_at->diffInDays($weekStart);
            return [
                'id' => $apt->id,
                'title' => $apt->title,
                'dayIndex' => $dayIndex,
                'startHour' => $apt->start_at->hour,
                'startMinute' => $apt->start_at->minute,
                'durationMinutes' => $apt->start_at->diffInMinutes($apt->end_at),
                'color' => $this->mapTypeToColor($apt->type),
                'type' => $apt->type->value,
            ];
        });
}

private function mapTypeToColor(AppointmentType $type): string
{
    return match ($type) {
        AppointmentType::Meeting => 'primary',
        AppointmentType::Call => 'success',
        AppointmentType::Focus => 'warning',
        AppointmentType::Break => 'zinc',
    };
}
```

### 8.3 Calendar Component with() Metodunu Güncelle

Hardcoded eventleri kaldır, service'ten çek:

```php
// index.blade.php - with() metodu

public function with(CalendarService $service): array
{
    $days = match ($this->view) {
        'month' => $service->getMonthGrid($this->currentDate),
        'week', 'day' => $service->getWeekGrid($this->currentDate),
        default => [],
    };

    $timeSlots = in_array($this->view, ['week', 'day'])
        ? $service->getTimeSlots()
        : [];

    // Gerçek veriden eventler
    $events = [];
    if (in_array($this->view, ['week', 'day'])) {
        $company = auth()->user()?->currentCompany();
        if ($company) {
            $events = $service->getAppointmentsForWeek($this->currentDate, $company->id)->toArray();
        }
    }

    return compact('days', 'timeSlots', 'events');
}
```

### 8.4 Scroll Pozisyonu (Sabah saatlerine)

Week/Day view açılınca 08:00'e scroll:

```html
<!-- index.blade.php - Week View Container -->
<div 
    class="h-[500px] sm:h-[600px] lg:h-[750px] overflow-y-auto"
    x-init="$el.scrollTop = 8 * 60"
>
```

### 8.5 Responsive Varsayılan Görünüm (Opsiyonel)

Mobile'da Gün, desktop'ta Ay:

```php
public function mount(): void
{
    if (empty($this->date)) {
        $this->date = now()->toDateString();
    }
    
    // View boşsa responsive default
    // Not: Bu JS tarafında daha iyi yapılır, şimdilik skip
}
```

---

## Test Adımları

```bash
# 1. Seeder çalıştır
php artisan db:seed --class=CalendarDemoSeeder

# 2. Tarayıcıda test
# /calendar-test rotasına git
# Hafta görünümünde 9 randevu görünmeli
```

---

## Doğrulama

| # | Test | Beklenen |
|---|------|----------|
| 1 | Seeder çalıştır | "9 demo randevu oluşturuldu" |
| 2 | Week view aç | Randevular doğru günlerde |
| 3 | Event renkleri | Meeting=mavi, Call=yeşil, Focus=sarı |
| 4 | Scroll pozisyonu | Sayfa 08:00 civarında açılsın |
| 5 | Day view | O günün randevuları görünsün |
| 6 | Farklı haftaya git | Boş görünsün (bu haftaya seed ettik) |

---

## Çıktı
✅ Gerçek model verisiyle çalışan takvim
✅ Test için demo data seeder
