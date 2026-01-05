# Adım 05.1: Week Grid Bug Fixes

## Tespit Edilen Sorunlar

### Sorun 1: Saat Sütunu Scroll Senkronizasyonu
**Açıklama:** Sol taraftaki saat etiketleri sabit kalıyor, sağ taraftaki gün sütunları scroll olduğunda saatler yerinde kalıyor. Yatayda hizasızlık oluşuyor.

**Kök Neden:** 
- Sol saat sütunu (`w-14 sm:w-16`) ayrı bir div içinde ve `overflow-hidden` ile sabitlenmiş
- Sağ body (`overflow-y-auto`) scroll oluyor ama sol sütun bağımsız

**Olması Gereken:**
- Saat etiketleri ve sağdaki hücreler birlikte scroll etmeli

---

### Sorun 2: Scrollbar Header Hizasızlığı
**Açıklama:** Sağ tarafta scrollbar göründüğünde, header ile body arasında 1-2px kaymа oluşuyor. Header'daki sütun sağ çizgileri body'deki sütunlarla tam hizalı değil.

**Kök Neden:**
- Body'de `overflow-y-auto` scrollbar için yer ayrılıyor (~15-17px)
- Header'da scrollbar yok, dolayısıyla header biraz daha geniş
- Bu genişlik farkı sütunların kaymasına neden oluyor

**Olması Gereken:**
- Header ve Body aynı genişlikte olmalı
- Scrollbar alanı her ikisinde de hesaplanmalı

---

## Çözüm Stratejisi

### Yaklaşım: Birleşik Scroll Container

Mevcut yapı:
```
┌─────────────────────────────────────┐
│ Time Column │ Days Area             │
│ (no scroll) │ ┌──────────────────┐  │
│             │ │ Header (no scroll)│  │
│             │ ├──────────────────┤  │
│             │ │ Body (scroll)    │  │
│             │ └──────────────────┘  │
└─────────────────────────────────────┘
```

Yeni yapı:
```
┌─────────────────────────────────────┐
│ ┌─────────────────────────────────┐ │
│ │ Header Row (Time + Days)        │ │ ← Sabit
│ ├─────────────────────────────────┤ │
│ │ Scrollable Body (Time + Days)  │ │ ← Birlikte scroll
│ └─────────────────────────────────┘ │
└─────────────────────────────────────┘
```

---

## Yapılacaklar

### Yeni Week View HTML

```html
@elseif($view === 'week')
    {{-- Week View Container --}}
    <div class="flex flex-col h-[500px] sm:h-[600px]">
        
        {{-- Header Row (Fixed) --}}
        <div class="flex border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 flex-shrink-0">
            {{-- Time Column Header (Empty Corner) --}}
            <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700"></div>
            
            {{-- Day Headers --}}
            <div class="flex-1 flex">
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
        </div>
        
        {{-- Scrollable Body (Time + Days together) --}}
        <div class="flex-1 flex overflow-y-auto">
            
            {{-- Time Column --}}
            <div class="w-14 sm:w-16 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                @foreach($timeSlots as $slot)
                    <div class="h-[60px] relative">
                        <span class="absolute -top-2.5 right-2 text-[10px] sm:text-xs text-zinc-400 dark:text-zinc-500">
                            {{ $slot }}
                        </span>
                    </div>
                @endforeach
            </div>
            
            {{-- Days Grid --}}
            <div class="flex-1 flex">
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
```

---

## Değişikliklerin Özeti

| Sorun | Çözüm |
|-------|-------|
| Saat sütunu scroll etmiyor | Time column ve Days grid **aynı scroll container içine** alındı |
| Header-body hizasızlığı | Header artık scroll alanının **dışında**, sabit |
| Scrollbar kayması | Header ve body'nin scroll alanı artık **ayrı** - header scrollbar'dan etkilenmiyor |

---

## Doğrulama

| # | Test | Beklenen Sonuç |
|---|------|----------------|
| 1 | Hafta görünümünde scroll et | Saatler ve gün hücreleri birlikte scroll etsin |
| 2 | Header konumu | Header sabit kalsın, scroll ile hareket etmesin |
| 3 | Header-body hizası | Gün sütunları tam hizalı olsun, kayma yok |
| 4 | Scrollbar görünümü | Body'de scrollbar göründüğünde header etkilenmesin |
| 5 | Saat etiketleri | Her saat çizgisi ile aynı hizada olsun |

---

## Çıktı
✅ Scroll senkronizasyonu + Header hizalaması düzeltildi
