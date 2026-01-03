# Faz 2: Authentication & Role Management

**Süre:** 4 gün  
**Önkoşul:** Faz 1 (Database & Models)  
**Çıktı:** Güvenli giriş sistemi ve rol tabanlı yetkilendirme

---

## Amaç

Laravel built-in auth kullanarak email/password ve Google OAuth ile giriş, şirket bazlı kullanıcı yönetimi ve Spatie Permission ile rol tabanlı yetkilendirme.

---

## Görevler

### 2.1 Mevcut Auth Kontrolü

> Projede Laravel built-in auth mevcut. Fortify veya Breeze yerine bunu kullanacağız.

- [ ] Mevcut auth yapısını incele
- [ ] `routes/auth.php` kontrol et
- [ ] Login/Register view'larını tespit et

### 2.2 Google OAuth (Socialite)

- [ ] Kurulum:
  ```bash
  composer require laravel/socialite
  ```

- [ ] Google Cloud Console:
  - Project oluştur
  - OAuth 2.0 credentials
  - Authorized redirect URI: `http://localhost/auth/google/callback`

- [ ] `.env` güncelle:
  ```env
  GOOGLE_CLIENT_ID=xxx
  GOOGLE_CLIENT_SECRET=xxx
  GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
  ```

- [ ] `config/services.php` güncelle
- [ ] `SocialiteController` oluştur:
  ```php
  // Actions/Auth/HandleGoogleCallbackAction.php
  class HandleGoogleCallbackAction
  {
      public function execute(): User
      {
          $googleUser = Socialite::driver('google')->user();
          
          return User::firstOrCreate(
              ['email' => $googleUser->email],
              [
                  'name' => $googleUser->name,
                  'google_id' => $googleUser->id,
                  'avatar' => $googleUser->avatar,
              ]
          );
      }
  }
  ```

### 2.3 Spatie Permission Konfigürasyonu

> Faz 1'de kurulum yapıldı. Şimdi rolleri tanımlayacağız.

- [ ] `RoleSeeder` güncelle:
  ```php
  Role::create(['name' => 'admin']);
  Role::create(['name' => 'manager']);
  Role::create(['name' => 'staff']);
  ```

| Rol | Yetkiler |
|-----|----------|
| **admin** | Tüm yetkiler, şirket ayarları, kullanıcı yönetimi, silme |
| **manager** | Tüm CRUD, takım görünürlüğü, atama yapabilir |
| **staff** | Sadece kendi kaynakları, atanan görevler |

### 2.4 Kayıt Akışı (Company + User)

- [ ] `RegisterUserData` DTO:
  ```php
  class RegisterUserData extends Data implements Wireable
  {
      use WireableData;
      
      public function __construct(
          #[Required, Max(100)]
          public string $name,
          #[Required, Email]
          public string $email,
          #[Required, Min(8)]
          public string $password,
          #[Required, Max(100)]
          public string $company_name,
      ) {}
  }
  ```

- [ ] `CreateCompanyWithAdminAction`:
  ```php
  class CreateCompanyWithAdminAction
  {
      public function execute(RegisterUserData $data): User
      {
          return DB::transaction(function () use ($data) {
              $company = Company::create([
                  'name' => $data->company_name,
                  'slug' => Str::slug($data->company_name),
              ]);
              
              $user = User::create([
                  'name' => $data->name,
                  'email' => $data->email,
                  'password' => Hash::make($data->password),
                  'company_id' => $company->id,
              ]);
              
              $user->assignRole('admin');
              
              return $user;
          });
      }
  }
  ```

### 2.5 Kullanıcı Davet Sistemi

- [ ] `invitations` migration:
  ```
  id, company_id, email, role, token, expires_at, accepted_at, timestamps
  ```

- [ ] `Invitation` model

- [ ] `InvitationData` DTO:
  ```php
  class InvitationData extends Data
  {
      public function __construct(
          #[Required, Email]
          public string $email,
          #[Required, In(['manager', 'staff'])]
          public string $role,
      ) {}
  }
  ```

- [ ] `SendInvitationAction`
- [ ] `AcceptInvitationAction`
- [ ] `InviteUserNotification` (Markdown email)

### 2.6 Middleware

- [ ] `EnsureCompanyAccess` middleware:
  ```php
  class EnsureCompanyAccess
  {
      public function handle(Request $request, Closure $next): Response
      {
          // User'ın company_id'si ile resource company_id eşleşmeli
          // Global scope olarak da eklenebilir
      }
  }
  ```

- [ ] `bootstrap/app.php` middleware kaydı
- [ ] Route gruplarına uygula

### 2.7 Auth Sayfaları (Livewire Volt + Flux UI)

**Tüm auth sayfaları Class-based Volt component olacak.**

- [ ] `resources/views/livewire/auth/login.blade.php`:
  ```php
  new class extends Component {
      public string $email = '';
      public string $password = '';
      public bool $remember = false;
      
      public function login(): void
      {
          // Auth::attempt...
      }
  }
  ```
  - Email/password form (Flux input)
  - Google ile giriş butonu
  - Şifremi unuttum linki
  - Remember me checkbox

- [ ] `resources/views/livewire/auth/register.blade.php`:
  - Name, Email, Password, Company Name
  - `CreateCompanyWithAdminAction` kullan

- [ ] `resources/views/livewire/auth/forgot-password.blade.php`
- [ ] `resources/views/livewire/auth/reset-password.blade.php`
- [ ] `resources/views/livewire/auth/verify-email.blade.php`

### 2.8 Takım Yönetimi Sayfası (Class-based Volt)

- [ ] `resources/views/livewire/team/index.blade.php`:
  - Kullanıcı listesi (Flux table)
  - Davet gönder modal (Flux modal)
  - Rol değiştirme (Flux select)
  - Kullanıcı silme (soft delete)
  - Sadece admin erişebilir

---

## Doğrulama

```bash
# Testler
php artisan test --filter=Auth
php artisan test --filter=Team

# Tinker kontrol
php artisan tinker
>>> User::first()->hasRole('admin')
>>> User::first()->company
```

Manuel test:
1. Email ile kayıt ol → Şirket + Admin user oluşsun
2. Dashboard'a yönlen
3. Çıkış yap → Google ile giriş
4. Admin olarak Takım sayfasına git
5. Yeni kullanıcı davet et (manager rolü)
6. Davet maili gelsin
7. Davet linkine tıkla → Kayıt ol → Aynı şirkete eklen
8. Staff kullanıcı takım sayfasına erişemesin

---

## Dosya Listesi

```
app/
├── Actions/
│   └── Auth/
│       ├── CreateCompanyWithAdminAction.php
│       ├── HandleGoogleCallbackAction.php
│       ├── SendInvitationAction.php
│       └── AcceptInvitationAction.php
├── Data/
│   ├── RegisterUserData.php
│   └── InvitationData.php
├── Http/
│   ├── Controllers/
│   │   └── Auth/
│   │       └── SocialiteController.php
│   └── Middleware/
│       └── EnsureCompanyAccess.php
├── Models/
│   └── Invitation.php
└── Notifications/
    └── InviteUserNotification.php

resources/views/livewire/
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   ├── reset-password.blade.php
│   └── verify-email.blade.php
└── team/
    └── index.blade.php

database/migrations/
└── xxxx_create_invitations_table.php

routes/
└── auth.php (güncellenecek)
```

---

## Güvenlik Notları

- [ ] CSRF koruması aktif (Livewire otomatik)
- [ ] Rate limiting: login route'a `throttle:5,1`
- [ ] Password hash: bcrypt (Laravel default)
- [ ] Session timeout: 2 saat
- [ ] Soft delete: kullanıcı silindiğinde `deleted_at` set
- [ ] Invitation token: 48 saat geçerli, tek kullanımlık
