# Adım 01: Shell Component

## Hedef
Takvim sayfasının en temel iskeletini oluşturmak. Design System v2.0'a tam uyumlu boş bir sayfa ve çalışan route.

---

## Design System Kontrol Listesi

Bu adımda uyulması gereken standartlar:

| Kural | Uygulama |
|-------|----------|
| **Sayfa Yapısı** | `flex flex-col gap-6` wrapper |
| **Page Header** | `flux:heading size="xl"` + `flux:subheading` |
| **Card Anatomisi** | `p-4 sm:p-6 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm` |
| **Nötr Renkler** | Sadece `zinc` (gray/slate YASAK) |
| **Başlık Hierarşisi** | Manuel `<h1>` yerine `<flux:heading>` |

---

## Yapılacaklar

### 1. Route Tanımla
`routes/web.php` dosyasına geçici test route'u ekle:

```php
use Livewire\Volt\Volt;

// Auth middleware grubunun içine ekle:
Volt::route('/calendar-test', 'calendar.index')->name('calendar.test');
```

### 2. Klasör Oluştur
```bash
mkdir -p resources/views/livewire/calendar
```

### 3. Volt Component Oluştur
`resources/views/livewire/calendar/index.blade.php`:

```php
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    //
}; ?>

<div class="flex flex-col gap-6">

    {{-- Page Header (Design System 2.1) --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Takvim</flux:heading>
            <flux:subheading>Randevularınızı ve etkinliklerinizi yönetin.</flux:subheading>
        </div>
        <div class="flex items-center gap-3">
            {{-- Action buttons will be added in Step 02 --}}
        </div>
    </div>

    {{-- Content Area (Design System 2.2 Card Anatomy) --}}
    <div class="p-4 sm:p-6 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm">
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            Takvim içeriği burada oluşturulacak.
        </flux:text>
    </div>

</div>
```

---

## Kod Açıklamaları

| Satır | Açıklama |
|-------|----------|
| `flex flex-col gap-6` | Design System 2.1: Global Page Gap (24px) |
| `flux:heading size="xl"` | Design System 3.1: Sayfa Ana Başlığı |
| `flux:subheading` | Flux UI'ın standart alt başlık bileşeni |
| `p-4 sm:p-6` | Design System 2.2: Mobile p-4, Desktop p-6 |
| `bg-white dark:bg-zinc-800` | Design System 1.3: Card Background |
| `border-zinc-200 dark:border-zinc-700` | Design System 2.2: Card Border |
| `rounded-xl shadow-sm` | Design System 2.2: Card Radius & Shadow |

---

## Doğrulama

| # | Test | Beklenen Sonuç |
|---|------|----------------|
| 1 | `/calendar-test` adresine git | Sayfa yüklensin |
| 2 | Başlık kontrolü | "Takvim" başlığı `flux:heading` ile görünsün |
| 3 | Alt başlık kontrolü | Açıklama metni görünsün |
| 4 | Kart yapısı | Beyaz kart, yuvarlatılmış köşeler, gölge |
| 5 | Dark mode | Karanlık temada düzgün görünsün |
| 6 | Sidebar | Sidebar ve navbar normal çalışsın |
| 7 | Responsive | Mobile'da header dikey, desktop'ta yatay |

---

## Çıktı
✅ Design System v2.0 uyumlu boş takvim sayfası
