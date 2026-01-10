# Faz 5: Appointment CRUD

**Süre:** 3-4 gün  
**Önkoşul:** Faz 4.5 (Calendar Entity) ✅  
**Çıktı:** Randevu oluşturma, düzenleme, silme + Çoklu takvim görünümü

---

## Amaç

Livewire Volt, Spatie Data DTO ve Action Classes ile randevu CRUD operasyonlarını eklemek. Tüm takvimler tek bir ajandada, her takvimenin kendi rengiyle görüntülenecek.

---

## Mevcut Durum (Faz 4.5 Çıktısı)

### Hazır Olanlar
- ✅ `Calendar` model (name, color, type, visibility, is_default)
- ✅ `Appointment` model (calendar_id FK dahil)
- ✅ `AppointmentAttendee` model + migration
- ✅ `AttendeeStatus` enum (pending, accepted, declined)
- ✅ `CalendarService` (getAppointmentsForWeek, getAppointmentsForMonth)
- ✅ `calendar/index.blade.php` (read-only görünüm)
- ✅ Şirket oluşturulunca → "Genel Takvim" (CompanyObserver)
- ✅ Kullanıcı şirkete eklenince → "{User.name} Takvimi" (Company::addUser)

### Eksikler
- [ ] `AppointmentData` DTO'da `calendar_id` yok
- [ ] `CalendarService` randevu rengini `type`'a göre veriyor, `calendar.color` olmalı
- [ ] Takvim sidebar (checkbox ile takvim filtreleme) yok
- [ ] Randevu CRUD modal yok
- [ ] Action classes yok

---

## Form Alanları

| Alan | Tip | Zorunlu | Validasyon |
|------|-----|---------|------------|
| calendar_id | select | ✓ | exists:calendars,id |
| title | text | ✓ | max:100 |
| type | select | ✓ | enum:AppointmentType |
| start_at | datetime | ✓ | date |
| end_at | datetime | ✓ | date, after_or_equal:start_at |
| all_day | checkbox | | boolean |
| location | text | | max:255, nullable |
| description | textarea | | nullable |
| attendee_user_ids | multi-select | | array of user_id |

---

## Görevler

### 5.1 AppointmentData DTO Güncelleme

**Dosya:** `app/Data/AppointmentData.php`

```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AppointmentType;
use Carbon\Carbon;
use Livewire\Wireable;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class AppointmentData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public readonly ?int $id,
        
        #[Exists('companies', 'id')]
        public readonly int $company_id,
        
        #[Exists('calendars', 'id')]
        public readonly int $calendar_id,
        
        #[Max(100)]
        public readonly string $title,
        
        public readonly AppointmentType $type,
        
        public readonly Carbon $start_at,
        
        #[AfterOrEqual('start_at')]
        public readonly Carbon $end_at,
        
        public readonly bool $all_day = false,
        
        #[Max(255)]
        public readonly ?string $location = null,
        
        public readonly ?string $description = null,
        
        public readonly ?int $created_by = null,
        
        /** @var array<int>|null */
        public readonly ?array $attendee_user_ids = null,
    ) {}
}
```

---

### 5.2 CalendarService Güncelleme

**Dosya:** `app/Services/CalendarService.php`

```php
// getAppointmentsForWeek() içinde renk düzeltmesi:

return $query->orderBy('start_at')
    ->with('calendar') // ← Eager load ekle
    ->get()
    ->map(function ($apt) use ($weekStart) {
        return [
            'id' => $apt->id,
            'title' => $apt->title,
            'dayIndex' => (int) $weekStart->diffInDays($apt->start_at),
            'startHour' => $apt->start_at->hour,
            'startMinute' => $apt->start_at->minute,
            'durationMinutes' => $apt->start_at->diffInMinutes($apt->end_at),
            'color' => $apt->calendar?->color ?? $apt->type->color(), // ← Düzeltildi
            'type' => $apt->type->value,
            'calendarId' => $apt->calendar_id,
        ];
    });
```

Aynı düzeltme `getAppointmentsForMonth()` için de yapılacak.

---

### 5.3 Action Classes

**Yeni klasör:** `app/Actions/Appointments/`

#### CreateAppointmentAction.php

```php
<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Data\AppointmentData;
use App\Enums\AttendeeStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAppointmentAction
{
    public function execute(AppointmentData $data, User $user): Appointment
    {
        return DB::transaction(function () use ($data, $user) {
            $appointment = Appointment::create([
                'company_id' => $data->company_id,
                'calendar_id' => $data->calendar_id,
                'title' => $data->title,
                'type' => $data->type,
                'start_at' => $data->start_at,
                'end_at' => $data->end_at,
                'all_day' => $data->all_day,
                'location' => $data->location,
                'description' => $data->description,
                'created_by' => $user->id,
            ]);
            
            // Oluşturanı otomatik katılımcı olarak ekle
            $appointment->attendees()->create([
                'user_id' => $user->id,
                'status' => AttendeeStatus::Accepted,
            ]);
            
            // Seçilen diğer kullanıcıları ekle (otomatik onaylı)
            foreach ($data->attendee_user_ids ?? [] as $userId) {
                if ($userId !== $user->id) {
                    $appointment->attendees()->create([
                        'user_id' => $userId,
                        'status' => AttendeeStatus::Accepted,
                    ]);
                }
            }
            
            return $appointment;
        });
    }
}
```

#### UpdateAppointmentAction.php

```php
<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Data\AppointmentData;
use App\Enums\AttendeeStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentAction
{
    public function execute(Appointment $appointment, AppointmentData $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $appointment->update([
                'calendar_id' => $data->calendar_id,
                'title' => $data->title,
                'type' => $data->type,
                'start_at' => $data->start_at,
                'end_at' => $data->end_at,
                'all_day' => $data->all_day,
                'location' => $data->location,
                'description' => $data->description,
            ]);
            
            // Attendees güncelle
            $currentUserIds = $appointment->attendees()
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();
            
            $newUserIds = $data->attendee_user_ids ?? [];
            
            // Kaldırılanları sil
            $toRemove = array_diff($currentUserIds, $newUserIds);
            if (!empty($toRemove)) {
                $appointment->attendees()
                    ->whereIn('user_id', $toRemove)
                    ->delete();
            }
            
            // Yenileri ekle
            $toAdd = array_diff($newUserIds, $currentUserIds);
            foreach ($toAdd as $userId) {
                $appointment->attendees()->create([
                    'user_id' => $userId,
                    'status' => AttendeeStatus::Accepted,
                ]);
            }
            
            return $appointment->fresh();
        });
    }
}
```

#### DeleteAppointmentAction.php

```php
<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Models\Appointment;

class DeleteAppointmentAction
{
    public function execute(Appointment $appointment): void
    {
        // Attendees cascade ile silinir (FK constraint)
        $appointment->delete();
    }
}
```

#### RescheduleAppointmentAction.php

```php
<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Models\Appointment;
use Carbon\Carbon;

class RescheduleAppointmentAction
{
    public function execute(Appointment $appointment, Carbon $start, Carbon $end): Appointment
    {
        $appointment->update([
            'start_at' => $start,
            'end_at' => $end,
        ]);
        
        return $appointment->fresh();
    }
}
```

---

### 5.4 AppointmentPolicy

**Dosya:** `app/Policies/AppointmentPolicy.php`

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentCompany() !== null;
    }
    
    public function view(User $user, Appointment $appointment): bool
    {
        return $appointment->company_id === $user->currentCompany()?->id
            && $appointment->calendar->isAccessibleBy($user);
    }
    
    public function create(User $user): bool
    {
        return $user->currentCompany() !== null;
    }
    
    public function update(User $user, Appointment $appointment): bool
    {
        if ($appointment->company_id !== $user->currentCompany()?->id) {
            return false;
        }
        
        // Owner/admin her randevuyu düzenleyebilir (Gate::before ile bypass)
        // Diğerleri sadece kendi oluşturduğunu
        return $appointment->created_by === $user->id;
    }
    
    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->update($user, $appointment);
    }
}
```

> **Not:** `Gate::before` ile owner rolü tüm policy'leri bypass eder (AppServiceProvider'da tanımlı).

---

### 5.5 Takvim Sidebar (Calendar Picker)

**Dosya:** `resources/views/livewire/calendar/index.blade.php` içine eklenecek

```php
// PHP kısmına ekle:
public array $visibleCalendarIds = [];

public function mount(): void
{
    if (empty($this->date)) {
        $this->date = now()->toDateString();
    }
    
    // Başlangıçta tüm erişilebilir takvimler görünür
    $this->visibleCalendarIds = $this->accessibleCalendars->pluck('id')->toArray();
}

#[Computed]
public function accessibleCalendars()
{
    $user = auth()->user();
    return \App\Models\Calendar::accessibleBy($user)
        ->where('company_id', $user->currentCompany()->id)
        ->get();
}

public function toggleCalendar(int $calendarId): void
{
    if (in_array($calendarId, $this->visibleCalendarIds)) {
        $this->visibleCalendarIds = array_diff($this->visibleCalendarIds, [$calendarId]);
    } else {
        $this->visibleCalendarIds[] = $calendarId;
    }
}
```

```html
<!-- Blade kısmına sidebar ekle -->
<div class="flex gap-6">
    <!-- Sidebar -->
    <div class="w-64 shrink-0 space-y-4">
        <flux:heading size="sm">Takvimlerim</flux:heading>
        
        @foreach($this->accessibleCalendars as $calendar)
            <label class="flex items-center gap-2 cursor-pointer">
                <input 
                    type="checkbox" 
                    wire:click="toggleCalendar({{ $calendar->id }})"
                    @checked(in_array($calendar->id, $visibleCalendarIds))
                    class="rounded"
                >
                <span 
                    class="w-3 h-3 rounded-full" 
                    style="background: {{ $calendar->color }}"
                ></span>
                <span class="text-sm">{{ $calendar->name }}</span>
            </label>
        @endforeach
    </div>
    
    <!-- Ana takvim alanı -->
    <div class="flex-1">
        <!-- Mevcut takvim içeriği -->
    </div>
</div>
```

---

### 5.6 Appointment Form Modal

**Yeni dosya:** `resources/views/livewire/calendar/appointment-form.blade.php`

```php
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Data\AppointmentData;
use App\Actions\Appointments\CreateAppointmentAction;
use App\Actions\Appointments\UpdateAppointmentAction;
use App\Models\Appointment;
use App\Models\Calendar;
use App\Enums\AppointmentType;
use Carbon\Carbon;

new class extends Component {
    public bool $showModal = false;
    public ?Appointment $appointment = null;
    
    // Form fields
    public ?int $calendar_id = null;
    public string $title = '';
    public string $type = 'meeting';
    public string $start_at = '';
    public string $end_at = '';
    public bool $all_day = false;
    public ?string $location = null;
    public ?string $description = null;
    public array $attendee_user_ids = [];
    
    protected function rules(): array
    {
        return [
            'calendar_id' => 'required|exists:calendars,id',
            'title' => 'required|string|max:100',
            'type' => 'required|in:meeting,call,focus,break',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
            'all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'attendee_user_ids' => 'array',
        ];
    }
    
    #[On('open-appointment-form')]
    public function open(?int $appointmentId = null, ?string $prefillDate = null): void
    {
        $this->resetForm();
        $this->showModal = true;
        
        if ($appointmentId) {
            $this->appointment = Appointment::with('attendees')->findOrFail($appointmentId);
            $this->fill([
                'calendar_id' => $this->appointment->calendar_id,
                'title' => $this->appointment->title,
                'type' => $this->appointment->type->value,
                'start_at' => $this->appointment->start_at->format('Y-m-d\TH:i'),
                'end_at' => $this->appointment->end_at->format('Y-m-d\TH:i'),
                'all_day' => $this->appointment->all_day,
                'location' => $this->appointment->location,
                'description' => $this->appointment->description,
            ]);
            $this->attendee_user_ids = $this->appointment->attendees
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();
        } else {
            $company = auth()->user()->currentCompany();
            $this->calendar_id = $company->defaultCalendar()->id;
            
            if ($prefillDate) {
                $this->start_at = $prefillDate . 'T09:00';
                $this->end_at = $prefillDate . 'T10:00';
            }
        }
    }
    
    public function save(
        CreateAppointmentAction $createAction,
        UpdateAppointmentAction $updateAction
    ): void {
        $this->validate();
        
        $user = auth()->user();
        $company = $user->currentCompany();
        
        $data = new AppointmentData(
            id: $this->appointment?->id,
            company_id: $company->id,
            calendar_id: $this->calendar_id,
            title: $this->title,
            type: AppointmentType::from($this->type),
            start_at: Carbon::parse($this->start_at),
            end_at: Carbon::parse($this->end_at),
            all_day: $this->all_day,
            location: $this->location,
            description: $this->description,
            attendee_user_ids: $this->attendee_user_ids,
        );
        
        if ($this->appointment) {
            $this->authorize('update', $this->appointment);
            $updateAction->execute($this->appointment, $data);
        } else {
            $this->authorize('create', Appointment::class);
            $createAction->execute($data, $user);
        }
        
        $this->showModal = false;
        $this->dispatch('calendar-refresh');
    }
    
    public function resetForm(): void
    {
        $this->reset(['appointment', 'calendar_id', 'title', 'type', 'start_at', 
            'end_at', 'all_day', 'location', 'description', 'attendee_user_ids']);
        $this->type = 'meeting';
    }
    
    #[Computed]
    public function availableCalendars()
    {
        $user = auth()->user();
        return Calendar::accessibleBy($user)
            ->where('company_id', $user->currentCompany()->id)
            ->get();
    }
    
    #[Computed]
    public function companyUsers()
    {
        return auth()->user()->currentCompany()->users;
    }
}; ?>

<flux:modal wire:model="showModal" class="max-w-2xl">
    <div class="p-6 space-y-6">
        <flux:heading size="lg">
            {{ $appointment ? __('calendar.edit_appointment') : __('calendar.new_appointment') }}
        </flux:heading>
        
        <form wire:submit="save" class="space-y-4">
            <!-- Takvim Seçimi -->
            <flux:select wire:model="calendar_id" label="{{ __('calendar.calendar') }}" required>
                @foreach($this->availableCalendars as $calendar)
                    <option value="{{ $calendar->id }}">{{ $calendar->name }}</option>
                @endforeach
            </flux:select>
            
            <!-- Başlık -->
            <flux:input 
                wire:model.blur="title" 
                label="{{ __('calendar.appointment_title') }}" 
                required 
            />
            
            <!-- Tür -->
            <flux:select wire:model="type" label="{{ __('calendar.appointment_type') }}" required>
                @foreach(\App\Enums\AppointmentType::cases() as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </flux:select>
            
            <!-- Tarih/Saat -->
            <div class="grid grid-cols-2 gap-4">
                <flux:input 
                    type="datetime-local" 
                    wire:model.blur="start_at" 
                    label="{{ __('calendar.start') }}" 
                    required 
                />
                <flux:input 
                    type="datetime-local" 
                    wire:model.blur="end_at" 
                    label="{{ __('calendar.end') }}" 
                    required 
                />
            </div>
            
            <!-- Tüm gün -->
            <flux:checkbox wire:model.live="all_day" label="{{ __('calendar.all_day') }}" />
            
            <!-- Konum -->
            <flux:input wire:model.blur="location" label="{{ __('calendar.location') }}" />
            
            <!-- Katılımcılar -->
            <div>
                <label class="block text-sm font-medium mb-2">{{ __('calendar.attendees') }}</label>
                <div class="flex flex-wrap gap-2 p-3 border rounded-lg dark:border-zinc-700">
                    @foreach($this->companyUsers as $user)
                        <label class="flex items-center gap-2 px-3 py-1 rounded-full cursor-pointer
                            {{ in_array($user->id, $attendee_user_ids) 
                                ? 'bg-primary-100 dark:bg-primary-900/30' 
                                : 'bg-zinc-100 dark:bg-zinc-800' }}">
                            <input 
                                type="checkbox" 
                                value="{{ $user->id }}"
                                wire:model="attendee_user_ids"
                                class="sr-only"
                            >
                            <span class="text-sm">{{ $user->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            
            <!-- Açıklama -->
            <flux:textarea wire:model.blur="description" label="{{ __('calendar.description') }}" rows="3" />
            
            <!-- Butonlar -->
            <div class="flex justify-end gap-2 pt-4">
                <flux:button variant="ghost" wire:click="$set('showModal', false)">
                    {{ __('common.cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $appointment ? __('common.update') : __('common.create') }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
```

---

### 5.7 Silme Onay Modal

**Yeni dosya:** `resources/views/livewire/calendar/delete-confirmation.blade.php`

```php
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Actions\Appointments\DeleteAppointmentAction;
use App\Models\Appointment;

new class extends Component {
    public bool $showModal = false;
    public ?Appointment $appointment = null;
    
    #[On('confirm-delete-appointment')]
    public function open(int $appointmentId): void
    {
        $this->appointment = Appointment::findOrFail($appointmentId);
        $this->authorize('delete', $this->appointment);
        $this->showModal = true;
    }
    
    public function delete(DeleteAppointmentAction $action): void
    {
        $action->execute($this->appointment);
        $this->showModal = false;
        $this->dispatch('calendar-refresh');
    }
}; ?>

<flux:modal wire:model="showModal" class="max-w-md">
    <div class="p-6">
        <flux:heading size="lg">{{ __('calendar.delete_appointment') }}</flux:heading>
        
        <flux:text class="mt-4">
            "{{ $appointment?->title }}" {{ __('calendar.delete_confirmation') }}
        </flux:text>
        
        <div class="flex justify-end gap-2 mt-6">
            <flux:button variant="ghost" wire:click="$set('showModal', false)">
                {{ __('common.cancel') }}
            </flux:button>
            <flux:button variant="danger" wire:click="delete">
                {{ __('common.delete') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
```

---

## Doğrulama

```bash
php artisan test --filter=Appointment
```

### Manuel Test Adımları

1. [ ] Takvim sayfasını aç → Sidebar'da takvim listesi görünsün
2. [ ] Checkbox'ları kapat/aç → Randevular filtrelesin
3. [ ] Boş güne tıkla → Form modal açılsın, tarih dolu olsun
4. [ ] Takvim seç, başlık gir, kaydet → Takvimde görünsün (takvim rengiyle)
5. [ ] Randevuya tıkla → Detay görünsün
6. [ ] Düzenle → Form açılsın, veriler dolu
7. [ ] Değiştir, kaydet → Güncellensin
8. [ ] Sil → Onay modal → Silinsin
9. [ ] Başka kullanıcının randevusunu düzenlemeye çalış → İzin verilmesin (policy)
10. [ ] Owner ile dene → Her şeyi yapabilsin

---

## Dosya Listesi

```
app/
├── Actions/Appointments/           ← YENİ
│   ├── CreateAppointmentAction.php
│   ├── UpdateAppointmentAction.php
│   ├── DeleteAppointmentAction.php
│   └── RescheduleAppointmentAction.php
├── Data/
│   └── AppointmentData.php         ← GÜNCELLE
├── Policies/                       ← YENİ
│   └── AppointmentPolicy.php
└── Services/
    └── CalendarService.php         ← GÜNCELLE

resources/views/livewire/calendar/
├── index.blade.php                 ← GÜNCELLE (sidebar + modal integrasyon)
├── appointment-form.blade.php      ← YENİ
└── delete-confirmation.blade.php   ← YENİ

tests/Feature/
├── Actions/AppointmentActionsTest.php  ← YENİ
└── Policies/AppointmentPolicyTest.php  ← YENİ
```

---

## Notlar

- **Attendee RSVP yok (MVP):** Seçilen kullanıcılar otomatik `accepted` olarak eklenir
- **Contact davet yok (MVP):** Sadece şirket kullanıcıları seçilebilir
- **Email notification yok (MVP):** Post-MVP'de eklenecek
- **Takvim rengi:** Randevular ait oldukları takvimin renginde görünür
- **Owner bypass:** `Gate::before` ile owner tüm policy kontrollerini bypass eder
- **Policy auto-discovery:** Laravel 12 ile `app/Policies` klasöründeki policy'ler otomatik kayıt olur
