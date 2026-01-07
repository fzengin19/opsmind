# Adım 05.2: Dikey Border Hizalama Sorunu

## Tespit Edilen Sorun

Ekran görüntüsünden görülen:
- Gün sütunları arasındaki dikey çizgiler (border-r) **farklı noktalarda bitiyor**
- Bazı sütunların borderleri daha kısa, bazıları daha uzun
- Özellikle en sol ve en sağ kenarlarda belirgin

---

## Kök Neden Analizi

### Mevcut Yapı (Sorunlu)
```html
<div class="flex-1 flex overflow-y-auto">           <!-- Scrollable body -->
    <div class="w-14 ...">                          <!-- Time column -->
    <div class="flex-1 flex">                       <!-- Days wrapper - SORUN BURADA -->
        @foreach($days)
            <div class="flex-1 border-r ...">       <!-- Day column -->
```

### Sorun
1. **İç flex container** (`flex-1 flex`) row yönünde lay out yapıyor
2. Day column'lar `flex-1` ile **genişlik** paylaşıyor (OK)
3. Ama **yükseklik** için constraint yok
4. Flex children otomatik **en kısa içeriğe** göre boyutlanıyor
5. Time column 24x60px, day column 24x60px - **aynı olmalı** ama CSS rendering farklılıkları oluyor

### Asıl Sorun
`flex` container içinde iç içe `flex` kullanılıyor. Dikey boyutlandırma karışıyor.

---

## Çözüm Stratejisi

### Yaklaşım: Grid Kullanımı

Flex yerine CSS Grid kullanarak tam kontrol sağlayalım:

```
┌─────────────────────────────────────────────┐
│  Grid: grid-cols-[56px_repeat(7,1fr)]       │
│  ┌──────┬──────┬──────┬ ... ┬──────┐        │
│  │ Time │ Pzt  │ Sal  │ ... │ Paz  │        │
│  └──────┴──────┴──────┴ ... ┴──────┘        │
└─────────────────────────────────────────────┘
```

**Avantajları:**
- Tüm sütunlar **aynı yüksekliği paylaşır**
- Dikey borderlar **tam hizalı** olur
- Grid auto-stretch özelliği ile sorun çözülür

---

## Yapılacaklar

### Yeni Scrollable Body Yapısı

Satır 181-206'yı aşağıdakiyle değiştir:

```html
                {{-- Scrollable Body (Grid layout for perfect alignment) --}}
                <div class="flex-1 overflow-y-auto">
                    <div class="grid grid-cols-[56px_repeat(7,1fr)] sm:grid-cols-[64px_repeat(7,1fr)]">
                        
                        {{-- Time Column --}}
                        <div class="border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20 row-span-full">
                            @foreach($timeSlots as $slot)
                                <div class="h-[60px] relative">
                                    <span class="absolute -top-2.5 right-2 text-[10px] sm:text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ $slot }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Day Columns --}}
                        @foreach($days as $day)
                            <div class="border-r border-zinc-100 dark:border-zinc-700/50 last:border-r-0">
                                @foreach($timeSlots as $slot)
                                    <div class="h-[60px] border-b border-dotted border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition"></div>
                                @endforeach
                            </div>
                        @endforeach
                        
                    </div>
                </div>
```

---

## Alternatif Çözüm (Daha Basit)

Grid yerine flex'i düzeltmek için `items-stretch` ve explicit height kullanabiliriz:

```html
                {{-- Scrollable Body --}}
                <div class="flex-1 flex overflow-y-auto items-stretch">
                    
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
                    @foreach($days as $day)
                        <div class="flex-1 border-r border-zinc-100 dark:border-zinc-700/50 last:border-r-0">
                            @foreach($timeSlots as $slot)
                                <div class="h-[60px] border-b border-dotted border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition"></div>
                            @endforeach
                        </div>
                    @endforeach
                    
                </div>
```

**Değişiklikler:**
1. `items-stretch` eklendi - flex children parent height'a esner
2. İç `flex-1 flex` wrapper kaldırıldı - doğrudan day column'lar sibling oldu
3. Daha düz yapı = daha az CSS karmaşıklığı

---

## Tercih Edilen Çözüm

**Alternatif çözüm (basit flex düzeltmesi)** öneririm:
- Daha az değişiklik
- Grid mobile'da komplike olabilir
- `items-stretch` flex'in varsayılanı ama explicit vermek daha güvenli

---

## Doğrulama

| # | Test | Beklenen Sonuç |
|---|------|----------------|
| 1 | Dikey çizgiler | Tüm sütun borderleri **aynı yükseklikte** |
| 2 | Scroll | Time ve days birlikte scroll etsin |
| 3 | Border hizası | Header ve body sütunları tam hizalı |
| 4 | Alt kenara scroll | Tüm borderlar altta aynı noktada bitsin |

---

## Çıktı
✅ Dikey border hizalama düzeltildi
