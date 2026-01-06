# OpsMind MVP - Phase Documentation

Bu klasör OpsMind MVP'nin 11 fazını detaylı olarak içerir.

## Fazlar

| # | Faz | Süre | Durum |
|---|-----|------|-------|
| 01 | [Database & Core Models](01-database-core-models.md) | 3 gün | ✅ |
| 02 | [Auth & Roles](02-auth-roles.md) | 4 gün | ✅ |
| 02.1 | [Spatie Teams Refactor](02.1-spatie-teams-refactor.md) | 2 gün | ⬜ |
| 03 | [Dashboard Skeleton](03-dashboard-skeleton.md) | 2 gün | ⬜ |
| 04 | [Calendar UI](04-calendar-ui.md) | 4 gün | 🟡 |
| **04.5** | **[Calendar Entity](04.5-calendar-entity.md)** | **1-2 gün** | ⬜ |
| 05 | [Appointment CRUD](05-appointment-crud.md) | 3 gün | ⬜ |
| 06 | [Google Calendar Sync](06-google-calendar-sync.md) | 4 gün | ⬜ |
| 07 | [Contact Management](07-contact-management.md) | 4 gün | ⬜ |
| 08 | [Kanban Task Board](08-kanban-task-board.md) | 5 gün | ⬜ |
| 09 | [Notifications](09-notifications.md) | 3 gün | ⬜ |
| 10 | [Polish & Testing](10-polish-testing.md) | 4 gün | ⬜ |

**Toplam:** ~40 iş günü (8-9 hafta)


## Durum Açıklamaları

- ⬜ Başlamadı
- 🟡 Devam Ediyor
- ✅ Tamamlandı

## 🚨 Faz Geçiş Kuralı

> **KURAL:** Bir fazın testleri geçmeden bir sonraki faza geçilmez!

Her fazın sonunda:
```bash
php artisan test --filter=FazAdi
```

Testler **yeşil** değilse faz tamamlanmış sayılmaz.

---

## API Versioning Yapısı

API endpoint'leri versiyonlu:

```
routes/
├── api/
│   ├── v1/
│   │   ├── appointments.php
│   │   ├── contacts.php
│   │   └── tasks.php
│   └── api.php (version router)
└── web.php
```

**Kullanım:**
```php
// routes/api/api.php
Route::prefix('v1')->group(base_path('routes/api/v1/appointments.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/contacts.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/tasks.php'));
```

**Endpoint formatı:**
```
/api/v1/appointments
/api/v1/contacts
/api/v1/tasks
```

---

## Model İsimlendirmesi

| Kavram | Model Adı | Neden? |
|--------|-----------|--------|
| Takvim randevusu | `Appointment` | `Event` Laravel'de rezerve |
| Takvim (container) | `Calendar` | Çoklu takvim desteği |
| Yapılacak iş | `Task` | `Job` queue ile çakışır |
| Kişi/Müşteri | `Contact` | `User` login ile karışır |
| Dosyalar | `Document` | `File` PHP core ile çakışır |

