# Adım 07: Day View (Günlük Görünüm)

## Hedef
Günlük görünümü oluşturmak. Week View'ın tek günlük basitleştirilmiş versiyonu.

---

## Mevcut Durum Analizi

### Tamamlanan Adımlar
| Adım | Durum | Açıklama |
|------|-------|----------|
| 01-04 | ✅ | Shell, Toolbar, Month Grid |
| 05 | ✅ | Week Grid (scroll + sticky header) |
| 06 | ✅ | Week Event Positioning |

### Mevcut Placeholder (Satır 275-282)
```html
@else
    {{-- Day View Placeholder --}}
    <div class="p-4 sm:p-6 text-center">
        <flux:icon name="calendar" ... />
        <flux:text ...>Günlük görünüm Adım 07'de eklenecek.</flux:text>
    </div>
@endif
```

### Mevcut PHP Logic (Satır 63-105)
- `$days`: Week view için 7 günlük dizi alıyor (`getWeekGrid`)
- `$events`: Sadece `week` view'da dolduruluyor

---

## Kritik Farklılıklar: Week vs Day

| Özellik | Week View | Day View |
|---------|-----------|----------|
| Gün sayısı | 7 | 1 |
| Header | 7 gün başlığı | Sadece tarih |
| Scroll | Aynı | Aynı |
| Event filtering | dayIndex (0-6) | Sadece bugün (dayIndex = 0) |
| Veri kaynağı | getWeekGrid() | getCurrentDate |

---

## Yapılacaklar

### 1. PHP: Event Verisini Day View İçin de Ekle (Satır 73-105)

Mevcut:
```php
if ($this->view === 'week') {
    $events = [...];
}
```

Yeni:
```php
if (in_array($this->view, ['week', 'day'])) {
    $events = [
        // ... aynı eventler ...
    ];
}
```

**Not:** Day view için `dayIndex` filtreleme template'de yapılacak. Şimdilik week'teki eventleri gösterelim - hangisi `currentDate` ile eşleşiyorsa.

### 2. HTML: Day View Bloğu (Satır 275-282'yi değiştir)

`@else` bloğunu `@elseif($view === 'day')` olarak değiştir ve içeriği ekle:

```html
        @elseif($view === 'day')
            {{-- Day View Container (single scroll container) --}}
            <div class="h-[500px] sm:h-[600px] lg:h-[750px] overflow-y-auto">
                
                {{-- Header Row (sticky) --}}
                <div class="flex sticky top-0 z-30 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                    {{-- Time Column Header (Empty Corner) --}}
                    <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900"></div>
                    
                    {{-- Single Day Header --}}
                    <div class="flex-1 py-3 sm:py-4 text-center bg-zinc-50 dark:bg-zinc-900">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->currentDate->locale('tr')->translatedFormat('l') }}
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold {{ $this->currentDate->isToday() ? 'text-primary-600 dark:text-primary-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                            {{ $this->currentDate->day }}
                        </div>
                        <div class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ $this->currentDate->locale('tr')->translatedFormat('F Y') }}
                        </div>
                    </div>
                </div>
                
                {{-- Body Row (Time Column + Day Column) --}}
                <div class="flex">
                    {{-- Time Column --}}
                    <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                        @foreach($timeSlots as $slot)
                            <div class="h-[60px] flex items-center justify-center border-b border-dotted border-zinc-200 dark:border-zinc-700">
                                <span class="text-[10px] sm:text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ $slot }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    
                    {{-- Single Day Column --}}
                    <div class="flex-1 relative">
                        @foreach($timeSlots as $slot)
                            <div class="h-[60px] border-b border-dotted border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition"></div>
                        @endforeach
                        
                        {{-- Events (filter by current day's weekday) --}}
                        @php
                            $currentDayIndex = $this->currentDate->dayOfWeekIso - 1; // 0=Pzt, 6=Paz
                        @endphp
                        @foreach($events as $event)
                            @if($event['dayIndex'] === $currentDayIndex)
                                @php
                                    $top = ($event['startHour'] * 60) + $event['startMinute'];
                                    $height = max(30, $event['durationMinutes']);
                                @endphp
                                <div 
                                    class="absolute inset-x-2 z-10 rounded-md px-3 py-2 text-sm font-medium overflow-hidden cursor-pointer transition-all shadow-sm border-l-4 hover:shadow-md
                                    @switch($event['color'])
                                        @case('primary')
                                            bg-primary-100 border-primary-500 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300
                                            @break
                                        @case('success')
                                            bg-emerald-100 border-emerald-500 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                                            @break
                                        @case('warning')
                                            bg-amber-100 border-amber-500 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                                            @break
                                        @case('danger')
                                            bg-rose-100 border-rose-500 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300
                                            @break
                                        @default
                                            bg-zinc-100 border-zinc-500 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300
                                    @endswitch"
                                    style="top: {{ $top }}px; height: {{ $height }}px;">
                                    <span class="line-clamp-3">{{ $event['title'] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                
            </div>
        @endif
```

---

## Kod Açıklamaları

| Öğe | Açıklama |
|-----|----------|
| `dayOfWeekIso - 1` | Carbon'da Pzt=1, Pas=7. dayIndex için 0-6 formatına çevir |
| `text-2xl sm:text-3xl` | Day view'da daha büyük gün numarası |
| `inset-x-2` | Day view'da daha geniş event kartları (8px margin) |
| `text-sm` + `line-clamp-3` | Daha büyük font, 3 satır limit |
| `l` format | "Pazartesi" gibi tam gün ismi |

---

## Doğrulama

| # | Test | Beklenen Sonuç |
|---|------|----------------|
| 1 | Ay başından "Gün" butonuna tıkla | Bugünün görünümü açılsın |
| 2 | Header'da tam gün ismi | "Pazartesi" gibi |
| 3 | Header'da büyük gün numarası | Vurgulu |
| 4 | Time column | 00:00 - 23:00 |
| 5 | Pazartesi'ye git + dayIndex=0 event varsa | "Ofis Toplantısı" görünsün |
| 6 | Çarşamba'ya git | "Müşteri Görüşmesi" görünsün |
| 7 | Cuma'ya git | "Proje Sunumu" görünsün |
| 8 | Scroll çalışsın | Header sticky |
| 9 | Dark mode | Renkler doğru |

---

## Çıktı
✅ Günlük görünüm (event'li)
