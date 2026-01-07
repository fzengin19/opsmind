# Adım 04: Month Data

## Hedef
`CalendarService`'ten gerçek ay verilerini çekip grid'e basmak.

## Önkoşul
- Adım 03 tamamlanmış olmalı
- `CalendarService::getMonthGrid()` çalışıyor olmalı

## Yapılacaklar

### 1. Service'i Inject Et
Component'e `with()` metodu ekle:

```php
use App\Services\CalendarService;

// ... mevcut kod ...

public function with(CalendarService $service): array
{
    return [
        'days' => $this->view === 'month' 
            ? $service->getMonthGrid($this->currentDate) 
            : [],
    ];
}
```

### 2. Dinamik Grid
Statik `@for` döngüsünü `@foreach` ile değiştir:

```html
<!-- Grid Hücreleri -->
<div class="grid grid-cols-7">
    @foreach($days as $day)
        <div class="min-h-[100px] p-2 border-b border-r border-zinc-200 dark:border-zinc-700 transition
            {{ $day['isCurrentMonth'] 
                ? 'bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/30' 
                : 'bg-zinc-50 dark:bg-zinc-900/50' }}">
            
            <!-- Gün Numarası -->
            <span class="inline-flex items-center justify-center text-sm font-medium
                {{ $day['isToday'] 
                    ? 'size-7 bg-brand-500 text-white rounded-full' 
                    : ($day['isCurrentMonth'] ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-400 dark:text-zinc-500') }}">
                {{ $day['day'] }}
            </span>
            
        </div>
    @endforeach
</div>
```

### 3. Dinamik Başlık
Toolbar'daki başlığı görünüme göre güncelle:

```html
<h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
    @if($view === 'month')
        {{ $this->currentDate->locale('tr')->translatedFormat('F Y') }}
    @elseif($view === 'week')
        {{ $this->currentDate->copy()->startOfWeek()->translatedFormat('d') }} - 
        {{ $this->currentDate->copy()->endOfWeek()->translatedFormat('d M Y') }}
    @else
        {{ $this->currentDate->locale('tr')->translatedFormat('d F Y') }}
    @endif
</h1>
```

## Doğrulama
1. Ayın doğru günleri görünsün (1, 2, 3... 30/31)
2. Önceki/sonraki aydan taşan günler soluk renkte olsun
3. Bugün yuvarlak mavi arka planla vurgulansın
4. İleri/Geri butonları ay değiştirsin ve grid güncellensin
5. Başlık "Ocak 2025" formatında görünsün

## Çıktı
✅ Dinamik aylık takvim
