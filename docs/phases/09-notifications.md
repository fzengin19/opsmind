# Faz 9: Notifications System

**Süre:** 3 gün  
**Önkoşul:** Faz 8 (Kanban Task Board)  
**Çıktı:** In-app ve email bildirimleri (polling-based)

---

## Amaç

Laravel Notifications ile in-app ve email bildirimleri. Class-based Volt dropdown ve sayfası.

> **Not:** Reverb (WebSocket) Post-MVP. MVP'de polling ile bildirim kontrolü yapılacak.

---

## Bildirim Tetikleyicileri

| Tetikleyici | In-App | Email | Açıklama |
|-------------|--------|-------|----------|
| Görev atandı | ✓ | ✓ | Birine görev atandığında |
| Görev yorumu | ✓ | | Görevime yorum yapıldığında |
| Randevu daveti | ✓ | ✓ | Randevuya katılımcı olarak eklendim |
| Randevu hatırlatma | ✓ | ✓ | 1 saat önce |
| Kullanıcı davet | | ✓ | Takıma davet emaili |
| Görev bitiş yaklaştı | ✓ | | 1 gün kala |

---

## Görevler

### 9.1 Notification Classes

- [ ] `app/Notifications/TaskAssignedNotification.php`:
  ```php
  class TaskAssignedNotification extends Notification implements ShouldQueue
  {
      use Queueable;
      
      public function __construct(
          public Task $task
      ) {}
      
      public function via(object $notifiable): array
      {
          $channels = ['database'];
          
          if ($notifiable->notificationSettings?->email_task_assigned) {
              $channels[] = 'mail';
          }
          
          return $channels;
      }
      
      public function toMail(object $notifiable): MailMessage
      {
          return (new MailMessage)
              ->subject('Yeni Görev Atandı: ' . $this->task->title)
              ->greeting('Merhaba ' . $notifiable->name . ',')
              ->line('Size yeni bir görev atandı:')
              ->line('**' . $this->task->title . '**')
              ->action('Görevi Görüntüle', route('tasks'))
              ->line('Teşekkürler!');
      }
      
      public function toArray(object $notifiable): array
      {
          return [
              'type' => 'task_assigned',
              'task_id' => $this->task->id,
              'task_title' => $this->task->title,
              'assigned_by' => $this->task->creator->name,
              'message' => $this->task->creator->name . ' size bir görev atadı: ' . $this->task->title,
              'url' => route('tasks'),
          ];
      }
  }
  ```

- [ ] `TaskCommentedNotification`
- [ ] `AppointmentInviteNotification`
- [ ] `AppointmentReminderNotification`
- [ ] `TaskDueSoonNotification`

### 9.2 Event & Listener Setup

- [ ] `app/Events/TaskAssigned.php`:
  ```php
  class TaskAssigned
  {
      use Dispatchable, InteractsWithSockets, SerializesModels;
      
      public function __construct(
          public Task $task,
          public User $assignee
      ) {}
  }
  ```

- [ ] `app/Listeners/SendTaskAssignedNotification.php`:
  ```php
  class SendTaskAssignedNotification
  {
      public function handle(TaskAssigned $event): void
      {
          $event->assignee->notify(new TaskAssignedNotification($event->task));
      }
  }
  ```

- [ ] `bootstrap/app.php` event registration:
  ```php
  ->withEvents([
      TaskAssigned::class => [SendTaskAssignedNotification::class],
      TaskCommented::class => [SendTaskCommentedNotification::class],
      AppointmentAttendeeAdded::class => [SendAppointmentInviteNotification::class],
  ])
  ```

### 9.3 Notification Settings

- [ ] `database/migrations/xxxx_create_notification_settings_table.php`:
  ```php
  Schema::create('notification_settings', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->boolean('email_task_assigned')->default(true);
      $table->boolean('email_appointment_invite')->default(true);
      $table->boolean('email_appointment_reminder')->default(true);
      $table->timestamps();
      
      $table->unique('user_id');
  });
  ```

- [ ] `app/Models/NotificationSetting.php`

### 9.4 Notification Dropdown Component (Class-based Volt)

- [ ] `resources/views/livewire/notifications/dropdown.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Computed;
  
  new class extends Component {
      public function markAsRead(string $id): void
      {
          auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
      }
      
      public function markAllAsRead(): void
      {
          auth()->user()->unreadNotifications->markAsRead();
      }
      
      #[Computed]
      public function notifications(): Collection
      {
          return auth()->user()
              ->notifications()
              ->latest()
              ->limit(10)
              ->get();
      }
      
      #[Computed]
      public function unreadCount(): int
      {
          return auth()->user()->unreadNotifications()->count();
      }
  }; ?>
  
  <flux:dropdown>
      <flux:button variant="ghost" class="relative">
          <flux:icon name="bell" class="w-5 h-5" />
          @if($this->unreadCount > 0)
          <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
              {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
          </span>
          @endif
      </flux:button>
      
      <flux:menu class="w-80">
          <div class="px-4 py-2 border-b dark:border-zinc-700 flex justify-between items-center">
              <span class="font-medium">Bildirimler</span>
              @if($this->unreadCount > 0)
              <flux:button variant="ghost" size="sm" wire:click="markAllAsRead">
                  Tümünü oku
              </flux:button>
              @endif
          </div>
          
          <div class="max-h-96 overflow-y-auto">
              @forelse($this->notifications as $notification)
              <a 
                  href="{{ $notification->data['url'] ?? '#' }}"
                  wire:click="markAsRead('{{ $notification->id }}')"
                  class="block px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700 {{ !$notification->read_at ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
              >
                  <div class="flex items-start gap-3">
                      <div class="flex-shrink-0 mt-1">
                          @switch($notification->data['type'] ?? '')
                              @case('task_assigned')
                                  <flux:icon name="clipboard-document-check" class="w-5 h-5 text-blue-500" />
                                  @break
                              @case('appointment_invite')
                                  <flux:icon name="calendar" class="w-5 h-5 text-green-500" />
                                  @break
                              @default
                                  <flux:icon name="bell" class="w-5 h-5 text-zinc-400" />
                          @endswitch
                      </div>
                      <div class="flex-1 min-w-0">
                          <p class="text-sm text-zinc-900 dark:text-zinc-100">
                              {{ $notification->data['message'] ?? 'Yeni bildirim' }}
                          </p>
                          <p class="text-xs text-zinc-500 mt-1">
                              {{ $notification->created_at->diffForHumans() }}
                          </p>
                      </div>
                      @if(!$notification->read_at)
                      <div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>
                      @endif
                  </div>
              </a>
              @empty
              <div class="px-4 py-8 text-center text-zinc-500">
                  Bildirim yok
              </div>
              @endforelse
          </div>
          
          <div class="px-4 py-2 border-t dark:border-zinc-700">
              <a href="{{ route('notifications') }}" class="text-sm text-brand-600 hover:underline">
                  Tümünü gör
              </a>
          </div>
      </flux:menu>
  </flux:dropdown>
  ```

### 9.5 Notifications Page

- [ ] `resources/views/livewire/notifications/index.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Layout;
  use Livewire\Attributes\Url;
  use Livewire\WithPagination;
  
  new #[Layout('components.layouts.app')] class extends Component {
      use WithPagination;
      
      #[Url]
      public string $filter = 'all'; // all, unread, read
      
      public function markAsRead(string $id): void
      {
          auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
      }
      
      public function markAllAsRead(): void
      {
          auth()->user()->unreadNotifications->markAsRead();
      }
      
      #[Computed]
      public function notifications(): LengthAwarePaginator
      {
          return auth()->user()
              ->notifications()
              ->when($this->filter === 'unread', fn ($q) => $q->whereNull('read_at'))
              ->when($this->filter === 'read', fn ($q) => $q->whereNotNull('read_at'))
              ->latest()
              ->paginate(20);
      }
  }; ?>
  
  <div class="max-w-3xl">
      <div class="flex justify-between items-center mb-6">
          <flux:heading size="xl">Bildirimler</flux:heading>
          <flux:button wire:click="markAllAsRead" variant="outline">
              Tümünü Okundu İşaretle
          </flux:button>
      </div>
      
      <div class="flex gap-2 mb-4">
          <flux:button :variant="$filter === 'all' ? 'primary' : 'ghost'" wire:click="$set('filter', 'all')">
              Tümü
          </flux:button>
          <flux:button :variant="$filter === 'unread' ? 'primary' : 'ghost'" wire:click="$set('filter', 'unread')">
              Okunmamış
          </flux:button>
          <flux:button :variant="$filter === 'read' ? 'primary' : 'ghost'" wire:click="$set('filter', 'read')">
              Okunmuş
          </flux:button>
      </div>
      
      <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm divide-y dark:divide-zinc-700">
          @forelse($this->notifications as $notification)
          <!-- Notification item -->
          @empty
          <div class="p-12 text-center text-zinc-500">
              Bildirim yok
          </div>
          @endforelse
      </div>
      
      <div class="mt-4">
          {{ $this->notifications->links() }}
      </div>
  </div>
  ```

### 9.6 Scheduler Commands

- [ ] `app/Console/Commands/SendAppointmentReminders.php`:
  ```php
  #[AsCommand(name: 'notifications:appointment-reminders')]
  class SendAppointmentReminders extends Command
  {
      public function handle(): void
      {
          $upcomingAppointments = Appointment::query()
              ->whereBetween('start_at', [now()->addMinutes(55), now()->addMinutes(65)])
              ->with('attendees.user')
              ->get();
          
          foreach ($upcomingAppointments as $appointment) {
              foreach ($appointment->attendees as $attendee) {
                  if ($attendee->user) {
                      $attendee->user->notify(new AppointmentReminderNotification($appointment));
                  }
              }
          }
          
          $this->info("Sent reminders for {$upcomingAppointments->count()} appointments");
      }
  }
  ```

- [ ] `app/Console/Commands/SendTaskDueSoonNotifications.php`

- [ ] `bootstrap/app.php` scheduler:
  ```php
  ->withSchedule(function (Schedule $schedule) {
      $schedule->command('notifications:appointment-reminders')->hourly();
      $schedule->command('notifications:task-due-soon')->dailyAt('09:00');
  })
  ```

### 9.7 Update Actions to Dispatch Events

- [ ] `app/Actions/Tasks/CreateTaskAction.php` güncelle:
  ```php
  public function execute(TaskData $data, User $user): Task
  {
      $task = // ... create logic
      
      if ($task->assignee_id && $task->assignee_id !== $user->id) {
          event(new TaskAssigned($task, $task->assignee));
      }
      
      return $task;
  }
  ```

### 9.8 Navbar Integration

- [ ] `resources/views/livewire/layout/navbar.blade.php` güncelle:
  ```blade
  <!-- Notifications (Faz 9) -->
  <livewire:notifications.dropdown />
  ```

### 9.9 Notification Settings Page

- [ ] `resources/views/livewire/settings/notifications.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Layout;
  
  new #[Layout('components.layouts.app')] class extends Component {
      public bool $email_task_assigned = true;
      public bool $email_appointment_invite = true;
      public bool $email_appointment_reminder = true;
      
      public function mount(): void
      {
          $settings = auth()->user()->notificationSettings;
          if ($settings) {
              $this->fill($settings->toArray());
          }
      }
      
      public function save(): void
      {
          auth()->user()->notificationSettings()->updateOrCreate(
              ['user_id' => auth()->id()],
              [
                  'email_task_assigned' => $this->email_task_assigned,
                  'email_appointment_invite' => $this->email_appointment_invite,
                  'email_appointment_reminder' => $this->email_appointment_reminder,
              ]
          );
          
          $this->dispatch('settings-saved');
      }
  }; ?>
  
  <div class="max-w-xl">
      <flux:heading size="xl" class="mb-6">Bildirim Ayarları</flux:heading>
      
      <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 space-y-4">
          <flux:heading size="sm">Email Bildirimleri</flux:heading>
          
          <flux:switch wire:model.live="email_task_assigned" label="Görev atandığında" />
          <flux:switch wire:model.live="email_appointment_invite" label="Randevuya eklendiğimde" />
          <flux:switch wire:model.live="email_appointment_reminder" label="Randevu hatırlatması" />
          
          <div class="pt-4">
              <flux:button wire:click="save" variant="primary">Kaydet</flux:button>
          </div>
      </div>
  </div>
  ```

---

## Doğrulama

```bash
php artisan test --filter=Notification
```

Manuel test:
1. Kullanıcı A → Kullanıcı B'ye görev atar
2. B'nin navbar bildirim çanında kırmızı badge görünsün
3. Dropdown'da bildirim görünsün
4. B'ye email gelsin (mailpit/mailtrap)
5. Bildirime tıkla → Görevler sayfasına git
6. "Tümünü okundu" → Badge kaybolsun
7. Randevu oluştur, B'yi ekle → Davet bildirimi
8. Ayarlar → Email bildirimlerini kapat → Email gelmesin

---

## Dosya Listesi

```
app/
├── Events/
│   ├── TaskAssigned.php
│   ├── TaskCommented.php
│   └── AppointmentAttendeeAdded.php
├── Listeners/
│   ├── SendTaskAssignedNotification.php
│   ├── SendTaskCommentedNotification.php
│   └── SendAppointmentInviteNotification.php
├── Notifications/
│   ├── TaskAssignedNotification.php
│   ├── TaskCommentedNotification.php
│   ├── TaskDueSoonNotification.php
│   ├── AppointmentInviteNotification.php
│   └── AppointmentReminderNotification.php
├── Models/
│   └── NotificationSetting.php
└── Console/Commands/
    ├── SendAppointmentReminders.php
    └── SendTaskDueSoonNotifications.php

resources/views/livewire/
├── notifications/
│   ├── dropdown.blade.php
│   └── index.blade.php
└── settings/
    └── notifications.blade.php

database/migrations/
└── xxxx_create_notification_settings_table.php
```

---

## Notlar

- **Polling-based** bildirimler (Reverb Post-MVP)
- Dropdown her 30 saniyede bir refresh: `wire:poll.30s`
- Email'ler **queued** (ShouldQueue)
- Browser push notification Post-MVP
- Slack/Teams Post-MVP
