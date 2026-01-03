# Faz 5: Appointment CRUD

**Süre:** 3-4 gün  
**Önkoşul:** Faz 4 (Calendar UI)  
**Çıktı:** Randevu oluşturma, düzenleme, silme, sürükle-bırak

---

## Amaç

Class-based Volt, Spatie Data DTO ve Action classes ile randevu CRUD operasyonlarını eklemek.

---

## Form Alanları (AppointmentData DTO'dan)

| Alan | Tip | Zorunlu | Validasyon |
|------|-----|---------|------------|
| title | text | ✓ | max:100 |
| type | select | ✓ | enum:AppointmentType |
| start_at | datetime | ✓ | date, after:now |
| end_at | datetime | ✓ | date, after:start_at |
| all_day | checkbox | | boolean |
| location | text | | max:255, nullable |
| description | textarea | | nullable |
| color | color picker | | nullable, hex color |
| attendees | multi-select | | array of user_id/contact_id |

---

## Görevler

### 5.1 AppointmentData DTO Güncelleme

- [ ] `app/Data/AppointmentData.php` genişlet:
  ```php
  use Spatie\LaravelData\Data;
  use Spatie\LaravelData\Concerns\WireableData;
  use Spatie\LaravelData\Attributes\Validation\Required;
  use Spatie\LaravelData\Attributes\Validation\Max;
  use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
  
  class AppointmentData extends Data implements Wireable
  {
      use WireableData;
      
      public function __construct(
          #[Required, Max(100)]
          public string $title,
          
          #[Required]
          public AppointmentType $type,
          
          #[Required]
          public Carbon $start_at,
          
          #[Required, AfterOrEqual('start_at')]
          public Carbon $end_at,
          
          public bool $all_day = false,
          
          #[Max(255)]
          public ?string $location = null,
          
          public ?string $description = null,
          
          public ?string $color = null,
          
          /** @var array<int> */
          public array $attendee_user_ids = [],
          
          /** @var array<int> */
          public array $attendee_contact_ids = [],
      ) {}
      
      public static function rules(): array
      {
          return [
              'end_at' => ['required', 'date', 'after:start_at'],
          ];
      }
  }
  ```

### 5.2 Action Classes

- [ ] `app/Actions/Appointments/CreateAppointmentAction.php`:
  ```php
  class CreateAppointmentAction
  {
      public function execute(AppointmentData $data, User $user): Appointment
      {
          return DB::transaction(function () use ($data, $user) {
              $appointment = Appointment::create([
                  'company_id' => $user->company_id,
                  'title' => $data->title,
                  'type' => $data->type,
                  'start_at' => $data->start_at,
                  'end_at' => $data->end_at,
                  'all_day' => $data->all_day,
                  'location' => $data->location,
                  'description' => $data->description,
                  'color' => $data->color,
                  'created_by' => $user->id,
              ]);
              
              $this->syncAttendees($appointment, $data);
              
              return $appointment;
          });
      }
      
      private function syncAttendees(Appointment $appointment, AppointmentData $data): void
      {
          foreach ($data->attendee_user_ids as $userId) {
              $appointment->attendees()->create([
                  'user_id' => $userId,
                  'status' => AttendeeStatus::Pending,
              ]);
          }
          
          foreach ($data->attendee_contact_ids as $contactId) {
              $appointment->attendees()->create([
                  'contact_id' => $contactId,
                  'status' => AttendeeStatus::Pending,
              ]);
          }
      }
  }
  ```

- [ ] `app/Actions/Appointments/UpdateAppointmentAction.php`
- [ ] `app/Actions/Appointments/DeleteAppointmentAction.php`
- [ ] `app/Actions/Appointments/RescheduleAppointmentAction.php`

### 5.3 Create Form Component (Class-based Volt)

- [ ] `resources/views/livewire/calendar/appointment-form.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\On;
  use App\Data\AppointmentData;
  use App\Actions\Appointments\CreateAppointmentAction;
  use App\Actions\Appointments\UpdateAppointmentAction;
  use App\Enums\AppointmentType;
  
  new class extends Component {
      public ?Appointment $appointment = null;
      
      // Form state
      public string $title = '';
      public string $type = 'meeting';
      public string $start_at = '';
      public string $end_at = '';
      public bool $all_day = false;
      public ?string $location = null;
      public ?string $description = null;
      public ?string $color = null;
      public array $attendee_user_ids = [];
      public array $attendee_contact_ids = [];
      
      // Search state
      public string $attendeeSearch = '';
      
      public function mount(?int $appointmentId = null, ?string $prefillDate = null): void
      {
          if ($appointmentId) {
              $this->appointment = Appointment::with('attendees')->findOrFail($appointmentId);
              $this->fill($this->appointment->toArray());
              $this->attendee_user_ids = $this->appointment->attendees
                  ->whereNotNull('user_id')->pluck('user_id')->toArray();
              $this->attendee_contact_ids = $this->appointment->attendees
                  ->whereNotNull('contact_id')->pluck('contact_id')->toArray();
          }
          
          if ($prefillDate) {
              $this->start_at = $prefillDate . 'T09:00';
              $this->end_at = $prefillDate . 'T10:00';
          }
      }
      
      public function save(
          CreateAppointmentAction $createAction,
          UpdateAppointmentAction $updateAction
      ): void {
          $data = AppointmentData::validateAndCreate([
              'title' => $this->title,
              'type' => AppointmentType::from($this->type),
              'start_at' => Carbon::parse($this->start_at),
              'end_at' => Carbon::parse($this->end_at),
              'all_day' => $this->all_day,
              'location' => $this->location,
              'description' => $this->description,
              'color' => $this->color,
              'attendee_user_ids' => $this->attendee_user_ids,
              'attendee_contact_ids' => $this->attendee_contact_ids,
          ]);
          
          if ($this->appointment) {
              $updateAction->execute($this->appointment, $data);
              $this->dispatch('appointment-updated');
          } else {
              $createAction->execute($data, auth()->user());
              $this->dispatch('appointment-created');
          }
          
          $this->dispatch('calendar-refresh');
          $this->dispatch('close-modal');
      }
      
      #[Computed]
      public function searchResults(): Collection
      {
          if (strlen($this->attendeeSearch) < 2) {
              return collect();
          }
          
          $users = User::where('company_id', auth()->user()->company_id)
              ->where('name', 'ilike', "%{$this->attendeeSearch}%")
              ->limit(5)->get()
              ->map(fn ($u) => ['type' => 'user', 'id' => $u->id, 'name' => $u->name, 'avatar' => $u->avatar]);
          
          $contacts = Contact::where('company_id', auth()->user()->company_id)
              ->where(fn ($q) => $q->where('first_name', 'ilike', "%{$this->attendeeSearch}%")
                  ->orWhere('last_name', 'ilike', "%{$this->attendeeSearch}%"))
              ->limit(5)->get()
              ->map(fn ($c) => ['type' => 'contact', 'id' => $c->id, 'name' => $c->full_name, 'avatar' => null]);
          
          return $users->concat($contacts);
      }
      
      public function addAttendee(string $type, int $id): void
      {
          if ($type === 'user' && !in_array($id, $this->attendee_user_ids)) {
              $this->attendee_user_ids[] = $id;
          } elseif ($type === 'contact' && !in_array($id, $this->attendee_contact_ids)) {
              $this->attendee_contact_ids[] = $id;
          }
          $this->attendeeSearch = '';
      }
      
      public function removeAttendee(string $type, int $id): void
      {
          if ($type === 'user') {
              $this->attendee_user_ids = array_diff($this->attendee_user_ids, [$id]);
          } else {
              $this->attendee_contact_ids = array_diff($this->attendee_contact_ids, [$id]);
          }
      }
  }; ?>
  
  <div class="p-6 space-y-6">
      <flux:heading size="lg">
          {{ $appointment ? 'Randevuyu Düzenle' : 'Yeni Randevu' }}
      </flux:heading>
      
      <form wire:submit="save" class="space-y-4">
          <!-- Title -->
          <flux:input 
              wire:model.blur="title" 
              label="Başlık" 
              placeholder="Toplantı başlığı..."
              required 
          />
          
          <!-- Type -->
          <flux:select wire:model="type" label="Tür" required>
              <option value="meeting">Toplantı</option>
              <option value="call">Telefon</option>
              <option value="focus">Odaklanma</option>
              <option value="break">Mola</option>
          </flux:select>
          
          <!-- Date/Time -->
          <div class="grid grid-cols-2 gap-4">
              <flux:input 
                  type="datetime-local" 
                  wire:model.blur="start_at" 
                  label="Başlangıç" 
                  required 
              />
              <flux:input 
                  type="datetime-local" 
                  wire:model.blur="end_at" 
                  label="Bitiş" 
                  required 
              />
          </div>
          
          <!-- All Day -->
          <flux:checkbox wire:model.live="all_day" label="Tüm gün" />
          
          <!-- Location -->
          <flux:input 
              wire:model.blur="location" 
              label="Konum" 
              placeholder="Adres veya Zoom linki..." 
          />
          
          <!-- Description -->
          <flux:textarea 
              wire:model.blur="description" 
              label="Açıklama" 
              rows="3" 
          />
          
          <!-- Attendees Search -->
          <div>
              <flux:input 
                  wire:model.live.debounce.300ms="attendeeSearch" 
                  label="Katılımcı Ekle" 
                  placeholder="İsim ara..." 
                  icon="user-plus"
              />
              
              @if($this->searchResults->isNotEmpty())
              <div class="mt-2 border rounded-lg divide-y dark:border-zinc-700">
                  @foreach($this->searchResults as $result)
                  <button 
                      type="button"
                      wire:click="addAttendee('{{ $result['type'] }}', {{ $result['id'] }})"
                      class="w-full p-2 flex items-center gap-2 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                  >
                      <flux:avatar size="sm" :src="$result['avatar']" />
                      <span>{{ $result['name'] }}</span>
                      <flux:badge size="sm">{{ $result['type'] === 'user' ? 'Kullanıcı' : 'Kişi' }}</flux:badge>
                  </button>
                  @endforeach
              </div>
              @endif
              
              <!-- Selected Attendees -->
              <div class="mt-2 flex flex-wrap gap-2">
                  @foreach($attendee_user_ids as $userId)
                      @php $user = \App\Models\User::find($userId) @endphp
                      <flux:badge dismissable wire:click="removeAttendee('user', {{ $userId }})">
                          {{ $user?->name }}
                      </flux:badge>
                  @endforeach
                  @foreach($attendee_contact_ids as $contactId)
                      @php $contact = \App\Models\Contact::find($contactId) @endphp
                      <flux:badge dismissable wire:click="removeAttendee('contact', {{ $contactId }})">
                          {{ $contact?->full_name }}
                      </flux:badge>
                  @endforeach
              </div>
          </div>
          
          <!-- Actions -->
          <div class="flex justify-end gap-2 pt-4">
              <flux:button variant="ghost" x-on:click="$flux.close()">
                  İptal
              </flux:button>
              <flux:button type="submit" variant="primary">
                  {{ $appointment ? 'Güncelle' : 'Oluştur' }}
              </flux:button>
          </div>
      </form>
  </div>
  ```

### 5.4 Delete Confirmation

- [ ] `resources/views/livewire/calendar/delete-appointment.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use App\Actions\Appointments\DeleteAppointmentAction;
  
  new class extends Component {
      public Appointment $appointment;
      
      public function delete(DeleteAppointmentAction $action): void
      {
          $action->execute($this->appointment);
          
          $this->dispatch('appointment-deleted');
          $this->dispatch('calendar-refresh');
          $this->dispatch('close-modal');
      }
  }; ?>
  
  <div class="p-6">
      <flux:heading size="lg">Randevuyu Sil</flux:heading>
      
      <flux:text class="mt-4">
          "{{ $appointment->title }}" randevusunu silmek istediğinize emin misiniz?
      </flux:text>
      
      <div class="flex justify-end gap-2 mt-6">
          <flux:button variant="ghost" x-on:click="$flux.close()">
              İptal
          </flux:button>
          <flux:button variant="danger" wire:click="delete">
              Sil
          </flux:button>
      </div>
  </div>
  ```

### 5.5 API Endpoints Güncelleme

- [ ] `app/Http/Controllers/Api/AppointmentController.php` genişlet:
  ```php
  class AppointmentController extends Controller
  {
      public function __construct()
      {
          $this->authorizeResource(Appointment::class);
      }
      
      // index() - Faz 4'te oluşturuldu
      
      public function store(Request $request, CreateAppointmentAction $action): JsonResponse
      {
          $data = AppointmentData::validateAndCreate($request->all());
          $appointment = $action->execute($data, $request->user());
          
          return response()->json($appointment, 201);
      }
      
      public function update(Request $request, Appointment $appointment, UpdateAppointmentAction $action): JsonResponse
      {
          $data = AppointmentData::validateAndCreate($request->all());
          $action->execute($appointment, $data);
          
          return response()->json($appointment->fresh());
      }
      
      public function destroy(Appointment $appointment, DeleteAppointmentAction $action): JsonResponse
      {
          $action->execute($appointment);
          
          return response()->json(null, 204);
      }
      
      public function reschedule(Request $request, Appointment $appointment, RescheduleAppointmentAction $action): JsonResponse
      {
          $action->execute(
              $appointment,
              Carbon::parse($request->start),
              Carbon::parse($request->end)
          );
          
          return response()->json($appointment->fresh());
      }
  }
  ```

- [ ] `routes/api/v1/appointments.php` güncelle:
  ```php
  <?php
  
  use App\Http\Controllers\Api\V1\AppointmentController;
  use Illuminate\Support\Facades\Route;
  
  Route::middleware('auth:sanctum')->group(function () {
      Route::apiResource('appointments', AppointmentController::class);
      Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule']);
  });
  ```

> **Not:** Controller namespace `App\Http\Controllers\Api\V1`

### 5.6 FullCalendar Drag & Drop

- [ ] Faz 4'teki `calendar.js` güncelle:
  ```javascript
  window.initCalendar = function(el, options) {
      return new Calendar(el, {
          // ... önceki config
          editable: true, // Aktif et
          eventDrop: (info) => {
              // Reschedule API call
              fetch(`/api/appointments/${info.event.id}/reschedule`, {
                  method: 'PATCH',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                  },
                  body: JSON.stringify({
                      start: info.event.start.toISOString(),
                      end: info.event.end?.toISOString() || info.event.start.toISOString()
                  })
              });
          },
          eventResize: (info) => {
              // Aynı reschedule logic
          }
      });
  };
  ```

### 5.7 AppointmentPolicy

- [ ] `app/Policies/AppointmentPolicy.php`:
  ```php
  class AppointmentPolicy
  {
      public function viewAny(User $user): bool
      {
          return true;
      }
      
      public function view(User $user, Appointment $appointment): bool
      {
          // Admin/Manager: Tüm şirket randevuları
          if ($user->hasAnyRole(['admin', 'manager'])) {
              return $appointment->company_id === $user->company_id;
          }
          
          // Staff: Sadece katıldıkları
          return $appointment->attendees()->where('user_id', $user->id)->exists()
              || $appointment->created_by === $user->id;
      }
      
      public function create(User $user): bool
      {
          return true;
      }
      
      public function update(User $user, Appointment $appointment): bool
      {
          if ($user->hasRole('admin')) {
              return $appointment->company_id === $user->company_id;
          }
          
          return $appointment->created_by === $user->id;
      }
      
      public function delete(User $user, Appointment $appointment): bool
      {
          return $this->update($user, $appointment);
      }
  }
  ```

---

## Doğrulama

```bash
php artisan test --filter=Appointment
```

Manuel test:
1. Takvimde boş güne tıkla → Form modal açılsın
2. Randevu oluştur → Takvimde görünsün
3. Randevuya tıkla → Detay modal
4. "Düzenle" → Form tekrar açılsın
5. Değişiklik yap → Kaydedilsin
6. "Sil" → Confirmation → Silinsin
7. Sürükle-bırak → Tarih güncellensin
8. Staff kullanıcı başkasının randevusunu düzenleyemesin

---

## Dosya Listesi

```
app/
├── Actions/Appointments/
│   ├── CreateAppointmentAction.php
│   ├── UpdateAppointmentAction.php
│   ├── DeleteAppointmentAction.php
│   └── RescheduleAppointmentAction.php
├── Data/
│   └── AppointmentData.php (güncellendi)
├── Http/Controllers/Api/
│   └── AppointmentController.php (güncellendi)
├── Policies/
│   └── AppointmentPolicy.php
└── Providers/
    └── AuthServiceProvider.php (policy kaydı)

resources/views/livewire/calendar/
├── appointment-form.blade.php
├── appointment-detail.blade.php (güncellendi)
└── delete-appointment.blade.php

resources/js/
└── calendar.js (güncellendi - editable)

routes/
└── api.php (güncellendi)
```

---

## Notlar

- **wire:model.blur** form input'larında (performans)
- **wire:model.live.debounce** sadece arama input'unda
- **DTO validasyon** - Volt'ta duplicate validasyon yok
- **Action classes** ile iş mantığı izole
- Recurrence (tekrarlayan) Post-MVP
- Zoom/Meet link oluşturma Post-MVP
