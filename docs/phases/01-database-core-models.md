# Faz 1: Database & Core Models

**Süre:** 3-4 gün  
**Önkoşul:** Yok (ilk faz)  
**Çıktı:** Veritabanı temeli, modeller, DTO'lar

---

## Amaç

PostgreSQL veritabanı şeması, Eloquent modelleri, Spatie Data DTO'ları ve Action class yapısını kurmak.

---

## Görevler

### 1.1 PostgreSQL Kurulumu

- [ ] PostgreSQL kurulumu (local veya Docker)
- [ ] `.env` güncelle:
  ```env
  DB_CONNECTION=pgsql
  DB_HOST=127.0.0.1
  DB_PORT=5432
  DB_DATABASE=opsmind
  DB_USERNAME=postgres
  DB_PASSWORD=secret
  ```
- [ ] Veritabanı oluştur: `createdb opsmind`

### 1.2 Paket Kurulumları

- [ ] Spatie Data (DTO'lar için):
  ```bash
  composer require spatie/laravel-data
  ```

- [ ] Spatie Permission (roller için):
  ```bash
  composer require spatie/laravel-permission
  php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
  ```

- [ ] API Routes Klasör Yapısı Oluştur:
  ```bash
  mkdir -p routes/api/v1
  touch routes/api/v1/appointments.php
  touch routes/api/v1/contacts.php
  touch routes/api/v1/tasks.php
  ```

- [ ] `routes/api/api.php` (version router):
  ```php
  <?php
  
  // V1 Routes
  Route::prefix('v1')->group(function () {
      require base_path('routes/api/v1/appointments.php');
      require base_path('routes/api/v1/contacts.php');
      require base_path('routes/api/v1/tasks.php');
  });
  ```

### 1.3 Migration Dosyaları

- [ ] `create_companies_table`
  ```
  id, name, slug, logo, timezone, settings (jsonb), timestamps
  ```

- [ ] `create_departments_table`
  ```
  id, company_id (FK), name, parent_id (self-ref), timestamps
  ```

- [ ] `add_fields_to_users_table`
  ```
  + company_id (FK), department_id (FK), avatar, phone, job_title, timezone
  ```

- [ ] `create_contacts_table`
  ```
  id, company_id, type (enum), first_name, last_name, email, phone, 
  company_name, job_title, notes (text), tags (jsonb), created_by, timestamps
  ```

- [ ] `create_appointments_table`
  ```
  id, company_id, title, description (text), type (enum), start_at, end_at, 
  all_day, location, color, google_calendar_id, created_by, timestamps
  ```

- [ ] `create_appointment_attendees_table`
  ```
  id, appointment_id (FK), user_id (nullable), contact_id (nullable), 
  status (enum), created_at
  ```

- [ ] `create_tasks_table`
  ```
  id, company_id, title, description (text), status (enum), priority (enum), 
  due_date, estimated_hours (decimal), assignee_id (FK), 
  contact_id (nullable), appointment_id (nullable), position (int), 
  created_by, timestamps
  ```

- [ ] `create_task_comments_table`
  ```
  id, task_id (FK), user_id (FK), body (text), timestamps
  ```

### 1.4 Eloquent Models

Her model için:
- `$fillable` tanımla
- `casts()` metodu (enum, json, datetime)
- İlişkileri tanımla (`belongsTo`, `hasMany`, `belongsToMany`)
- `$with` property (eager loading defaults)

| Model | İlişkiler |
|-------|-----------|
| `Company` | hasMany: users, departments, contacts, appointments, tasks |
| `Department` | belongsTo: company, parent; hasMany: children, users |
| `User` | belongsTo: company, department; hasMany: createdAppointments, assignedTasks |
| `Contact` | belongsTo: company, createdBy; belongsToMany: appointments (attendees) |
| `Appointment` | belongsTo: company, createdBy; belongsToMany: users, contacts |
| `Task` | belongsTo: company, assignee, contact, appointment; hasMany: comments |
| `TaskComment` | belongsTo: task, user |

### 1.5 PHP 8 Enums

```php
// app/Enums/ContactType.php
enum ContactType: string {
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Partner = 'partner';
    case Lead = 'lead';
}
```

- [ ] `ContactType` (customer, vendor, partner, lead)
- [ ] `AppointmentType` (meeting, call, focus, break)
- [ ] `TaskStatus` (backlog, todo, in_progress, review, done)
- [ ] `TaskPriority` (low, medium, high, urgent)
- [ ] `AttendeeStatus` (pending, accepted, declined)

### 1.6 Spatie Data DTO'ları

DTO'lar validasyon kurallarını içerir, Livewire ile uyumlu olmalı:

```php
// app/Data/AppointmentData.php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Concerns\WireableData;
use Livewire\Wireable;

class AppointmentData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        #[Required, Max(100)]
        public string $title,
        public AppointmentType $type,
        public Carbon $start_at,
        public Carbon $end_at,
        public ?string $description = null,
        public ?string $location = null,
        public bool $all_day = false,
    ) {}
}
```

- [ ] `CompanyData`
- [ ] `ContactData`
- [ ] `AppointmentData`
- [ ] `TaskData`
- [ ] `TaskCommentData`

### 1.7 Factories & Seeders

- [ ] Her model için Factory
- [ ] `RoleSeeder` - 3 rol oluştur (admin, manager, staff)
- [ ] `DatabaseSeeder`:
  - 1 demo şirket
  - 5 kullanıcı (1 admin, 2 manager, 2 staff)
  - 20 kişi (contact)
  - 30 randevu (appointment)
  - 50 görev (task)

---

## Doğrulama

```bash
# Migration
php artisan migrate:fresh

# Seeder
php artisan db:seed

# Test
php artisan tinker
>>> Company::with('users', 'contacts', 'appointments', 'tasks')->first()
>>> AppointmentData::from(Appointment::first())
```

---

## Dosya Listesi

```
database/
├── migrations/
│   ├── xxxx_create_companies_table.php
│   ├── xxxx_create_departments_table.php
│   ├── xxxx_add_fields_to_users_table.php
│   ├── xxxx_create_contacts_table.php
│   ├── xxxx_create_appointments_table.php
│   ├── xxxx_create_appointment_attendees_table.php
│   ├── xxxx_create_tasks_table.php
│   └── xxxx_create_task_comments_table.php
├── factories/
│   ├── CompanyFactory.php
│   ├── DepartmentFactory.php
│   ├── ContactFactory.php
│   ├── AppointmentFactory.php
│   ├── TaskFactory.php
│   └── TaskCommentFactory.php
└── seeders/
    ├── RoleSeeder.php
    └── DatabaseSeeder.php

app/
├── Models/
│   ├── Company.php
│   ├── Department.php
│   ├── Contact.php
│   ├── Appointment.php
│   ├── AppointmentAttendee.php
│   ├── Task.php
│   └── TaskComment.php
├── Enums/
│   ├── ContactType.php
│   ├── AppointmentType.php
│   ├── TaskStatus.php
│   ├── TaskPriority.php
│   └── AttendeeStatus.php
└── Data/
    ├── CompanyData.php
    ├── ContactData.php
    ├── AppointmentData.php
    ├── TaskData.php
    └── TaskCommentData.php
```

---

## Mimari Kurallar

> Bu kurallar tüm fazlarda geçerlidir:

1. **Class-based Volt** kullanılacak (functional değil)
2. **DTO'lar validasyonun tek kaynağı** - Volt'ta duplicate validasyon yok
3. **Action classes** karmaşık iş mantığı için (Faz 5'ten itibaren)
4. **PostgreSQL JSONB** - `settings`, `tags` gibi esnek alanlar için
5. **Eager loading** - N+1 sorgu problemi önlenir
6. **API Versioning** - Tüm endpoint'ler `/api/v1/` prefix'i ile

### API Klasör Yapısı

```
routes/
├── api/
│   ├── v1/
│   │   ├── appointments.php
│   │   ├── contacts.php
│   │   └── tasks.php
│   └── api.php
└── web.php
```

### 🚨 FAZ GEÇİŞ KURALI

> **Bir fazın testleri geçmeden sonraki faza geçilmez!**

```bash
php artisan test --filter=FazAdi
# Testler yeşil değilse faz tamamlanmamıştır.
```

---

## Notlar

- Tüm tablolarda `company_id` ile multi-tenant izolasyonu sağlanır
- `google_calendar_id` Faz 6'da Google Calendar sync için kullanılacak
- `position` sütunu Kanban sürükle-bırak sıralaması için
- Spatie Data DTO'lar Wireable olmalı (Livewire uyumu için)
