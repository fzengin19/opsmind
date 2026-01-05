# Custom Native Calendar - Geliştirme Planı

Bu belge, OpsMind projesi için sıfırdan özel bir takvim bileşeni geliştirme sürecini adım adım açıklar.

## Genel Yaklaşım

Her adım:
1. **Tek bir sorumluluğa** odaklanır
2. **Bağımsız olarak test edilebilir**
3. Bir önceki adımın **üzerine inşa edilir**
4. Çalışır durumda bırakılır

---

## Geliştirme Adımları

| Adım | Başlık | Açıklama | Tahmini Süre |
|------|--------|----------|--------------|
| 01 | Shell Component | Boş sayfa iskeleti + routing | 15 dk |
| 02 | Toolbar | Navigasyon butonları ve görünüm seçici | 20 dk |
| 03 | Month Grid | Sadece aylık grid (veri olmadan) | 30 dk |
| 04 | Month Data | Service'ten veri çekip grid'e basma | 20 dk |
| 05 | Week Grid Structure | Haftalık görünüm iskeleti (saatler + günler) | 45 dk |
| 06 | Week Event Positioning | Absolute pozisyonlama mantığı | 30 dk |
| 07 | Day View | Tek gün görünümü (Week'in basitleştirilmişi) | 20 dk |
| 08 | Polish & Integration | Responsive, dark mode, final touches | 30 dk |

---

## Detaylı Adım Belgeleri

Her adımın detaylı açıklaması aşağıdaki dosyalarda bulunur:

- [01-shell-component.md](./calendar-dev/01-shell-component.md)
- [02-toolbar.md](./calendar-dev/02-toolbar.md)
- [03-month-grid.md](./calendar-dev/03-month-grid.md)
- [04-month-data.md](./calendar-dev/04-month-data.md)
- [05-week-grid-structure.md](./calendar-dev/05-week-grid-structure.md)
- [06-week-event-positioning.md](./calendar-dev/06-week-event-positioning.md)
- [07-day-view.md](./calendar-dev/07-day-view.md)
- [08-polish-integration.md](./calendar-dev/08-polish-integration.md)

---

## Kurallar

1. **Her adımda sadece o adımın kodunu yaz** - Gelecek adımları düşünme
2. **Her adımdan sonra tarayıcıda test et**
3. **Çalışmayan bir şey varsa o adımda düzelt, sonrakine geçme**
4. **Basit tut** - Overcomplicate etme
