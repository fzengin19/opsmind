# Faz 3: Dashboard Skeleton

**Süre:** 2-3 gün  
**Önkoşul:** Faz 2 (Auth & Roles)  
**Çıktı:** Ana layout, Tailwind v4 tema, temel dashboard

---

## Amaç

Tailwind CSS v4 tema yapısı, Flux UI layout bileşenleri ve Class-based Volt ile ana uygulama iskeletini oluşturmak.

---

## Layout Yapısı

```
┌─────────────────────────────────────────────────────────────┐
│  Logo    🔍 Search    🔔 Notifications    👤 Profile        │  ← Navbar
├─────────┬───────────────────────────────────────────────────┤
│ 📅 Cal  │                                                   │
│ 👥 CRM  │              Main Content Area                    │  ← Sidebar + Content
│ ✅ Tasks│                                                   │
│ ⚙️ Set  │                                                   │
└─────────┴───────────────────────────────────────────────────┘
```

---

## Görevler

### 3.1 Tailwind CSS v4 Tema Kurulumu

> Tailwind v4 CSS-first yapılandırma kullanır. `tailwind.config.js` yok!

- [ ] `resources/css/app.css` güncelle:
  ```css
  @import "tailwindcss";
  @import "../../vendor/livewire/flux/dist/flux.css";
  
  @theme {
    /* OpsMind Marka Renkleri */
    --color-brand-50: oklch(0.97 0.02 250);
    --color-brand-100: oklch(0.93 0.04 250);
    --color-brand-500: oklch(0.55 0.15 250);
    --color-brand-600: oklch(0.48 0.15 250);
    --color-brand-700: oklch(0.40 0.15 250);
    
    /* Semantic Colors */
    --color-success: oklch(0.65 0.15 145);
    --color-warning: oklch(0.75 0.15 85);
    --color-danger: oklch(0.60 0.20 25);
    
    /* Font */
    --font-display: "Figtree", sans-serif;
    --font-body: "Inter", sans-serif;
    
    /* Custom Breakpoint */
    --breakpoint-3xl: 1920px;
  }
  
  /* Dark mode overrides */
  @media (prefers-color-scheme: dark) {
    :root {
      --color-brand-500: oklch(0.65 0.15 250);
    }
  }
  ```

### 3.2 Ana Layout Component

- [ ] `resources/views/components/layouts/app.blade.php`:
  ```blade
  <!DOCTYPE html>
  <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
        x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
        :class="{ 'dark': darkMode }">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      @vite(['resources/css/app.css', 'resources/js/app.js'])
      @fluxAppearance
  </head>
  <body class="min-h-screen bg-white dark:bg-zinc-900">
      <livewire:layout.navbar />
      
      <div class="flex">
          <livewire:layout.sidebar />
          
          <main class="flex-1 p-6">
              {{ $slot }}
          </main>
      </div>
      
      @fluxScripts
  </body>
  </html>
  ```

### 3.3 Navbar Component (Class-based Volt)

- [ ] `resources/views/livewire/layout/navbar.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  
  new class extends Component {
      public function logout(): void
      {
          auth()->logout();
          $this->redirect('/login');
      }
  }; ?>
  
  <flux:navbar class="border-b border-zinc-200 dark:border-zinc-700">
      <flux:navbar.item href="/" class="font-bold text-brand-600">
          OpsMind
      </flux:navbar.item>
      
      <flux:spacer />
      
      <!-- Search (Placeholder - Faz 10) -->
      <flux:input icon="magnifying-glass" placeholder="Ara..." disabled />
      
      <!-- Notifications (Placeholder - Faz 9) -->
      <flux:button variant="ghost" icon="bell" />
      
      <!-- Dark Mode Toggle -->
      <flux:button 
          variant="ghost" 
          icon="moon"
          x-on:click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
      />
      
      <!-- Profile Dropdown -->
      <flux:dropdown>
          <flux:button variant="ghost">
              <flux:avatar size="sm" :src="auth()->user()->avatar" />
              <span class="ml-2">{{ auth()->user()->name }}</span>
          </flux:button>
          
          <flux:menu>
              <flux:menu.item href="/settings" icon="cog-6-tooth">
                  Ayarlar
              </flux:menu.item>
              <flux:separator />
              <flux:menu.item wire:click="logout" icon="arrow-right-on-rectangle">
                  Çıkış Yap
              </flux:menu.item>
          </flux:menu>
      </flux:dropdown>
  </flux:navbar>
  ```

### 3.4 Sidebar Component (Class-based Volt)

- [ ] `resources/views/livewire/layout/sidebar.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  
  new class extends Component {
      public function isActive(string $route): bool
      {
          return request()->routeIs($route . '*');
      }
  }; ?>
  
  <aside class="w-64 border-r border-zinc-200 dark:border-zinc-700 min-h-screen p-4">
      <nav class="space-y-1">
          <flux:button 
              variant="{{ $this->isActive('dashboard') ? 'primary' : 'ghost' }}"
              href="/dashboard"
              icon="home"
              class="w-full justify-start"
          >
              Dashboard
          </flux:button>
          
          <flux:button 
              variant="{{ $this->isActive('calendar') ? 'primary' : 'ghost' }}"
              href="/calendar"
              icon="calendar"
              class="w-full justify-start"
          >
              Takvim
          </flux:button>
          
          <flux:button 
              variant="{{ $this->isActive('contacts') ? 'primary' : 'ghost' }}"
              href="/contacts"
              icon="users"
              class="w-full justify-start"
          >
              Kişiler
          </flux:button>
          
          <flux:button 
              variant="{{ $this->isActive('tasks') ? 'primary' : 'ghost' }}"
              href="/tasks"
              icon="check-circle"
              class="w-full justify-start"
          >
              Görevler
          </flux:button>
          
          @role('admin')
          <flux:separator />
          <flux:button 
              variant="{{ $this->isActive('settings') ? 'primary' : 'ghost' }}"
              href="/settings"
              icon="cog-6-tooth"
              class="w-full justify-start"
          >
              Ayarlar
          </flux:button>
          @endrole
      </nav>
  </aside>
  ```

### 3.5 Dashboard Sayfası (Class-based Volt)

- [ ] `resources/views/livewire/dashboard.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Layout;
  
  new #[Layout('components.layouts.app')] class extends Component {
      // Dashboard logic will be added in later phases
  }; ?>
  
  <div>
      <flux:heading size="xl" class="mb-6">
          Hoş geldin, {{ auth()->user()->name }}! 👋
      </flux:heading>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Today's Appointments (Placeholder) -->
          <livewire:dashboard.today-appointments />
          
          <!-- Assigned Tasks (Placeholder) -->
          <livewire:dashboard.assigned-tasks />
          
          <!-- Recent Activity (Placeholder) -->
          <livewire:dashboard.recent-activity />
      </div>
      
      <!-- Quick Actions -->
      <div class="mt-8 flex gap-4">
          <flux:button variant="primary" icon="plus">
              Yeni Randevu
          </flux:button>
          <flux:button variant="outline" icon="plus">
              Yeni Görev
          </flux:button>
          <flux:button variant="outline" icon="user-plus">
              Yeni Kişi
          </flux:button>
      </div>
  </div>
  ```

### 3.6 Placeholder Widget Components (Lazy Loading)

> Widget'lar `#[Lazy]` attribute ile yüklenecek, skeleton gösterecek

- [ ] `resources/views/livewire/dashboard/today-appointments.blade.php`:
  ```php
  <?php
  use Livewire\Volt\Component;
  use Livewire\Attributes\Lazy;
  
  new #[Lazy] class extends Component {
      public function placeholder(): string
      {
          return <<<'HTML'
          <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 shadow-sm">
              <div class="animate-pulse space-y-3">
                  <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-1/2"></div>
                  <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
                  <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded w-3/4"></div>
              </div>
          </div>
          HTML;
      }
  }; ?>
  
  <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 shadow-sm">
      <flux:heading size="sm" class="mb-4">📅 Bugünün Randevuları</flux:heading>
      
      <flux:text class="text-zinc-500">
          Henüz randevu yok.
      </flux:text>
  </div>
  ```

- [ ] `assigned-tasks.blade.php` (benzer yapı)
- [ ] `recent-activity.blade.php` (benzer yapı)

### 3.7 Responsive Design

- [ ] Mobile hamburger menu (Alpine.js)
- [ ] Sidebar drawer pattern
- [ ] Breakpoint: `md:` ve üzeri sidebar görünür
- [ ] Widget grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3`

### 3.8 Routes

- [ ] `routes/web.php` güncelle:
  ```php
  Route::middleware(['auth', 'verified'])->group(function () {
      Route::view('/dashboard', 'livewire.dashboard')->name('dashboard');
      Route::view('/calendar', 'livewire.calendar.index')->name('calendar');
      Route::view('/contacts', 'livewire.contacts.index')->name('contacts');
      Route::view('/tasks', 'livewire.tasks.board')->name('tasks');
      
      Route::middleware('role:admin')->group(function () {
          Route::view('/settings', 'livewire.settings.index')->name('settings');
          Route::view('/team', 'livewire.team.index')->name('team');
      });
  });
  ```

---

## Doğrulama

```bash
npm run build
php artisan serve
```

Manuel test:
1. Giriş yap → Dashboard görünsün
2. Hoşgeldin mesajı kullanıcı adını göstersin
3. Sidebar'dan sayfa değiştir
4. Dark mode toggle çalışsın (localStorage'a kaydetsin)
5. Mobile görünümde sidebar çekmece olsun
6. Widget'lar skeleton ile yüklensin

---

## Dosya Listesi

```
resources/
├── css/
│   └── app.css                              # Tailwind v4 @theme
├── views/
│   ├── components/
│   │   └── layouts/
│   │       └── app.blade.php                # Ana layout
│   └── livewire/
│       ├── layout/
│       │   ├── navbar.blade.php             # Class-based Volt
│       │   └── sidebar.blade.php            # Class-based Volt
│       ├── dashboard.blade.php              # Class-based Volt
│       └── dashboard/
│           ├── today-appointments.blade.php # Lazy widget
│           ├── assigned-tasks.blade.php     # Lazy widget
│           └── recent-activity.blade.php    # Lazy widget

routes/
└── web.php                                   # Route güncellemeleri
```

---

## Flux UI Bileşenleri

| Bileşen | Kullanım |
|---------|----------|
| `<flux:navbar>` | Üst navigasyon |
| `<flux:dropdown>` | Profil menüsü |
| `<flux:button>` | Sidebar links, actions |
| `<flux:avatar>` | Kullanıcı avatar'ı |
| `<flux:heading>` | Sayfa başlıkları |
| `<flux:text>` | Metin içerikleri |
| `<flux:separator>` | Menü ayırıcı |
| `<flux:input>` | Arama input |

---

## Notlar

- Widget içerikleri Faz 10'da gerçek data ile doldurulacak
- Arama işlevselliği Faz 10'da aktif edilecek
- Bildirim dropdown Faz 9'da aktif edilecek
- `#[Lazy]` attribute performans için kritik
- Dark mode tercihi localStorage'da saklanır
