# Adım 02: Toolbar

## Hedef
Takvim üst çubuğunu oluşturmak: Dinamik tarih başlığı, navigasyon butonları (İleri/Geri/Bugün), görünüm seçici (Ay/Hafta/Gün).

---

## Önkoşul
- Adım 01 tamamlanmış olmalı
- Mevcut dosya: `resources/views/livewire/calendar/index.blade.php`

---

## Design System Kontrol Listesi

| Kural | Uygulama |
|-------|----------|
| **Buton Grupları** | `gap-3` spacing (Design System 2.3) |
| **Primary Buton** | Aktif görünüm için `variant="primary"` |
| **Ghost Buton** | İnaktif görünümler için `variant="ghost"` |
| **İkonlar** | `flux:icon` ile `size-4` boyutunda, `text-zinc-500` rengi |
| **Nötr Renkler** | Sadece `zinc` (Design System 1.3) |

---

## Yapılacaklar

### 1. PHP Logic Ekle (Satır 3-8 arası)

Mevcut boş class'ı aşağıdakiyle değiştir:

```php
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {
    #[Url]
    public string $view = 'month';

    #[Url]
    public string $date = '';

    public function mount(): void
    {
        if (empty($this->date)) {
            $this->date = now()->toDateString();
        }
    }

    public function getCurrentDateProperty(): Carbon
    {
        return Carbon::parse($this->date);
    }

    public function next(): void
    {
        $date = $this->currentDate->copy();
        match ($this->view) {
            'month' => $date->addMonth(),
            'week' => $date->addWeek(),
            'day' => $date->addDay(),
        };
        $this->date = $date->toDateString();
    }

    public function prev(): void
    {
        $date = $this->currentDate->copy();
        match ($this->view) {
            'month' => $date->subMonth(),
            'week' => $date->subWeek(),
            'day' => $date->subDay(),
        };
        $this->date = $date->toDateString();
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }
}; ?>
```

### 2. Page Header Güncelle (Satır 12-21)

Mevcut Page Header bloğunu aşağıdakiyle değiştir:

```html
{{-- Page Header with Toolbar (Design System 2.1) --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    
    {{-- Left: Title + Navigation --}}
    <div class="flex items-center gap-4">
        {{-- Dynamic Title --}}
        <flux:heading size="xl">
            @if($view === 'month')
                {{ $this->currentDate->locale('tr')->translatedFormat('F Y') }}
            @elseif($view === 'week')
                {{ $this->currentDate->copy()->startOfWeek()->translatedFormat('d') }} - 
                {{ $this->currentDate->copy()->endOfWeek()->translatedFormat('d M Y') }}
            @else
                {{ $this->currentDate->locale('tr')->translatedFormat('d F Y') }}
            @endif
        </flux:heading>
        
        {{-- Navigation Buttons (Design System 4.1 - ghost variant) --}}
        <div class="flex items-center gap-1">
            <flux:button variant="ghost" size="sm" icon="chevron-left" wire:click="prev" />
            <flux:button variant="ghost" size="sm" wire:click="today">Bugün</flux:button>
            <flux:button variant="ghost" size="sm" icon="chevron-right" wire:click="next" />
        </div>
    </div>
    
    {{-- Right: View Switcher --}}
    <div class="flex items-center gap-1 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
        <flux:button 
            :variant="$view === 'month' ? 'primary' : 'ghost'" 
            size="sm"
            wire:click="setView('month')">
            Ay
        </flux:button>
        <flux:button 
            :variant="$view === 'week' ? 'primary' : 'ghost'" 
            size="sm"
            wire:click="setView('week')">
            Hafta
        </flux:button>
        <flux:button 
            :variant="$view === 'day' ? 'primary' : 'ghost'" 
            size="sm"
            wire:click="setView('day')">
            Gün
        </flux:button>
    </div>
    
</div>
```

### 3. Content Area Güncelle (Satır 23-28)

Debug bilgisi ekle:

```html
{{-- Content Area (Design System 2.2 Card Anatomy) --}}
<div class="p-4 sm:p-6 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm">
    <flux:text class="text-zinc-500 dark:text-zinc-400">
        Görünüm: <strong>{{ $view }}</strong> | 
        Tarih: <strong>{{ $date }}</strong>
    </flux:text>
</div>
```

---

## Kod Açıklamaları

| Öğe | Design System Referansı |
|-----|------------------------|
| `#[Url]` | State'i URL'de saklar (deep linking) |
| `gap-4` | Başlık ile butonlar arası (DS 2.3) |
| `gap-1` | Buton grubu içi sıkı boşluk (DS 2.3) |
| `variant="ghost"` | Navigasyon butonları (DS 4.1) |
| `variant="primary"` | Aktif görünüm butonu (DS 4.1) |
| `bg-zinc-100 dark:bg-zinc-800` | View switcher arka planı (DS 1.3) |
| `rounded-lg` | Buton grubu container |

---

## Doğrulama

| # | Test | Beklenen Sonuç |
|---|------|----------------|
| 1 | Sayfa yükle | "Ocak 2025" gibi dinamik başlık görünsün |
| 2 | "İleri" butonuna tıkla | Ay değişsin (Şubat'a) |
| 3 | "Geri" butonuna tıkla | Önceki aya dön |
| 4 | "Bugün" butonuna tıkla | Bugünkü tarihe dön |
| 5 | "Hafta" butonuna tıkla | Başlık hafta formatına geçsin, buton aktif olsun |
| 6 | "Gün" butonuna tıkla | Başlık gün formatına geçsin |
| 7 | URL kontrolü | `?view=week&date=2025-01-06` gibi parametreler görünsün |
| 8 | Sayfa yenile | State korunsun (URL'den) |
| 9 | Dark mode | Tüm butonlar düzgün görünsün |

---

## Çıktı
✅ Çalışan toolbar + state yönetimi + URL sync
