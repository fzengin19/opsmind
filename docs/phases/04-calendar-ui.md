# Faz 4: Calendar UI

**Süre:** 4 gün  
**Önkoşul:** Faz 3 (Dashboard Skeleton)  
**Çıktı:** Interaktif takvim arayüzü (sadece görüntüleme, CRUD Faz 5'te)

---

## Amaç

FullCalendar.js v6 ve Class-based Volt ile aylık/haftalık/günlük görünümlü interaktif takvim UI'ı oluşturmak.

---

## Takvim Görünümleri

| Görünüm | View Adı | Açıklama |
|---------|----------|----------|
| **Aylık** | `dayGridMonth` | Grid hücreler, gün bazında randevu sayısı |
| **Haftalık** | `timeGridWeek` | Saat bazlı timeline (7 gün) |
| **Günlük** | `timeGridDay` | Tek gün saat bazlı detay |
| **Ajanda** | `listWeek` | Liste formatı (yaklaşan randevular) |

---

## Görevler

### 4.1 FullCalendar.js Kurulumu

- [ ] NPM paketleri:
  ```bash
  npm install @fullcalendar/core @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/list @fullcalendar/interaction
  ```

- [ ] `resources/js/calendar.js` oluştur:
  ```javascript
  import { Calendar } from '@fullcalendar/core';
  import dayGridPlugin from '@fullcalendar/daygrid';
  import timeGridPlugin from '@fullcalendar/timegrid';
  import listPlugin from '@fullcalendar/list';
  import interactionPlugin from '@fullcalendar/interaction';
  import trLocale from '@fullcalendar/core/locales/tr';
  
  window.initCalendar = function(el, options) {
      return new Calendar(el, {
          plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
          locale: trLocale,
          initialView: options.initialView || 'dayGridMonth',
          headerToolbar: false, // Custom toolbar kullanacağız
          events: options.eventsUrl,
          editable: false, // Faz 5'te aktif edilecek
          selectable: true,
          eventClick: options.onEventClick,
          dateClick: options.onDateClick,
          eventDidMount: (info) => {
              // Tooltip için Alpine.js event dispatch
              info.el.setAttribute('x-tooltip', info.event.title);
          },
          ...options.extra
      });
  };
  ```

- [ ] `resources/js/app.js` import:
  ```javascript
  import './calendar.js';
  ```

- [ ] Vite build test: `npm run build`

### 4.2 Calendar Page Component (Class-based Volt)

- [ ] `resources/views/livewire/calendar/index.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Layout;
  use Livewire\Attributes\On;
  use App\Models\Appointment;
  use App\Data\AppointmentData;
  
  new #[Layout('components.layouts.app')] class extends Component {
      public string $currentView = 'dayGridMonth';
      public ?int $selectedAppointmentId = null;
      
      public function changeView(string $view): void
      {
          $this->currentView = $view;
          $this->dispatch('calendar-view-changed', view: $view);
      }
      
      public function goToToday(): void
      {
          $this->dispatch('calendar-go-today');
      }
      
      public function navigate(string $direction): void
      {
          $this->dispatch('calendar-navigate', direction: $direction);
      }
      
      #[On('appointment-clicked')]
      public function showAppointment(int $id): void
      {
          $this->selectedAppointmentId = $id;
          $this->dispatch('open-modal', name: 'appointment-detail');
      }
      
      #[On('date-clicked')]
      public function prepareNewAppointment(string $date): void
      {
          // Faz 5'te aktif edilecek
          session()->flash('selected_date', $date);
      }
  }; ?>
  
  <div class="flex gap-6">
      <!-- Mini Calendar Sidebar -->
      <aside class="w-64 hidden lg:block">
          <livewire:calendar.mini-calendar />
      </aside>
      
      <!-- Main Calendar -->
      <div class="flex-1">
          <!-- Toolbar -->
          <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                  <flux:button variant="outline" icon="chevron-left" 
                      wire:click="navigate('prev')" />
                  <flux:button variant="outline" wire:click="goToToday">
                      Bugün
                  </flux:button>
                  <flux:button variant="outline" icon="chevron-right" 
                      wire:click="navigate('next')" />
              </div>
              
              <flux:heading size="lg" x-text="calendarTitle">
                  <!-- Alpine.js ile güncellenecek -->
              </flux:heading>
              
              <div class="flex gap-1">
                  <flux:button 
                      :variant="$currentView === 'dayGridMonth' ? 'primary' : 'ghost'"
                      wire:click="changeView('dayGridMonth')">
                      Ay
                  </flux:button>
                  <flux:button 
                      :variant="$currentView === 'timeGridWeek' ? 'primary' : 'ghost'"
                      wire:click="changeView('timeGridWeek')">
                      Hafta
                  </flux:button>
                  <flux:button 
                      :variant="$currentView === 'timeGridDay' ? 'primary' : 'ghost'"
                      wire:click="changeView('timeGridDay')">
                      Gün
                  </flux:button>
                  <flux:button 
                      :variant="$currentView === 'listWeek' ? 'primary' : 'ghost'"
                      wire:click="changeView('listWeek')">
                      Ajanda
                  </flux:button>
              </div>
          </div>
          
          <!-- Calendar Container -->
          <div 
              x-data="calendarComponent()"
              x-init="init()"
              class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-4"
          >
              <div x-ref="calendar" class="min-h-[600px]"></div>
          </div>
      </div>
      
      <!-- Appointment Detail Modal (Placeholder) -->
      <flux:modal name="appointment-detail">
          @if($selectedAppointmentId)
              <livewire:calendar.appointment-detail 
                  :appointment-id="$selectedAppointmentId" 
                  :key="$selectedAppointmentId" 
              />
          @endif
      </flux:modal>
  </div>
  
  @script
  <script>
  Alpine.data('calendarComponent', () => ({
      calendar: null,
      calendarTitle: '',
      
      init() {
          this.calendar = window.initCalendar(this.$refs.calendar, {
              initialView: '{{ $currentView }}',
              eventsUrl: '/api/appointments',
              onEventClick: (info) => {
                  $wire.dispatch('appointment-clicked', { id: info.event.id });
              },
              onDateClick: (info) => {
                  $wire.dispatch('date-clicked', { date: info.dateStr });
              }
          });
          
          this.calendar.render();
          this.updateTitle();
          
          // Livewire event listeners
          $wire.on('calendar-view-changed', ({ view }) => {
              this.calendar.changeView(view);
              this.updateTitle();
          });
          
          $wire.on('calendar-go-today', () => {
              this.calendar.today();
              this.updateTitle();
          });
          
          $wire.on('calendar-navigate', ({ direction }) => {
              direction === 'prev' ? this.calendar.prev() : this.calendar.next();
              this.updateTitle();
          });
      },
      
      updateTitle() {
          this.calendarTitle = this.calendar.view.title;
      }
  }));
  </script>
  @endscript
  ```

### 4.3 Mini Calendar Component

- [ ] `resources/views/livewire/calendar/mini-calendar.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Carbon\Carbon;
  
  new class extends Component {
      public Carbon $currentMonth;
      
      public function mount(): void
      {
          $this->currentMonth = now()->startOfMonth();
      }
      
      public function previousMonth(): void
      {
          $this->currentMonth = $this->currentMonth->subMonth();
      }
      
      public function nextMonth(): void
      {
          $this->currentMonth = $this->currentMonth->addMonth();
      }
      
      public function selectDate(string $date): void
      {
          $this->dispatch('mini-calendar-date-selected', date: $date);
      }
  }; ?>
  
  <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-4">
      <!-- Calendar grid burada -->
  </div>
  ```

### 4.4 Appointment API Endpoint

- [ ] `app/Http/Controllers/Api/AppointmentController.php`:
  ```php
  class AppointmentController extends Controller
  {
      public function index(Request $request): JsonResponse
      {
          $appointments = Appointment::query()
              ->where('company_id', auth()->user()->company_id)
              ->whereBetween('start_at', [
                  Carbon::parse($request->start),
                  Carbon::parse($request->end),
              ])
              ->with('attendees.user', 'attendees.contact')
              ->get();
          
          return response()->json(
              $appointments->map(fn ($apt) => [
                  'id' => $apt->id,
                  'title' => $apt->title,
                  'start' => $apt->start_at->toIso8601String(),
                  'end' => $apt->end_at->toIso8601String(),
                  'allDay' => $apt->all_day,
                  'color' => $this->getTypeColor($apt->type),
                  'extendedProps' => [
                      'type' => $apt->type->value,
                      'location' => $apt->location,
                      'attendeesCount' => $apt->attendees->count(),
                  ],
              ])
          );
      }
      
      private function getTypeColor(AppointmentType $type): string
      {
          return match($type) {
              AppointmentType::Meeting => '#3b82f6', // blue
              AppointmentType::Call => '#10b981',    // green
              AppointmentType::Focus => '#8b5cf6',   // purple
              AppointmentType::Break => '#f59e0b',   // amber
          };
      }
  }
  ```

- [ ] `routes/api/v1/appointments.php` oluştur:
  ```php
  <?php
  
  use App\Http\Controllers\Api\V1\AppointmentController;
  use Illuminate\Support\Facades\Route;
  
  Route::middleware('auth:sanctum')->group(function () {
      Route::get('/appointments', [AppointmentController::class, 'index']);
  });
  ```

- [ ] `app/Http/Controllers/Api/V1/AppointmentController.php` (namespace dikkat!)

### 4.5 Appointment Detail Modal (Placeholder)

- [ ] `resources/views/livewire/calendar/appointment-detail.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use App\Models\Appointment;
  use App\Data\AppointmentData;
  
  new class extends Component {
      public Appointment $appointment;
      
      public function mount(int $appointmentId): void
      {
          $this->appointment = Appointment::with('attendees.user', 'attendees.contact')
              ->findOrFail($appointmentId);
      }
  }; ?>
  
  <div class="p-6">
      <flux:heading size="lg">{{ $appointment->title }}</flux:heading>
      
      <div class="mt-4 space-y-3">
          <div class="flex items-center gap-2">
              <flux:icon name="clock" class="w-5 h-5" />
              <span>{{ $appointment->start_at->format('d M Y, H:i') }}</span>
          </div>
          
          @if($appointment->location)
          <div class="flex items-center gap-2">
              <flux:icon name="map-pin" class="w-5 h-5" />
              <span>{{ $appointment->location }}</span>
          </div>
          @endif
          
          @if($appointment->description)
          <div class="mt-4">
              <flux:text>{{ $appointment->description }}</flux:text>
          </div>
          @endif
      </div>
      
      <div class="mt-6 flex gap-2">
          <flux:button variant="primary" disabled>
              Düzenle (Faz 5)
          </flux:button>
          <flux:button variant="outline" x-on:click="$flux.close()">
              Kapat
          </flux:button>
      </div>
  </div>
  ```

### 4.6 Responsive Design

- [ ] Mobile: Ajanda görünümü varsayılan
- [ ] Tablet: Haftalık görünüm
- [ ] Desktop: Aylık görünüm + mini calendar sidebar
- [ ] Breakpoints:
  ```php
  public function getInitialView(): string
  {
      // JavaScript tarafında algılanacak
      return 'dayGridMonth';
  }
  ```

### 4.7 Dark Mode Styling

- [ ] FullCalendar dark mode CSS:
  ```css
  /* resources/css/app.css */
  .dark .fc {
      --fc-page-bg-color: theme('colors.zinc.800');
      --fc-neutral-bg-color: theme('colors.zinc.700');
      --fc-neutral-text-color: theme('colors.zinc.300');
      --fc-border-color: theme('colors.zinc.600');
  }
  ```

---

## Doğrulama

```bash
npm run build
php artisan serve

# Seed data kontrolü
php artisan tinker
>>> Appointment::factory()->count(10)->create(['company_id' => 1])
```

Manuel test:
1. Takvim sayfasına git → Aylık görünüm
2. Görünüm switcher: Ay → Hafta → Gün → Ajanda
3. Tarih navigasyonu: « » okları, Bugün butonu
4. Demo randevular görünsün (farklı renkler)
5. Randevuya tıkla → Detay modal açılsın
6. Dark mode'da takvim düzgün görünsün
7. Mobile görünümde ajanda default olsun

---

## Dosya Listesi

```
resources/
├── js/
│   ├── app.js                              # Calendar import
│   └── calendar.js                         # FullCalendar wrapper
├── css/
│   └── app.css                             # + FullCalendar dark mode
└── views/livewire/calendar/
    ├── index.blade.php                     # Ana takvim sayfası
    ├── mini-calendar.blade.php             # Sidebar mini takvim
    └── appointment-detail.blade.php        # Detay modal

app/
└── Http/Controllers/Api/
    └── AppointmentController.php           # JSON feed endpoint

routes/
└── api.php                                 # /api/appointments
```

---

## FullCalendar Renk Şeması

| Tip | Renk | Hex |
|-----|------|-----|
| Meeting | Mavi | `#3b82f6` |
| Call | Yeşil | `#10b981` |
| Focus | Mor | `#8b5cf6` |
| Break | Amber | `#f59e0b` |

---

## Notlar

- **CRUD işlemleri Faz 5'te** eklenecek (create, edit, delete)
- **Sürükle-bırak reschedule Faz 5'te** aktif edilecek (`editable: true`)
- Şimdilik **sadece görüntüleme** modu
- `wire:model.blur` pattern kullanılmıyor (takvim read-only)
- Alpine.js + Livewire hybrid kullanımı (FullCalendar kontrolü için)
