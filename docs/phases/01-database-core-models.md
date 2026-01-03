# Faz 1: Database & Core Models ✅

**Süre:** 3-4 gün  
**Önkoşul:** Yok (ilk faz)  
**Çıktı:** Veritabanı temeli, modeller, DTO'lar  
**Durum:** ✅ TAMAMLANDI

---

## Amaç

PostgreSQL/SQLite veritabanı şeması, Eloquent modelleri, Spatie Data DTO'ları ve temel yapıyı kurmak.

---

## Mimari Kararlar

### 🚨 ÖNEMLİ: company_user Pivot Tablosu

> User-Company ilişkisi `users.company_id` FK yerine **pivot tablo** ile yönetilir.

```
company_user:
  user_id, company_id, role, department_id, job_title, joined_at
```

**Gerekçe:**
- Daha normalize yapı
- Multi-company'ye geçiş kolay
- "Şirketsiz user" durumu daha temiz
- Role bilgisi pivot'ta tutulur (CompanyRole enum)

---

## Tamamlanan Görevler

### 1.1 Veritabanı Kurulumu ✅
- SQLite kullanılıyor (PostgreSQL'e migrate edilebilir)
- Veritabanı yapılandırması hazır

### 1.2 Paket Kurulumları ✅
- `spatie/laravel-data` v4.18 kuruldu
- `spatie/laravel-permission` v6.24 kuruldu
- API routes klasör yapısı oluşturuldu (`routes/api/v1/`)

### 1.3 Migration Dosyaları ✅

| Migration | Açıklama |
|-----------|----------|
| `create_companies_table` | Firma tablosu (name, slug, settings JSONB) |
| `create_departments_table` | Departmanlar (self-ref hierarchy) |
| `add_fields_to_users_table` | avatar, phone, timezone, google_id |
| `create_company_user_table` | **Pivot: user-company ilişkisi + role** |
| `create_contacts_table` | CRM kontakları |
| `create_appointments_table` | Randevular |
| `create_appointment_attendees_table` | Randevu katılımcıları |
| `create_tasks_table` | Kanban görevleri |
| `create_task_comments_table` | Görev yorumları |

### 1.4 Eloquent Models ✅

| Model | İlişkiler |
|-------|-----------|
| `Company` | belongsToMany: users (pivot) / hasMany: departments, contacts, appointments, tasks |
| `Department` | belongsTo: company, parent / hasMany: children |
| `User` | belongsToMany: companies (pivot) / hasMany: contacts, appointments, tasks |
| `Contact` | belongsTo: company, createdBy / belongsToMany: appointments |
| `Appointment` | belongsTo: company, createdBy / hasMany: attendees, tasks |
| `Task` | belongsTo: company, assignee, createdBy / hasMany: comments |
| `TaskComment` | belongsTo: task, user |

### 1.5 PHP 8 Enums ✅

| Enum | Değerler |
|------|----------|
| `CompanyRole` | owner, admin, manager, member |
| `ContactType` | customer, vendor, partner, lead |
| `AppointmentType` | meeting, call, focus, break |
| `TaskStatus` | backlog, todo, in_progress, review, done |
| `TaskPriority` | low, medium, high, urgent |
| `AttendeeStatus` | pending, accepted, declined |

### 1.6 Spatie Data DTOs ✅

- `CompanyData` (Wireable)
- `ContactData` (Wireable)
- `AppointmentData` (Wireable)
- `TaskData` (Wireable)
- `TaskCommentData` (Wireable)

### 1.7 Factories & Seeders ✅

**Factories:**
- CompanyFactory
- DepartmentFactory
- ContactFactory
- AppointmentFactory
- TaskFactory
- TaskCommentFactory
- UserFactory (`forCompany($company, $role)` state ile)

**Seeders:**
- RoleSeeder (admin, manager, member + 18 permissions)
- DatabaseSeeder (demo company, 3 users, 20 contacts, 15 appointments, 50 tasks)

---

## Test Sonuçları

```
Tests: 38 passed (79 assertions)
Duration: 0.47s
```

---

## Dosya Yapısı

```
database/
├── migrations/
│   ├── create_companies_table.php
│   ├── create_departments_table.php
│   ├── add_fields_to_users_table.php
│   ├── create_company_user_table.php      # ← YENİ: Pivot tablo
│   ├── create_contacts_table.php
│   ├── create_appointments_table.php
│   ├── create_appointment_attendees_table.php
│   ├── create_tasks_table.php
│   └── create_task_comments_table.php
├── factories/
└── seeders/

app/
├── Models/
│   ├── Company.php    # belongsToMany users, addUser(), removeUser()
│   ├── User.php       # belongsToMany companies, currentCompany(), hasCompany()
│   └── ...
├── Enums/
│   ├── CompanyRole.php  # ← YENİ
│   └── ...
└── Data/
```

---

## Mimari Kurallar

1. **Class-based Volt** kullanılacak (functional değil)
2. **DTO'lar validasyonun tek kaynağı**
3. **Pivot tablo** user-company ilişkisi için (users.company_id YOK)
4. **CompanyRole enum** pivot'ta role için
5. **Eager loading** - N+1 sorgu problemi önlenir
6. **API Versioning** - `/api/v1/` prefix

### 🚨 FAZ GEÇİŞ KURALI

> Bir fazın testleri geçmeden sonraki faza geçilmez!

```bash
php artisan test tests/Feature/Models
# 38 tests, 79 assertions - TÜM TESTLER GEÇTİ ✅
```
