# Faz 6: Google Calendar Sync

**Süre:** 4 gün  
**Önkoşul:** Faz 5 (Appointment CRUD)  
**Çıktı:** Google Calendar'dan tek yönlü veri çekme (Pull)

---

## Amaç

Google Calendar API entegrasyonu ile kullanıcının mevcut takvim randevularını OpsMind'a çekmek. Action classes ve Service pattern kullanılacak.

---

## MVP Kapsam

| Özellik | MVP | Post-MVP |
|---------|-----|----------|
| Google → OpsMind (pull) | ✓ | |
| OpsMind → Google (push) | | ✓ |
| Real-time webhook | | ✓ |
| Çoklu takvim seçimi | | ✓ |
| Outlook/Apple sync | | ✓ |

---

## OAuth Flow

```
┌─────────────┐     ┌─────────────────┐     ┌──────────────┐
│   Kullanıcı │────▶│  Google OAuth   │────▶│  OpsMind     │
│   "Bağla"   │     │  Consent Screen │     │  Callback    │
└─────────────┘     └─────────────────┘     └──────────────┘
                                                    │
                           ┌────────────────────────┘
                           ▼
              ┌─────────────────────────┐
              │  Token DB'ye kaydet     │
              │  (access + refresh)     │
              └─────────────────────────┘
                           │
                           ▼
              ┌─────────────────────────┐
              │  Cron: Her 5 dk sync    │
              └─────────────────────────┘
```

---

## Görevler

### 6.1 Google Cloud Console Kurulumu

- [ ] Google Cloud Console → Yeni proje oluştur
- [ ] APIs & Services → Google Calendar API enable
- [ ] OAuth consent screen:
  - App name: "OpsMind Calendar Sync"
  - Scopes: `https://www.googleapis.com/auth/calendar.readonly`
  - Test users ekle (development)
- [ ] Credentials → OAuth 2.0 Client ID
- [ ] Authorized redirect URI: `http://localhost/integrations/google-calendar/callback`

### 6.2 Token Storage

- [ ] `database/migrations/xxxx_create_user_google_tokens_table.php`:
  ```php
  Schema::create('user_google_tokens', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->text('access_token');  // Encrypted
      $table->text('refresh_token'); // Encrypted
      $table->timestamp('expires_at');
      $table->string('calendar_id')->default('primary');
      $table->timestamp('last_synced_at')->nullable();
      $table->timestamps();
      
      $table->unique('user_id');
  });
  ```

- [ ] `app/Models/UserGoogleToken.php`:
  ```php
  class UserGoogleToken extends Model
  {
      protected $fillable = [
          'user_id', 'access_token', 'refresh_token', 
          'expires_at', 'calendar_id', 'last_synced_at'
      ];
      
      protected $casts = [
          'expires_at' => 'datetime',
          'last_synced_at' => 'datetime',
          'access_token' => 'encrypted',
          'refresh_token' => 'encrypted',
      ];
      
      public function user(): BelongsTo
      {
          return $this->belongsTo(User::class);
      }
      
      public function isExpired(): bool
      {
          return $this->expires_at->isPast();
      }
  }
  ```

### 6.3 Google Calendar Service

- [ ] `composer require google/apiclient`

- [ ] `app/Services/GoogleCalendarService.php`:
  ```php
  class GoogleCalendarService
  {
      private Google_Client $client;
      
      public function __construct()
      {
          $this->client = new Google_Client();
          $this->client->setClientId(config('services.google_calendar.client_id'));
          $this->client->setClientSecret(config('services.google_calendar.client_secret'));
          $this->client->setRedirectUri(config('services.google_calendar.redirect'));
          $this->client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
          $this->client->setAccessType('offline');
          $this->client->setPrompt('consent');
      }
      
      public function getAuthUrl(): string
      {
          return $this->client->createAuthUrl();
      }
      
      public function handleCallback(string $code): array
      {
          $token = $this->client->fetchAccessTokenWithAuthCode($code);
          
          return [
              'access_token' => $token['access_token'],
              'refresh_token' => $token['refresh_token'] ?? null,
              'expires_at' => now()->addSeconds($token['expires_in']),
          ];
      }
      
      public function refreshTokenIfNeeded(UserGoogleToken $token): void
      {
          if (!$token->isExpired()) {
              return;
          }
          
          $this->client->setAccessToken([
              'refresh_token' => $token->refresh_token,
          ]);
          
          $newToken = $this->client->fetchAccessTokenWithRefreshToken();
          
          $token->update([
              'access_token' => $newToken['access_token'],
              'expires_at' => now()->addSeconds($newToken['expires_in']),
          ]);
      }
      
      public function getEvents(UserGoogleToken $token, Carbon $start, Carbon $end): array
      {
          $this->refreshTokenIfNeeded($token);
          
          $this->client->setAccessToken([
              'access_token' => $token->access_token,
          ]);
          
          $service = new Google_Service_Calendar($this->client);
          
          $events = $service->events->listEvents($token->calendar_id, [
              'timeMin' => $start->toRfc3339String(),
              'timeMax' => $end->toRfc3339String(),
              'singleEvents' => true,
              'orderBy' => 'startTime',
          ]);
          
          return $events->getItems();
      }
  }
  ```

### 6.4 Action Classes

- [ ] `app/Actions/GoogleCalendar/ConnectGoogleCalendarAction.php`:
  ```php
  class ConnectGoogleCalendarAction
  {
      public function execute(User $user, array $tokenData): UserGoogleToken
      {
          return UserGoogleToken::updateOrCreate(
              ['user_id' => $user->id],
              [
                  'access_token' => $tokenData['access_token'],
                  'refresh_token' => $tokenData['refresh_token'],
                  'expires_at' => $tokenData['expires_at'],
              ]
          );
      }
  }
  ```

- [ ] `app/Actions/GoogleCalendar/DisconnectGoogleCalendarAction.php`

- [ ] `app/Actions/GoogleCalendar/SyncGoogleCalendarAction.php`:
  ```php
  class SyncGoogleCalendarAction
  {
      public function __construct(
          private GoogleCalendarService $service,
          private CreateAppointmentAction $createAction,
          private UpdateAppointmentAction $updateAction,
      ) {}
      
      public function execute(User $user): SyncResult
      {
          $token = $user->googleToken;
          if (!$token) {
              throw new \Exception('Google Calendar not connected');
          }
          
          $startDate = now()->subMonth();
          $endDate = now()->addMonths(3);
          
          $googleEvents = $this->service->getEvents($token, $startDate, $endDate);
          
          $created = 0;
          $updated = 0;
          
          foreach ($googleEvents as $googleEvent) {
              $existing = Appointment::where('google_calendar_id', $googleEvent->getId())
                  ->where('company_id', $user->company_id)
                  ->first();
              
              $data = $this->mapGoogleEvent($googleEvent);
              
              if ($existing) {
                  $this->updateAction->execute($existing, $data);
                  $updated++;
              } else {
                  $appointment = $this->createAction->execute($data, $user);
                  $appointment->update(['google_calendar_id' => $googleEvent->getId()]);
                  $created++;
              }
          }
          
          $token->update(['last_synced_at' => now()]);
          
          return new SyncResult($created, $updated);
      }
      
      private function mapGoogleEvent(Google_Service_Calendar_Event $event): AppointmentData
      {
          $isAllDay = $event->getStart()->getDate() !== null;
          
          return new AppointmentData(
              title: $event->getSummary() ?? 'Untitled',
              type: AppointmentType::Meeting,
              start_at: Carbon::parse($isAllDay 
                  ? $event->getStart()->getDate() 
                  : $event->getStart()->getDateTime()),
              end_at: Carbon::parse($isAllDay 
                  ? $event->getEnd()->getDate() 
                  : $event->getEnd()->getDateTime()),
              all_day: $isAllDay,
              location: $event->getLocation(),
              description: $event->getDescription(),
              color: $this->mapColorId($event->getColorId()),
          );
      }
      
      private function mapColorId(?string $colorId): ?string
      {
          // Google color ID → Hex mapping
          return match($colorId) {
              '1' => '#7986cb', // Lavender
              '2' => '#33b679', // Sage
              '3' => '#8e24aa', // Grape
              '4' => '#e67c73', // Flamingo
              '5' => '#f6c026', // Banana
              '6' => '#f5511d', // Tangerine
              '7' => '#039be5', // Peacock
              '8' => '#616161', // Graphite
              '9' => '#3f51b5', // Blueberry
              '10' => '#0b8043', // Basil
              '11' => '#d60000', // Tomato
              default => null,
          };
      }
  }
  ```

### 6.5 Sync Job

- [ ] `app/Jobs/SyncGoogleCalendarJob.php`:
  ```php
  class SyncGoogleCalendarJob implements ShouldQueue
  {
      use Queueable;
      
      public function __construct(
          public User $user
      ) {}
      
      public function handle(SyncGoogleCalendarAction $action): void
      {
          try {
              $result = $action->execute($this->user);
              
              Log::info("Google Calendar sync completed for user {$this->user->id}", [
                  'created' => $result->created,
                  'updated' => $result->updated,
              ]);
          } catch (\Exception $e) {
              Log::error("Google Calendar sync failed for user {$this->user->id}", [
                  'error' => $e->getMessage(),
              ]);
          }
      }
  }
  ```

### 6.6 Scheduler Command

- [ ] `app/Console/Commands/SyncCalendarsCommand.php`:
  ```php
  #[AsCommand(name: 'calendar:sync')]
  class SyncCalendarsCommand extends Command
  {
      public function handle(): void
      {
          $users = User::whereHas('googleToken')->get();
          
          foreach ($users as $user) {
              SyncGoogleCalendarJob::dispatch($user);
          }
          
          $this->info("Dispatched sync jobs for {$users->count()} users");
      }
  }
  ```

- [ ] `bootstrap/app.php` scheduler:
  ```php
  ->withSchedule(function (Schedule $schedule) {
      $schedule->command('calendar:sync')->everyFiveMinutes();
  })
  ```

### 6.7 Settings UI (Class-based Volt)

- [ ] `resources/views/livewire/settings/integrations.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Layout;
  use App\Services\GoogleCalendarService;
  use App\Actions\GoogleCalendar\DisconnectGoogleCalendarAction;
  use App\Actions\GoogleCalendar\SyncGoogleCalendarAction;
  
  new #[Layout('components.layouts.app')] class extends Component {
      public function connect(GoogleCalendarService $service): void
      {
          $this->redirect($service->getAuthUrl());
      }
      
      public function disconnect(DisconnectGoogleCalendarAction $action): void
      {
          $action->execute(auth()->user());
          $this->dispatch('google-disconnected');
      }
      
      public function syncNow(SyncGoogleCalendarAction $action): void
      {
          try {
              $result = $action->execute(auth()->user());
              $this->dispatch('sync-completed', 
                  created: $result->created, 
                  updated: $result->updated
              );
          } catch (\Exception $e) {
              $this->dispatch('sync-failed', message: $e->getMessage());
          }
      }
      
      #[Computed]
      public function googleToken(): ?UserGoogleToken
      {
          return auth()->user()->googleToken;
      }
  }; ?>
  
  <div>
      <flux:heading size="xl" class="mb-6">Entegrasyonlar</flux:heading>
      
      <div class="max-w-2xl">
          <!-- Google Calendar Card -->
          <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
              <div class="flex items-center justify-between">
                  <div class="flex items-center gap-4">
                      <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow">
                          <svg class="w-8 h-8" viewBox="0 0 24 24">
                              <!-- Google Calendar icon -->
                          </svg>
                      </div>
                      <div>
                          <flux:heading size="md">Google Calendar</flux:heading>
                          @if($this->googleToken)
                              <flux:badge variant="success">Bağlı</flux:badge>
                          @else
                              <flux:badge variant="outline">Bağlı değil</flux:badge>
                          @endif
                      </div>
                  </div>
                  
                  @if($this->googleToken)
                      <div class="flex gap-2">
                          <flux:button 
                              variant="outline" 
                              wire:click="syncNow"
                              wire:loading.attr="disabled"
                          >
                              <span wire:loading.remove>Şimdi Sync</span>
                              <span wire:loading>Sync ediliyor...</span>
                          </flux:button>
                          <flux:button 
                              variant="ghost" 
                              wire:click="disconnect"
                          >
                              Bağlantıyı Kes
                          </flux:button>
                      </div>
                  @else
                      <flux:button 
                          variant="primary" 
                          wire:click="connect"
                      >
                          Bağla
                      </flux:button>
                  @endif
              </div>
              
              @if($this->googleToken)
              <div class="mt-4 pt-4 border-t dark:border-zinc-700">
                  <flux:text size="sm" class="text-zinc-500">
                      Son sync: {{ $this->googleToken->last_synced_at?->diffForHumans() ?? 'Henüz sync edilmedi' }}
                  </flux:text>
                  <flux:text size="sm" class="text-zinc-500">
                      Otomatik sync: Her 5 dakika
                  </flux:text>
              </div>
              @endif
          </div>
      </div>
  </div>
  ```

### 6.8 OAuth Callback Controller

- [ ] `app/Http/Controllers/GoogleCalendarCallbackController.php`:
  ```php
  class GoogleCalendarCallbackController extends Controller
  {
      public function __invoke(
          Request $request,
          GoogleCalendarService $service,
          ConnectGoogleCalendarAction $action
      ): RedirectResponse {
          if ($request->has('error')) {
              return redirect()->route('settings.integrations')
                  ->with('error', 'Google Calendar bağlantısı iptal edildi');
          }
          
          $tokenData = $service->handleCallback($request->code);
          $action->execute($request->user(), $tokenData);
          
          // İlk sync'i başlat
          SyncGoogleCalendarJob::dispatch($request->user());
          
          return redirect()->route('settings.integrations')
              ->with('success', 'Google Calendar başarıyla bağlandı');
      }
  }
  ```

- [ ] `routes/web.php`:
  ```php
  Route::middleware('auth')->group(function () {
      Route::get('/integrations/google-calendar/callback', GoogleCalendarCallbackController::class)
          ->name('google-calendar.callback');
  });
  ```

---

## Doğrulama

```bash
php artisan test --filter=GoogleCalendar
```

Manuel test:
1. Ayarlar → Entegrasyonlar sayfasına git
2. "Google Calendar Bağla" butonuna tıkla
3. Google hesabı seç ve izin ver
4. Callback → "Bağlı" badge görünsün
5. Takvim sayfasına git → Google randevuları görünsün
6. "Şimdi Sync" butonuna tıkla → Yeni randevular gelsin
7. "Bağlantıyı Kes" → Token silinsin

---

## Dosya Listesi

```
app/
├── Actions/GoogleCalendar/
│   ├── ConnectGoogleCalendarAction.php
│   ├── DisconnectGoogleCalendarAction.php
│   └── SyncGoogleCalendarAction.php
├── Data/
│   └── SyncResult.php
├── Http/Controllers/
│   └── GoogleCalendarCallbackController.php
├── Jobs/
│   └── SyncGoogleCalendarJob.php
├── Models/
│   └── UserGoogleToken.php
├── Services/
│   └── GoogleCalendarService.php
└── Console/Commands/
    └── SyncCalendarsCommand.php

database/migrations/
└── xxxx_create_user_google_tokens_table.php

resources/views/livewire/settings/
└── integrations.blade.php

config/
└── services.php (+ google_calendar)
```

---

## .env Gereksinimleri

```env
# Google Calendar API (Ayrı credentials - Auth'dan farklı)
GOOGLE_CALENDAR_CLIENT_ID=xxx
GOOGLE_CALENDAR_CLIENT_SECRET=xxx
GOOGLE_CALENDAR_REDIRECT_URI=http://localhost/integrations/google-calendar/callback
```

---

## config/services.php

```php
'google_calendar' => [
    'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_CALENDAR_REDIRECT_URI'),
],
```

---

## Google Event → Appointment Mapping

| Google Field | OpsMind Field | Dönüşüm |
|--------------|---------------|---------|
| id | google_calendar_id | Direkt |
| summary | title | Fallback: "Untitled" |
| description | description | Nullable |
| start.dateTime / start.date | start_at | all_day kontrolü |
| end.dateTime / end.date | end_at | all_day kontrolü |
| location | location | Nullable |
| colorId | color | Hex'e map |

---

## Notlar

- Token'lar **encrypted** olarak saklanır
- Sadece **primary calendar** sync edilir (MVP)
- Sync aralığı: **5 dakika**
- Google API quota: 1M request/gün (yeterli)
- Refresh token **offline access** ile alınır
- Shared calendar'lar Post-MVP
- Two-way sync Post-MVP
