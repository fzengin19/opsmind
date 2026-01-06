# Step 1: Frontend Setup & Configuration (Livewire 3 + Flux + Vite)

Bu proje **Livewire 3 + Flux UI** kullanıyor. Alpine.js `@fluxScripts` direktifi ile otomatik yükleniyor. Bu nedenle klasik Laravel projelerinden farklı bir yaklaşım gerekli.

## 📦 1. NPM Paketi Kurulumu

```bash
npm install @toast-ui/calendar@2.1.3
```

## 📁 2. Dosya Yapısı (Oluşturulacaklar)

Projenizde şu an `resources/js/` dizini **boş**. Aşağıdaki dosyaları oluşturmalıyız:

```
resources/js/
├── app.js                      # Ana giriş noktası (Vite tarafından bekleniyor)
└── services/
    └── calendar-manager.js     # TOAST UI wrapper sınıfı
```

## 🛠 3. resources/js/app.js (YENİ DOSYA)

Bu dosya projenizde **mevcut değil**, oluşturulacak. Vite config (`vite.config.js` satır 10) bu dosyayı bekliyor.

```javascript
/**
 * OpsMind - Main JavaScript Entry Point
 * 
 * NOT: Alpine.js Livewire/Flux tarafından otomatik yükleniyor (@fluxScripts).
 * Bu dosyada sadece özel modüllerimizi window'a expose ediyoruz.
 */

// TOAST UI Calendar Manager'ı import et ve global yap
import CalendarManager from './services/calendar-manager';

// Alpine.js bu sınıfa erişebilmesi için window'a ata
window.CalendarManager = CalendarManager;
```

## 🛠 4. resources/js/services/calendar-manager.js (YENİ DOSYA)

```javascript
import Calendar from '@toast-ui/calendar';

/**
 * TOAST UI Calendar için wrapper sınıfı.
 * Alpine.js x-data içinde kullanılacak.
 */
export default class CalendarManager {
    constructor(element, options = {}) {
        this.element = element;
        this.instance = null;
        this.options = options;
        
        // Callback'ler
        this.onUpdate = null;
        this.onSelect = null;
        this.onClick = null;
    }

    init() {
        if (this.instance) return this.instance;

        const config = {
            defaultView: 'week',
            useCreationPopup: false,
            useDetailPopup: false,
            usageStatistics: false,
            isReadOnly: false,
            week: {
                startDayOfWeek: 1, // Pazartesi
                taskView: false,
                eventView: ['time'],
            },
            month: {
                startDayOfWeek: 1,
            },
            ...this.options,
        };

        this.instance = new Calendar(this.element, config);
        this.attachEvents();
        
        return this.instance;
    }

    attachEvents() {
        this.instance.on('beforeUpdateEvent', (e) => {
            if (this.onUpdate) this.onUpdate(e);
        });
        
        this.instance.on('selectDateTime', (e) => {
            if (this.onSelect) this.onSelect(e);
        });
        
        this.instance.on('clickEvent', (e) => {
            if (this.onClick) this.onClick(e);
        });
    }

    updateEvents(events) {
        if (!this.instance) return;
        this.instance.clear();
        this.instance.createEvents(events);
    }
    
    next() { this.instance?.next(); }
    prev() { this.instance?.prev(); }
    today() { this.instance?.today(); }
    changeView(view) { this.instance?.changeView(view); }
    
    getDateRange() {
        if (!this.instance) return { start: new Date(), end: new Date() };
        return {
            start: this.instance.getDateRangeStart().toDate(),
            end: this.instance.getDateRangeEnd().toDate()
        };
    }
    
    destroy() {
        if (this.instance) this.instance.destroy();
    }
}
```

## 🎨 5. resources/css/app.css (GÜNCELLEME)

Mevcut `app.css` dosyanızın **EN BAŞINA** TOAST UI CSS'ini ekleyin:

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';

/* TOAST UI Calendar CSS - node_modules'tan Vite tarafından çözümlenir */
@import '@toast-ui/calendar/dist/toastui-calendar.min.css';

/* ... mevcut @source, @theme vb. direktifler ... */
```

Ardından dosyanın **SONUNA** dark mode override'larını ekleyin:

```css
/* ===== TOAST UI Calendar Dark Mode Overrides ===== */

.dark .toastui-calendar-layout {
    background-color: var(--color-zinc-900);
}

.dark .toastui-calendar-grid-cell,
.dark .toastui-calendar-timegrid-gridline,
.dark .toastui-calendar-day-names {
    border-color: var(--color-zinc-800) !important;
}

.dark .toastui-calendar-day-name-item {
    color: var(--color-zinc-400) !important;
}

.dark .toastui-calendar-timegrid-time-label {
    color: var(--color-zinc-500) !important;
}

.toastui-calendar-timegrid-current-time-line-past,
.toastui-calendar-timegrid-current-time-line-future {
    border-color: var(--color-danger);
}

.dark .toastui-calendar-popup-container {
    background-color: var(--color-zinc-800);
    border-color: var(--color-zinc-700);
    color: var(--color-zinc-200);
}
```

## 🌍 6. lang/tr/calendar.php (YENİ DOSYA)

```php
<?php

return [
    'title' => 'Takvim',
    'view_modes' => [
        'month' => 'Ay',
        'week' => 'Hafta',
        'day' => 'Gün',
        'agenda' => 'Ajanda',
    ],
    'buttons' => [
        'today' => 'Bugün',
        'prev' => 'Önceki',
        'next' => 'Sonraki',
    ],
    'messages' => [
        'no_events' => 'Bu tarih aralığında planlanmış etkinlik yok.',
    ],
    'labels' => [
        'new_event' => 'Yeni Etkinlik',
        'title' => 'Başlık',
        'start' => 'Başlangıç',
        'end' => 'Bitiş',
        'description' => 'Açıklama',
        'location' => 'Konum',
        'type' => 'Tür',
        'all_day' => 'Tüm gün',
    ],
];
```

## ✅ Doğrulama Adımları

1. `npm install` çalıştır
2. `npm run dev` çalıştır  
3. Tarayıcı konsolunda `window.CalendarManager` yaz
4. **Başarı:** Sınıf tanımını görmeli
5. **Hata:** `undefined` ise import yollarını kontrol et

## ⚠️ Kritik Notlar

- **Alpine.js ayrıca import edilmemeli** - Flux/Livewire zaten sağlıyor
- **`@fluxScripts`** direktifi Alpine'ı enjekte ediyor (sidebar.blade.php satır 142)
- **CSS import** Tailwind v4 syntax'ı ile uyumlu olmalı
