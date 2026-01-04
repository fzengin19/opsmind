# OpsMind Design System v2.0 (Pixel-Perfect Standard)

> Bu doküman geliştirme sürecindeki **TEK GERÇEK KAYNAĞIDIR**. Burada yazmayan hiçbir stil deseni kullanılamaz.

---

## 1. Renk Mimarisi (Color Architecture)

### 1.1 Primary Brand Scale (OKLCH)
OpsMind marka kimliği için özel olarak üretilmiş renk skalasıdır.

```css
@theme {
    /* Brand Colors - OKLCH format for wider gamut */
    --color-primary-50:  oklch(0.97 0.02 250);
    --color-primary-100: oklch(0.93 0.04 250);
    --color-primary-200: oklch(0.85 0.08 250);
    --color-primary-300: oklch(0.75 0.12 250);
    --color-primary-400: oklch(0.65 0.14 250);
    --color-primary-500: oklch(0.55 0.15 250); /* Base Brand Color */
    --color-primary-600: oklch(0.48 0.15 250);
    --color-primary-700: oklch(0.40 0.15 250);
    --color-primary-800: oklch(0.32 0.12 250);
    --color-primary-900: oklch(0.25 0.10 250);
}
```

### 1.2 Semantic Colors (Anlamsal Renkler)
Her durum için `light` ve `dark` varyasyonları **zorunludur**.

| Token | Light (Default) | Dark Mode | Kullanım |
|-------|-----------------|-----------|----------|
| `success` | `oklch(0.65 0.15 145)` | `oklch(0.70 0.12 145)` | Başarılı işlem, onay, artış. |
| `warning` | `oklch(0.75 0.15 85)` | `oklch(0.80 0.12 85)` | Bekleyen işlem, dikkat gerektiren. |
| `danger` | `oklch(0.60 0.20 25)` | `oklch(0.65 0.18 25)` | Silme, hata, kritik uyarı. |
| `info` | `oklch(0.60 0.15 230)` | `oklch(0.65 0.12 230)` | Bilgilendirme metinleri. |

### 1.3 Neutral Scale (Nötr Skala)
Projede **ASLA** `gray`, `slate`, `neutral` renkleri kullanılmayacaktır. Sadece **`zinc`** kullanılacaktır.

- **Backgrounds:**
    - Light Page: `bg-zinc-50`
    - Light Card: `bg-white`
    - Dark Page: `dark:bg-zinc-900`
    - Dark Card: `dark:bg-zinc-800`
- **Text:**
    - Primary: `text-zinc-900` / `dark:text-zinc-50`
    - Secondary: `text-zinc-600` / `dark:text-zinc-400`
    - Tertiary: `text-zinc-400` / `dark:text-zinc-500`

---

## 2. Layout & Spacing Standartları

### 2.1 Global Layout Yapısı
Eski `py-12`, `max-w-7xl` yapıları **YASAKLANMIŞTIR**. Flux layout yapısı kullanılacaktır.

```blade
<!-- Standart Sayfa Yapısı -->
<div class="flex flex-col gap-6"> <!-- Global Page Gap: 24px (gap-6) -->

    <!-- 1. Page Header (Zorunlu) -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $pageTitle }}</flux:heading>
            <flux:subheading>{{ $pageDescription }}</flux:subheading>
        </div>
        <div class="flex items-center gap-3">
            <!-- Action Buttons -->
        </div>
    </div>

    <!-- 2. Content Area -->
    <div class="grid grid-cols-1 gap-6">
        <!-- Components -->
    </div>
</div>
```

### 2.2 Card Anatomisi (Panel)
Tüm paneller (formlar, widgetlar) bu standartta olmalıdır.

- **Padding:** Desktop `p-6`, Mobile `p-4`
- **Border:** `border border-zinc-200 dark:border-zinc-700`
- **Radius:** `rounded-xl`
- **Shadow:** `shadow-sm` (Sadece light mode'da belirgindir)
- **Background:** `bg-white dark:bg-zinc-800`

```blade
<div class="p-4 sm:p-6 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm">
    <!-- Card Content -->
</div>
```

### 2.3 Spacing Scale (Boşluk Skalası)

| Token | Piksel | Kullanım Alanı |
|-------|--------|----------------|
| `gap-2` | 8px | Buton içi ikon-metin arası, çok sıkı gruplamalar. |
| `gap-3` | 12px | Form label-input arası, buton grupları. |
| `gap-4` | 16px | Card içi section arası, form field arası. |
| `gap-6` | 24px | Ana grid boşlukları, section arası boşluklar (Standart). |
| `gap-8` | 32px | Tematik bölümler arası (örn: Settings'de farklı konular). |

---

## 3. Tipografi ve İkonografi

### 3.1 Başlık Hiyerarşisi
Flux Heading component'leri kullanılacak, manuel `h1`, `h2` etiketleri yasaktır.

| Component | Eşdeğer | Kullanım |
|-----------|---------|----------|
| `<flux:heading size="xl">` | `text-2xl font-semibold` | Sayfa Ana Başlığı |
| `<flux:heading size="lg">` | `text-xl font-semibold` | Section / Modal Başlığı |
| `<flux:heading size="base">` | `text-base font-medium` | Card Başlığı |

### 3.2 İkon Seti (Heroicons via Flux)
- **Tüm ikonlar:** `flux:icon` veya `heroicons` seti kullanılacak.
- **Boyut:** Standart metin içi ikonlar `size-4` veya `size-5`.
- **Renk:** Genellikle `text-zinc-500`, aktif durumda `text-primary-500` veya semantic renkler.

---

## 4. Component Davranışları

### 4.1 Butonlar
- **Primary:** Sayfanın ana aksiyonu (Kaydet, Oluştur). `variant="primary"`
- **Secondary:** İptal, Geri, Filtreleme. `variant="ghost"` veya `variant="outline"`
- **Destructive:** Silme işlemleri. `variant="danger"`

### 4.2 Tablolar
- Başlıklar: `text-xs font-medium uppercase tracking-wider text-zinc-500`
- Satır Yüksekliği: `py-3` (Compact değil, ferah)
- Zebra Striping: **Kullanılmayacak**. Sadece `hover:bg-zinc-50` efekti.

### 4.3 Formlar
- Grup başlıkları: `<flux:separator>` ile ayrılmalı.
- Buton hizalaması: Her zaman sağ alt köşe (desktop) veya tam genişlik (mobile).
- Validasyon mesajları: Input hemen altında.

---

## 5. Özel Kurallar ve Yasaklar

1.  **Strict Neutrality:** `text-gray-*` kullanımı kesinlikle yasaktır. Kodda `grep` ile aranıp `text-zinc-*` yapılmalıdır.
2.  **Container Width:** `max-w-7xl mx-auto` gibi manuel sınırlamalar yerine Flux'ın ana container yapısına güvenilecektir. Gerekirse `max-w` wrapper en dışta bir kez verilir.
3.  **Legacy Code:** `py-12` class'ı (Laravel Breeze varsayılanı) silinecek. Standart `gap-6` akışı bozar.
4.  **Z-Index:**
    - Dropdown/Menu: `z-50`
    - Modal: `z-40`
    - Sticky Headers: `z-30`
    - Default: `z-0`

---
