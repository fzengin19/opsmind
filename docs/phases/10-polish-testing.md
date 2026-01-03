# Faz 10: Polish & Testing

**Süre:** 4-5 gün  
**Önkoşul:** Faz 9 (Notifications)  
**Çıktı:** Production-ready MVP

---

## Amaç

Pest v4 testleri, UI polish, performans optimizasyonu, Global Search ve deployment hazırlığı.

---

## Görevler

### 10.1 Dashboard Widgets (Gerçek Data)

- [ ] `resources/views/livewire/dashboard/today-appointments.blade.php` güncelle:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Lazy;
  
  new #[Lazy] class extends Component {
      #[Computed]
      public function appointments(): Collection
      {
          return Appointment::where('company_id', auth()->user()->company_id)
              ->whereDate('start_at', today())
              ->orderBy('start_at')
              ->limit(5)
              ->get();
      }
      
      public function placeholder(): string
      {
          return <<<'HTML'
          <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 shadow-sm animate-pulse">
              <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-1/2 mb-4"></div>
              <div class="space-y-3">
                  <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
                  <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded w-3/4"></div>
              </div>
          </div>
          HTML;
      }
  }; ?>
  ```

- [ ] `assigned-tasks.blade.php` - Bana atanan görevler
- [ ] `recent-activity.blade.php` - Son 10 aktivite

### 10.2 Global Search

- [ ] `resources/views/livewire/layout/global-search.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  
  new class extends Component {
      public string $query = '';
      public bool $isOpen = false;
      
      public function updatedQuery(): void
      {
          $this->isOpen = strlen($this->query) >= 2;
      }
      
      #[Computed]
      public function results(): array
      {
          if (strlen($this->query) < 2) return [];
          
          $companyId = auth()->user()->company_id;
          
          return [
              'appointments' => Appointment::where('company_id', $companyId)
                  ->where('title', 'ilike', "%{$this->query}%")
                  ->limit(3)->get(),
              
              'contacts' => Contact::where('company_id', $companyId)
                  ->where(fn ($q) => $q
                      ->where('first_name', 'ilike', "%{$this->query}%")
                      ->orWhere('last_name', 'ilike', "%{$this->query}%"))
                  ->limit(3)->get(),
              
              'tasks' => Task::where('company_id', $companyId)
                  ->where('title', 'ilike', "%{$this->query}%")
                  ->limit(3)->get(),
          ];
      }
      
      public function close(): void
      {
          $this->isOpen = false;
          $this->query = '';
      }
  }; ?>
  
  <div class="relative" x-data="{ open: $wire.entangle('isOpen') }">
      <flux:input 
          wire:model.live.debounce.300ms="query"
          placeholder="Ara... (⌘K)"
          icon="magnifying-glass"
          x-on:keydown.escape="$wire.close()"
          x-on:keydown.cmd.k.window.prevent="$el.focus()"
      />
      
      <div 
          x-show="open" 
          x-on:click.outside="$wire.close()"
          class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border dark:border-zinc-700 overflow-hidden z-50"
      >
          @if(!empty($this->results['appointments']))
          <div class="p-2">
              <div class="text-xs text-zinc-500 px-2 mb-1">Randevular</div>
              @foreach($this->results['appointments'] as $apt)
              <a href="{{ route('calendar') }}" class="block px-2 py-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700">
                  <flux:icon name="calendar" class="w-4 h-4 inline mr-2" />
                  {{ $apt->title }}
              </a>
              @endforeach
          </div>
          @endif
          
          @if(!empty($this->results['contacts']))
          <div class="p-2 border-t dark:border-zinc-700">
              <div class="text-xs text-zinc-500 px-2 mb-1">Kişiler</div>
              @foreach($this->results['contacts'] as $contact)
              <a href="{{ route('contacts.show', $contact) }}" class="block px-2 py-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700">
                  <flux:icon name="user" class="w-4 h-4 inline mr-2" />
                  {{ $contact->full_name }}
              </a>
              @endforeach
          </div>
          @endif
          
          @if(!empty($this->results['tasks']))
          <div class="p-2 border-t dark:border-zinc-700">
              <div class="text-xs text-zinc-500 px-2 mb-1">Görevler</div>
              @foreach($this->results['tasks'] as $task)
              <a href="{{ route('tasks') }}" class="block px-2 py-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700">
                  <flux:icon name="check-circle" class="w-4 h-4 inline mr-2" />
                  {{ $task->title }}
              </a>
              @endforeach
          </div>
          @endif
      </div>
  </div>
  ```

### 10.3 UI Polish

- [ ] **Loading states** (`wire:loading`):
  ```blade
  <flux:button type="submit" wire:loading.attr="disabled">
      <span wire:loading.remove>Kaydet</span>
      <span wire:loading>Kaydediliyor...</span>
  </flux:button>
  ```

- [ ] **Empty states** component:
  ```blade
  <!-- resources/views/components/empty-state.blade.php -->
  <div class="text-center py-12">
      <flux:icon :name="$icon" class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-600" />
      <flux:heading size="sm" class="mt-4">{{ $title }}</flux:heading>
      <flux:text class="mt-2 text-zinc-500">{{ $description }}</flux:text>
      @if(isset($action))
          <div class="mt-4">{{ $action }}</div>
      @endif
  </div>
  ```

- [ ] **Toast notifications**:
  ```blade
  <!-- Flux UI toast pattern -->
  <div x-data="{ show: false, message: '' }"
       x-on:toast.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
       x-show="show"
       class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg"
  >
      <span x-text="message"></span>
  </div>
  ```

- [ ] **Error pages**:
  - `resources/views/errors/404.blade.php`
  - `resources/views/errors/500.blade.php`

- [ ] **Türkçe validation mesajları**:
  ```bash
  php artisan lang:publish
  ```
  `lang/tr/validation.php` düzenle

### 10.4 Dark Mode Kontrolü

- [ ] Tüm sayfalar dark mode'da test
- [ ] Contrast ratio (WCAG AA)
- [ ] FullCalendar dark theme CSS
- [ ] Flux UI dark variants

### 10.5 Responsive Kontrolü

| Breakpoint | Test Edilecekler |
|------------|------------------|
| Mobile (375px) | Sidebar collapse, hamburger menu |
| Tablet (768px) | 2-column grid, sidebar visible |
| Desktop (1280px) | Full layout, 3-column widgets |

### 10.6 Pest v4 Test Coverage

#### Feature Tests

- [ ] `tests/Feature/Auth/LoginTest.php`:
  ```php
  test('user can login with correct credentials', function () {
      $user = User::factory()->create();
      
      $this->post('/login', [
          'email' => $user->email,
          'password' => 'password',
      ])->assertRedirect('/dashboard');
      
      $this->assertAuthenticated();
  });
  
  test('user cannot login with wrong password', function () {
      $user = User::factory()->create();
      
      $this->post('/login', [
          'email' => $user->email,
          'password' => 'wrong-password',
      ]);
      
      $this->assertGuest();
  });
  ```

- [ ] `tests/Feature/AppointmentTest.php`:
  ```php
  test('user can create appointment', function () {
      $user = User::factory()->create();
      
      Volt::actingAs($user)
          ->test('calendar.appointment-form')
          ->set('title', 'Test Randevu')
          ->set('type', 'meeting')
          ->set('start_at', now()->addDay()->format('Y-m-d\TH:i'))
          ->set('end_at', now()->addDay()->addHour()->format('Y-m-d\TH:i'))
          ->call('save')
          ->assertHasNoErrors();
      
      expect(Appointment::where('title', 'Test Randevu')->exists())->toBeTrue();
  });
  ```

- [ ] `tests/Feature/ContactTest.php`
- [ ] `tests/Feature/TaskTest.php`
- [ ] `tests/Feature/NotificationTest.php`

#### Unit Tests

- [ ] `tests/Unit/Services/GoogleCalendarServiceTest.php`:
  ```php
  test('it maps google event to appointment data', function () {
      $service = new GoogleCalendarService();
      
      // Mock Google event
      $googleEvent = new Google_Service_Calendar_Event([
          'summary' => 'Test Event',
          'start' => ['dateTime' => '2026-01-03T10:00:00+03:00'],
          'end' => ['dateTime' => '2026-01-03T11:00:00+03:00'],
      ]);
      
      $data = $service->mapGoogleEvent($googleEvent);
      
      expect($data->title)->toBe('Test Event');
      expect($data->all_day)->toBeFalse();
  });
  ```

- [ ] `tests/Unit/Policies/AppointmentPolicyTest.php`
- [ ] `tests/Unit/Policies/TaskPolicyTest.php`

#### Browser Tests (Pest v4)

- [ ] `tests/Browser/KanbanTest.php`:
  ```php
  test('user can drag task between columns', function () {
      $user = User::factory()->create();
      $task = Task::factory()->create([
          'company_id' => $user->company_id,
          'status' => TaskStatus::Todo,
      ]);
      
      $page = visit('/tasks')->actingAs($user);
      
      $page->assertSee($task->title)
          ->drag("[data-task-id='{$task->id}']", "[data-status='in_progress']")
          ->waitForLivewire();
      
      expect($task->fresh()->status)->toBe(TaskStatus::InProgress);
  });
  ```

- [ ] `tests/Browser/CalendarTest.php`

### 10.7 Performance

- [ ] **N+1 Query kontrolü**:
  ```bash
  composer require barryvdh/laravel-debugbar --dev
  ```
  - Sayfa başına query sayısını kontrol et
  - `->with()` eksiklerini düzelt

- [ ] **Database indexes**:
  ```php
  // Migrations'a ekle
  $table->index(['company_id', 'status']);
  $table->index(['company_id', 'start_at']);
  ```

- [ ] **Asset minification**:
  ```bash
  npm run build
  ```

### 10.8 Security

- [ ] CSRF token kontrolleri
- [ ] Authorization policy testleri
- [ ] Rate limiting:
  ```php
  // bootstrap/app.php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->api(append: [
          'throttle:api',
      ]);
  })
  ```
- [ ] Input sanitization (XSS)

### 10.9 Code Quality

- [ ] Laravel Pint:
  ```bash
  vendor/bin/pint
  ```

- [ ] PHPStan (opsiyonel):
  ```bash
  composer require --dev larastan/larastan
  ./vendor/bin/phpstan analyse
  ```

### 10.10 Documentation

- [ ] `README.md` güncelle:
  - Kurulum adımları
  - Gereksinimler
  - Environment variables
  - Development commands

- [ ] `.env.example` güncelle (tüm yeni değişkenler)

---

## Test Komutları

```bash
# Tüm testler
php artisan test

# Coverage raporu
php artisan test --coverage --min=80

# Belirli test dosyası
php artisan test tests/Feature/AppointmentTest.php

# Filtre ile
php artisan test --filter=can_create_appointment

# Browser testleri (Pest v4)
php artisan test tests/Browser

# Parallel testler
php artisan test --parallel
```

---

## Deployment Checklist

### Environment
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` doğru
- [ ] Database credentials güvenli
- [ ] Mail driver configured

### Database
- [ ] `php artisan migrate --force`
- [ ] Database backup strategy

### Assets
- [ ] `npm run build`
- [ ] Public storage link: `php artisan storage:link`

### Caching
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Queue
- [ ] Redis kurulu ve çalışıyor
- [ ] Supervisor config:
  ```ini
  [program:opsmind-worker]
  command=php /path/to/artisan queue:work --sleep=3 --tries=3
  autostart=true
  autorestart=true
  numprocs=2
  ```

### Scheduler
- [ ] Cron job:
  ```bash
  * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
  ```

### SSL
- [ ] HTTPS aktif
- [ ] Force HTTPS middleware

---

## Dosya Listesi

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   ├── RegisterTest.php
│   │   └── PasswordResetTest.php
│   ├── AppointmentTest.php
│   ├── ContactTest.php
│   ├── TaskTest.php
│   └── NotificationTest.php
├── Unit/
│   ├── Services/
│   │   └── GoogleCalendarServiceTest.php
│   ├── Actions/
│   │   ├── CreateAppointmentActionTest.php
│   │   └── ReorderTaskActionTest.php
│   └── Policies/
│       ├── AppointmentPolicyTest.php
│       ├── ContactPolicyTest.php
│       └── TaskPolicyTest.php
└── Browser/
    ├── KanbanTest.php
    └── CalendarTest.php

resources/views/
├── errors/
│   ├── 404.blade.php
│   └── 500.blade.php
├── components/
│   ├── empty-state.blade.php
│   └── toast.blade.php
└── livewire/
    ├── layout/
    │   └── global-search.blade.php
    └── dashboard/
        ├── today-appointments.blade.php (güncelle)
        ├── assigned-tasks.blade.php (güncelle)
        └── recent-activity.blade.php (güncelle)
```

---

## Başarı Kriterleri

| Metrik | Hedef |
|--------|-------|
| Test coverage | ≥80% |
| Lighthouse Performance | ≥85 |
| Page load time | <2s |
| Feature testler | ✓ Yeşil |
| Browser testler | ✓ Yeşil |
| Pint (code style) | ✓ Hata yok |
| Mobile responsive | ✓ |
| Dark mode | ✓ |

---

## MVP Tamamlandı! 🎉

Bu fazın sonunda:
- ✅ Production-ready kod
- ✅ Test edilmiş tüm özellikler
- ✅ Performans optimize
- ✅ Güvenlik kontrol edildi
- ✅ Deployment hazır
- ✅ Global search çalışıyor
- ✅ Dashboard widget'lar gerçek data gösteriyor
- ✅ Beta kullanıcılarla test başlayabilir
