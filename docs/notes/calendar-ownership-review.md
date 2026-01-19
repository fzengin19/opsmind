# Calendar Ownership - Değerlendirme Notları

**Tarih:** 2026-01-20  
**İlgili Faz:** 5.6 Calendar CRUD, 6 Google Calendar Sync

---

## Mevcut Durum

Team/Resource takvimi oluşturulduğunda, oluşturan kullanıcı `calendar_user` pivot tablosunda `role: owner` olarak atanıyor.

## Potansiyel Sorunlar

1. **Çalışan ayrılığı:** Kişi şirketten ayrılırsa Team takvimi kalır ama "sahibi" olmaz
2. **Kaynak sahipliği:** Meeting Room gibi şirket kaynağı kişisel sahiplik ne kadar mantıklı?
3. **Google Sync:** Google Workspace'te shared calendar'lar organizasyon sahipliğinde

## Alternatifler

| Seçenek | Açıklama | Avantaj | Dezavantaj |
|---------|----------|---------|------------|
| A. Mevcut | Creator = pivot owner | Basit, çalışıyor | Sahiplik konsepti belirsiz |
| B. Pivot owner kaldır | Sadece admin/owner yönetsin | Temiz | Esneklik azalır |
| C. created_by field | Oluşturanı izle, sahiplik değil | Audit trail | Yeni migration |

## Karar

**Şimdilik Seçenek A ile devam.** Admin/Owner zaten policy üzerinden her şeyi kontrol edebiliyor. Faz 6 (Google Sync) geldiğinde bu yapıyı yeniden değerlendirmek mantıklı.

## TODO (Faz 6)

- [ ] Google Calendar'da shared calendar ownership modeli araştır
- [ ] calendar_user pivot yapısını gözden geçir
- [ ] Gerekirse `created_by` field ekle
