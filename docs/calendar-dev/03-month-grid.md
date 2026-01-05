# Adım 03: Month Grid (Aylık Takvim Izgarası)

## Hedef
Aylık takvim grid yapısını oluşturmak. **Henüz event yok**, sadece statik 7 sütunlu grid + CalendarService'ten gelen dinamik günler.

---

## Mevcut Durum Analizi

### Tamamlanan Adımlar
| Adım | Durum | Açıklama |
|------|-------|----------|
| 01 - Shell | ✅ | Boş sayfa iskeleti, route çalışıyor |
| 02 - Toolbar | ✅ | Navigasyon + görünüm seçici + URL sync |

### Mevcut Kod Yapısı
**index.blade.php:**
- Satır 1-58: PHP logic (state, metodlar)
- Satır 61-101: Toolbar (başlık + butonlar)
- Satır 104-110: Content Area (şu an debug bilgisi)

**CalendarService.php:**
- `getMonthGrid(Carbon $date)`: 35-42 günlük dizi döner ✅ HAZIR
- Her gün: `date`, `isCurrentMonth`, `isToday`, `day` içeriyor

---

## Önkoşul
- Adım 02 tamamlanmış olmalı
- `CalendarService::getMonthGrid()` çalışıyor

---

## Design System + Responsive Kontrol Listesi

| Kural | Desktop | Mobile | Uygulama |
|-------|---------|--------|----------|
| **Card Container** | p-0 | p-0 | Grid taşmaması için padding yok |
| **Grid** | 7 sütun | 7 sütun | `grid-cols-7` sabit |
| **Cell Height** | 100px | 80px | `min-h-[80px] sm:min-h-[100px]` |
| **Cell Padding** | p-2 | p-1 | `p-1 sm:p-2` |
| **Day Text** | Normal | Küçük | `text-xs sm:text-sm` |
| **Header Text** | Normal | Küçük | `text-xs` (her ikisinde de) |
| **Overflow** | visible | scroll | Container `overflow-x-auto` |

---

## Yapılacaklar

### 1. CalendarService'i Import Et (Satır 6 civarı)

Mevcut import'lara ekle:

```php
use App\Services\CalendarService;
```

### 2. with() Metodu Ekle (Satır 56-57 arası, class kapanmadan önce)

```php
    public function with(CalendarService $service): array
    {
        return [
            'days' => $this->view === 'month' 
                ? $service->getMonthGrid($this->currentDate) 
                : [],
        ];
    }
```

### 3. Content Area'yı Güncelle (Satır 104-110 arası)

Mevcut debug kartını aşağıdakiyle **tam olarak** değiştir:

```html
    {{-- Calendar Grid Container --}}
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm overflow-hidden">
        
        @if($view === 'month')
            {{-- Day Headers (Pzt, Sal, Çar...) --}}
            <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                @foreach(['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] as $dayName)
                    <div class="py-2 sm:py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        {{ $dayName }}
                    </div>
                @endforeach
            </div>

            {{-- Month Grid --}}
            <div class="grid grid-cols-7">
                @foreach($days as $day)
                    <div class="min-h-[80px] sm:min-h-[100px] p-1 sm:p-2 border-b border-r border-zinc-200 dark:border-zinc-700 transition
                        {{ $day['isCurrentMonth'] 
                            ? 'bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/30' 
                            : 'bg-zinc-50/50 dark:bg-zinc-900/30' }}">
                        
                        {{-- Day Number --}}
                        <span class="inline-flex items-center justify-center text-xs sm:text-sm font-medium
                            {{ $day['isToday'] 
                                ? 'size-6 sm:size-7 bg-primary-500 text-white rounded-full' 
                                : ($day['isCurrentMonth'] 
                                    ? 'text-zinc-900 dark:text-zinc-100' 
                                    : 'text-zinc-400 dark:text-zinc-500') }}">
                            {{ $day['day'] }}
                        </span>
                        
                    </div>
                @endforeach
            </div>
        @else
            {{-- Placeholder for Week/Day views --}}
            <div class="p-4 sm:p-6 text-center">
                <flux:icon name="calendar-days" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-3" />
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ $view === 'week' ? 'Haftalık' : 'Günlük' }} görünüm Adım 05'te eklenecek.
                </flux:text>
            </div>
        @endif
        
    </div>
```

---

## Responsive Tasarım Detayları

### Mobile (< 640px)
- Hücre yüksekliği: `80px`
- Hücre padding: `4px` 
- Gün numarası: `text-xs` + `size-6` (bugün için)
- Header padding: `py-2`

### Desktop (≥ 640px)
- Hücre yüksekliği: `100px`
- Hücre padding: `8px`
- Gün numarası: `text-sm` + `size-7` (bugün için)
- Header padding: `py-3`

---

## Doğrulama

| # | Test | Beklenen Sonuç |
|---|------|----------------|
| 1 | Desktop'ta aç | Grid düzgün, 7 sütun eşit genişlikte |
| 2 | Mobilde aç | Grid sığsın, metin okunabilir olsun |
| 3 | Gün başlıkları | "Pzt, Sal..." görünsün |
| 4 | Gün sayıları | 1-31 görünsün |
| 5 | Önceki/sonraki ay | Soluk renkte görünsün |
| 6 | Bugün vurgusu | Yuvarlak, mavi arka plan |
| 7 | Hover efekti | Hücre rengi değişsin |
| 8 | Ay değiştir | Grid güncellensin |
| 9 | Hafta/Gün görünümü | Placeholder + ikon görünsün |
| 10 | Dark mode | Tüm renkler doğru |

---

## Çıktı
✅ Responsive, dinamik aylık takvim gridi
