# Adım 05: Week Grid Structure (Haftalık Görünüm Yapısı)

## Hedef
Haftalık görünümün **yapısını** oluşturmak. Bu adımda **henüz event yok**, sadece:
- 7 gün sütunu (Pzt-Paz)
- Sol tarafta saat etiketleri (00:00 - 23:00)
- Yatay saat çizgileri

---

## Mevcut Durum Analizi

### Tamamlanan Adımlar
| Adım | Durum | İçerik |
|------|-------|--------|
| 01 | ✅ | Shell + Route |
| 02 | ✅ | Toolbar + State + URL sync |
| 03 | ✅ | Month Grid (responsive) |
| 04 | ✅ | Month Data (03 ile birleştirildi) |

### Mevcut Kod Yapısı
- **index.blade.php**: 164 satır
  - Satır 61-68: `with()` metodu - **GÜNCELLENECEK**
  - Satır 150-157: Week/Day placeholder - **DEĞİŞTİRİLECEK**
- **CalendarService.php**: 94 satır
  - `getWeekGrid()`: Satır 43-58 ✅ HAZIR
  - `getTimeSlots()`: Satır 64-71 ✅ HAZIR

---

## Design System Uyumluluk Kontrol Listesi

| Kural | Referans | Uygulama |
|-------|----------|----------|
| **Card Container** | DS 2.2 | Mevcut container korunacak |
| **Header Background** | DS 1.3 | `bg-zinc-50 dark:bg-zinc-900/50` |
| **Border** | DS 2.2 | `border-zinc-200 dark:border-zinc-700` |
| **Text Colors** | DS 1.3 | Primary: `text-zinc-900`, Secondary: `text-zinc-500`, Tertiary: `text-zinc-400` |
| **Sticky Header** | DS 5.4 | `sticky top-0 z-30` |
| **Hover** | DS 4.2 | `hover:bg-zinc-50 dark:hover:bg-zinc-700/30` |

---

## Kritik Mimari Kararlar

### 1. Flex vs Grid
**Seçim: Flexbox**
- Sol sütun (saatler): Sabit genişlik `w-14 sm:w-16`
- Sağ alan (günler): `flex-1` ile kalan alanı kaplar
- Günler kendi içinde `flex` ile eşit bölünür

### 2. Scroll Davranışı
- Container: `overflow-hidden` (taşma yok)
- Body: `overflow-y-auto` (dikey scroll)
- Header: `sticky top-0` (sabit kalır)
- Saat sütunu: Body ile birlikte scroll

### 3. Yükseklik Hesabı
- 1 saat = `60px` (sabit)
- 24 saat = `1440px` toplam yükseklik
- Container: `h-[600px]` (görünen alan)
- Scroll ile 08:00 civarına odaklanılacak (Adım 08'de)

---

## Responsive Tasarım

| Özellik | Mobile (< 640px) | Desktop (≥ 640px) |
|---------|------------------|-------------------|
| Saat sütunu genişliği | `w-14` (56px) | `w-16` (64px) |
| Saat font size | `text-[10px]` | `text-xs` |
| Gün başlığı padding | `py-2` | `py-3` |
| Gün numarası | `text-base` | `text-lg` |
| Container height | `h-[500px]` | `h-[600px]` |

---

## Yapılacaklar

### 1. with() Metodunu Güncelle (Satır 61-68)

```php
    public function with(CalendarService $service): array
    {
        $days = match($this->view) {
            'month' => $service->getMonthGrid($this->currentDate),
            'week', 'day' => $service->getWeekGrid($this->currentDate),
            default => [],
        };

        $timeSlots = in_array($this->view, ['week', 'day']) 
            ? $service->getTimeSlots() 
            : [];

        return compact('days', 'timeSlots');
    }
```

### 2. Week View HTML (Satır 150-157'yi değiştir)

`@else` bloğunu aşağıdakiyle tamamen değiştir:

```html
        @elseif($view === 'week')
            {{-- Week View Container --}}
            <div class="flex h-[500px] sm:h-[600px]">
                
                {{-- Left: Time Column --}}
                <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                    {{-- Empty corner (aligns with header) --}}
                    <div class="h-14 sm:h-16 border-b border-zinc-200 dark:border-zinc-700"></div>
                    
                    {{-- Time Labels (scrolls with body) --}}
                    <div class="overflow-hidden">
                        @foreach($timeSlots as $slot)
                            <div class="h-[60px] relative">
                                <span class="absolute -top-2.5 right-2 text-[10px] sm:text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ $slot }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                {{-- Right: Days Area --}}
                <div class="flex-1 flex flex-col overflow-hidden">
                    
                    {{-- Header: Day Names (sticky) --}}
                    <div class="flex border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 sticky top-0 z-30">
                        @foreach($days as $day)
                            <div class="flex-1 py-2 sm:py-3 text-center border-r border-zinc-200 dark:border-zinc-700 last:border-r-0">
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">
                                    {{ $day['dayName'] }}
                                </div>
                                <div class="text-base sm:text-lg font-semibold {{ $day['isToday'] ? 'text-primary-600 dark:text-primary-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ $day['day'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    {{-- Body: Time Grid (scrollable) --}}
                    <div class="flex-1 flex overflow-y-auto">
                        @foreach($days as $day)
                            <div class="flex-1 border-r border-zinc-100 dark:border-zinc-700/50 last:border-r-0">
                                @foreach($timeSlots as $slot)
                                    <div class="h-[60px] border-b border-dotted border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition"></div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    
                </div>
            </div>
        @else
            {{-- Day View Placeholder --}}
            <div class="p-4 sm:p-6 text-center">
                <flux:icon name="calendar" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-3" />
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    Günlük görünüm Adım 07'de eklenecek.
                </flux:text>
            </div>
        @endif
```

---

## Kod Açıklamaları

| Satır/Öğe | Açıklama | DS Referansı |
|-----------|----------|--------------|
| `h-[500px] sm:h-[600px]` | Responsive container yüksekliği | - |
| `w-14 sm:w-16` | Saat sütunu genişliği (responsive) | - |
| `flex-shrink-0` | Saat sütunu küçülmez | - |
| `h-14 sm:h-16` | Boş köşe (header ile hizalama) | - |
| `-top-2.5` | Saat etiketi yukarı kaydırma (çizgi hizası) | - |
| `text-[10px] sm:text-xs` | Saat etiketi font (responsive) | DS 3 |
| `sticky top-0 z-30` | Sticky header | DS 5.4 |
| `bg-zinc-50 dark:bg-zinc-900/50` | Header arka plan | DS 1.3 |
| `border-dotted` | Saat çizgileri (kesikli) | - |
| `last:border-r-0` | Son sütunda sağ border yok | - |

---

## Önemli Notlar

1. **Scroll Senkronizasyonu:** Bu basit implementasyonda saat sütunu ve gün sütunları ayrı scroll'a sahip olabilir. Bu sorun Adım 08'de çözülecek.

2. **Event Yok:** Bu adımda event rendering yok. Sadece yapı.

3. **Day View:** Henüz placeholder. Adım 07'de Week view'ın basitleştirilmiş hali olarak eklenecek.

---

## Doğrulama

| # | Test | Beklenen Sonuç |
|---|------|----------------|
| 1 | Hafta görünümüne geç | 7 gün sütunu görünsün |
| 2 | Gün başlıkları | "Pzt, Sal, Çar..." üstte |
| 3 | Gün numaraları | 1-7 arası sayılar |
| 4 | Bugün vurgusu | Mavi renk ile vurgulu |
| 5 | Saat etiketleri | 00:00 - 23:00 sol tarafta |
| 6 | Yatay çizgiler | Her saat için kesikli çizgi |
| 7 | Dikey scroll | 24 saat görüntülenebilir |
| 8 | Header sticky | Scroll'da başlık sabit kalsın |
| 9 | Hover efekti | Hücre arka planı değişsin |
| 10 | Dark mode | Tüm renkler doğru |
| 11 | Mobile | Dar ama okunabilir |
| 12 | Ay görünümüne dön | Month grid çalışmaya devam etsin |

---

## Çıktı
✅ Haftalık görünüm yapısı (event'siz, responsive)
