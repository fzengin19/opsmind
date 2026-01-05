# Adım 06: Week Event Positioning (Haftalık Görünüm Event Yerleştirme)

## Hedef
Haftalık görünüme event kartları eklemek. CSS `absolute` pozisyonlama ile doğru saat/gün hizasına yerleştirmek.

---

## Mevcut Durum Analizi

### Tamamlanan Adımlar
| Adım | Durum | Açıklama |
|------|-------|----------|
| 01 | ✅ | Shell + Route |
| 02 | ✅ | Toolbar + State |
| 03-04 | ✅ | Month Grid (dinamik) |
| 05 | ✅ | Week Grid (scroll + sticky header) |

### Mevcut Week View Yapısı (Satır 159-205)
```
Week Container (h-[500px] overflow-y-auto)
├── Header Row (sticky top-0 z-30)
│   ├── Empty Corner (w-14 sm:w-16)
│   └── Day Headers (flex-1 x7)
└── Body Row (flex)
    ├── Time Column (w-14 sm:w-16)
    │   └── 24 x [h-[60px] time slot]
    └── Day Columns (flex-1 x7)  ← EVENT'LER BURAYA
        └── 24 x [h-[60px] hour cell]
```

### Kritik Satırlar
- **Day Columns Loop:** Satır 196-201
- Mevcut hücre: `<div class="flex-1 border-r ...">`

---

## Design System Uyumluluk

### Event Kart Stili (DS 1.2 Semantic Colors + DS 2.2 Card)
| Özellik | Değer | Referans |
|---------|-------|----------|
| **Border-left** | `border-l-4 border-primary-500` | Vurgu çizgisi |
| **Background** | `bg-primary-100 dark:bg-primary-900/50` | Yarı saydam |
| **Text** | `text-primary-700 dark:text-primary-300` | Kontrast |
| **Radius** | `rounded-md` | DS 2.2 alt-card |
| **Padding** | `px-2 py-1` | Kompakt |
| **Font** | `text-xs font-medium` | Takvim için küçük |
| **Shadow** | `shadow-sm` | Hafif gölge |
| **Z-Index** | `z-10` | Grid çizgilerinin üstünde |

### Renk Varyasyonları
| Tür | Light | Dark |
|-----|-------|------|
| **Primary (Mavi)** | `bg-primary-100 border-primary-500 text-primary-700` | `dark:bg-primary-900/50 dark:text-primary-300` |
| **Success (Yeşil)** | `bg-emerald-100 border-emerald-500 text-emerald-700` | `dark:bg-emerald-900/50 dark:text-emerald-300` |
| **Warning (Sarı)** | `bg-amber-100 border-amber-500 text-amber-700` | `dark:bg-amber-900/50 dark:text-amber-300` |
| **Danger (Kırmızı)** | `bg-rose-100 border-rose-500 text-rose-700` | `dark:bg-rose-900/50 dark:text-rose-300` |

---

## Yapılacaklar

### 1. Dummy Event Verisi Ekle (with() metodu - Satır 61-74)

Mevcut `with()` metodunu güncelle:

```php
    public function with(CalendarService $service): array
    {
        $days = match ($this->view) {
            'month' => $service->getMonthGrid($this->currentDate),
            'week', 'day' => $service->getWeekGrid($this->currentDate),
            default => [],
        };

        $timeSlots = in_array($this->view, ['week', 'day'])
            ? $service->getTimeSlots()
            : [];

        // Dummy events for testing
        $events = [];
        if ($this->view === 'week') {
            $events = [
                [
                    'id' => 1,
                    'title' => 'Ofis Toplantısı',
                    'dayIndex' => 0, // Pazartesi
                    'startHour' => 9,
                    'startMinute' => 30,
                    'durationMinutes' => 90,
                    'color' => 'primary',
                ],
                [
                    'id' => 2,
                    'title' => 'Müşteri Görüşmesi',
                    'dayIndex' => 2, // Çarşamba
                    'startHour' => 14,
                    'startMinute' => 0,
                    'durationMinutes' => 60,
                    'color' => 'success',
                ],
                [
                    'id' => 3,
                    'title' => 'Proje Sunumu',
                    'dayIndex' => 4, // Cuma
                    'startHour' => 11,
                    'startMinute' => 0,
                    'durationMinutes' => 120,
                    'color' => 'warning',
                ],
            ];
        }

        return compact('days', 'timeSlots', 'events');
    }
```

### 2. Day Column'a `relative` Ekle (Satır 197)

Mevcut:
```html
<div class="flex-1 border-r border-zinc-100 dark:border-zinc-700/50 last:border-r-0">
```

Yeni:
```html
<div class="flex-1 border-r border-zinc-100 dark:border-zinc-700/50 last:border-r-0 relative">
```

### 3. Event Render Bloğu Ekle (Saat hücrelerinden SONRA, Day Column içinde)

Satır 200-201 arasına ekle (timeSlots loop'undan sonra, day column kapanmadan önce):

```html
                            {{-- Events --}}
                            @foreach($events as $event)
                                @if($event['dayIndex'] === $loop->parent->index)
                                    @php
                                        $top = ($event['startHour'] * 60) + $event['startMinute'];
                                        $height = max(30, $event['durationMinutes']);
                                    @endphp
                                    <div 
                                        class="absolute inset-x-1 z-10 rounded-md px-2 py-1 text-xs font-medium overflow-hidden cursor-pointer transition-all shadow-sm border-l-4 hover:shadow-md
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
                                        <span class="line-clamp-2">{{ $event['title'] }}</span>
                                    </div>
                                @endif
                            @endforeach
```

---

## Kod Açıklamaları

| Öğe | Açıklama | DS Referansı |
|-----|----------|--------------|
| `absolute` | Event kart, day column içinde mutlak pozisyonlanır | - |
| `inset-x-1` | Sol ve sağdan 4px boşluk | - |
| `z-10` | Grid çizgilerinin üstünde | DS 5.4 |
| `top: {{ $top }}px` | Başlangıç saatine göre pozisyon (saat * 60 + dakika) | - |
| `height: {{ $height }}px` | Süreye göre yükseklik (dakika = pixel) | - |
| `line-clamp-2` | Metin 2 satırdan fazlaysa kes | - |
| `hover:shadow-md` | Hover'da gölge artışı | - |
| `border-l-4` | Sol kenar vurgu çizgisi | Takvim UX standardı |

---

## Doğrulama

| # | Test | Beklenen Sonuç |
|---|------|----------------|
| 1 | Hafta görünümünde 3 event görünsün | ✓ |
| 2 | "Ofis Toplantısı" - Pazartesi 09:30 hizasında | top: 570px (9*60+30) |
| 3 | "Müşteri Görüşmesi" - Çarşamba 14:00 hizasında | top: 840px (14*60) |
| 4 | "Proje Sunumu" - Cuma 11:00 hizasında | top: 660px (11*60) |
| 5 | Event yükseklikleri: 90px, 60px, 120px | ✓ |
| 6 | Hover efekti çalışsın | Gölge artışı |
| 7 | Dark mode renkleri doğru | ✓ |
| 8 | Scroll yapınca eventler yerinde kalsın | Absolute position |
| 9 | Event metni kesilsin (line-clamp) | Uzun başlıklar için |

---

## Çıktı
✅ Event'li haftalık görünüm (3 dummy event)
