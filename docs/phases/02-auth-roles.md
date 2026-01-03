# Faz 2: Authentication & Role Management

**Süre:** 4 gün  
**Önkoşul:** Faz 1 (Database & Models) ✅  
**Çıktı:** Güvenli giriş sistemi, firma oluşturma, davet sistemi

---

## Amaç

Fortify ile email/password ve Google OAuth girişi, onboarding ile firma oluşturma, davet sistemi ile takım üyesi ekleme.

---

## Mimari Önemli Notlar

### 🚨 Pivot Tablo Kullanımı

> User-Company ilişkisi `company_user` pivot tablosu ile yönetilir.  
> `users.company_id` **YOKTUR**.

```php
// ❌ YANLIŞ (eski plan)
$user->company_id = $company->id;

// ✅ DOĞRU (yeni plan)
$company->addUser($user, CompanyRole::Owner);
```

### Helper Metodlar

```php
$user->hasCompany()           // Şirketi var mı?
$user->currentCompany()       // İlk/aktif şirket
$user->roleIn($company)       // Şirketteki rolü (CompanyRole enum)
$user->isOwnerOf($company)    // Sahip mi?

$company->addUser($user, $role, $departmentId, $jobTitle)
$company->removeUser($user)
$company->owners()            // Owner rolündeki kullanıcılar
$company->admins()            // Owner + Admin
```

---

## Görevler

### 2.1 Mevcut Auth Kontrolü ✅

> Fortify zaten kurulu. Login, Register, Password Reset, 2FA çalışıyor.

- [x] Fortify yapısı mevcut
- [x] Livewire auth views (Flux UI) mevcut
- [x] Rate limiting yapılandırılmış

### 2.2 Onboarding Sistemi (YENİ)

> Register'da firma sorulmaz. Login sonrası şirketsiz kullanıcılar onboarding'e yönlendirilir.

**Akış:**
```
Register → email/password/name (firma YOK) →
Login → hasCompany()=false → /onboarding/create-company →
Firma adı gir → company_user pivot (owner) → /dashboard
```

- [ ] `EnsureHasCompany` middleware
- [ ] `/onboarding/create-company` Volt sayfası
- [ ] `CreateCompanyAction`:
  ```php
  class CreateCompanyAction
  {
      public function execute(User $user, string $name): Company
      {
          $company = Company::create([
              'name' => $name,
              'slug' => Str::slug($name),
          ]);
          
          $company->addUser($user, CompanyRole::Owner);
          
          return $company;
      }
  }
  ```

### 2.3 Google OAuth (Socialite)

- [ ] `composer require laravel/socialite`
- [ ] `config/services.php` güncelle
- [ ] `.env` güncelle:
  ```env
  GOOGLE_CLIENT_ID=xxx
  GOOGLE_CLIENT_SECRET=xxx
  GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
  ```
- [ ] `SocialiteController`:
  ```php
  public function callback(): RedirectResponse
  {
      $googleUser = Socialite::driver('google')->user();
      
      $user = User::firstOrCreate(
          ['email' => $googleUser->email],
          [
              'name' => $googleUser->name,
              'google_id' => $googleUser->id,
              'avatar' => $googleUser->avatar,
              'email_verified_at' => now(),
          ]
      );
      
      Auth::login($user);
      
      // Şirketi yoksa onboarding'e
      if (!$user->hasCompany()) {
          return redirect('/onboarding/create-company');
      }
      
      return redirect('/dashboard');
  }
  ```
- [ ] Login sayfasına Google butonu ekle

### 2.4 Kullanıcı Davet Sistemi

**Akış:**
```
Admin /team'de email + role girer → Invitation oluşur →
Email gider (token) → /invitation/{token} →
Kayıtlı: Login et + accept → Yeni: Register + accept →
company_user pivot'a ekle → /dashboard
```

- [ ] `invitations` migration:
  ```php
  Schema::create('invitations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('company_id')->constrained()->cascadeOnDelete();
      $table->string('email');
      $table->string('role', 20); // CompanyRole enum value
      $table->string('token', 64)->unique();
      $table->timestamp('expires_at');
      $table->timestamp('accepted_at')->nullable();
      $table->foreignId('invited_by')->constrained('users');
      $table->timestamps();
      
      $table->unique(['company_id', 'email']);
  });
  ```

- [ ] `Invitation` model
- [ ] `InvitationData` DTO:
  ```php
  class InvitationData extends Data
  {
      public function __construct(
          #[Required, Email]
          public string $email,
          #[Required]
          public CompanyRole $role,
      ) {}
  }
  ```

- [ ] `SendInvitationAction`
- [ ] `AcceptInvitationAction`:
  ```php
  class AcceptInvitationAction
  {
      public function execute(Invitation $invitation, User $user): void
      {
          $invitation->company->addUser(
              $user,
              CompanyRole::from($invitation->role)
          );
          
          $invitation->update(['accepted_at' => now()]);
      }
  }
  ```

- [ ] `InviteUserNotification` (Markdown email)
- [ ] `/invitation/{token}` sayfası

### 2.5 Middleware

- [ ] `EnsureHasCompany`:
  ```php
  class EnsureHasCompany
  {
      public function handle(Request $request, Closure $next): Response
      {
          if (auth()->check() && !auth()->user()->hasCompany()) {
              return redirect('/onboarding/create-company');
          }
          
          return $next($request);
      }
  }
  ```

- [ ] `bootstrap/app.php` middleware kaydı
- [ ] Dashboard ve diğer authenticated route'lara uygula

### 2.6 Takım Yönetimi Sayfası

- [ ] `/team` Volt sayfası:
  - Kullanıcı listesi (`$company->users()`)
  - Davet modal (email + role)
  - Rol değiştirme (pivot update)
  - Üye çıkarma (`removeUser()`)
  - Pending davetler listesi

- [ ] Yetki kontrolü: Sadece Owner/Admin erişebilir

---

## Edge Cases

| # | Durum | Çözüm |
|---|-------|-------|
| E1 | User zaten firmada, 2. firma açmak istiyor | MVP: HAYIR, hata ver |
| E2 | Admin, başka firmadaki user'ı davet ediyor | MVP: HAYIR, hata ver |
| E3 | Pending davet varken user login | Dashboard'da banner göster |
| E4 | Süresi dolmuş davet | Hata + "Yeni davet isteyin" |
| E5 | Zaten kabul edilmiş davet | Login'e yönlendir |
| E6 | Aynı email'e 2. davet | Eski iptal, yeni oluştur |
| E7 | Owner kendini çıkarmak ister | İzin verme (en az 1 owner) |
| E8 | Google OAuth email = pending invitation | Otomatik kabul et |

---

## Doğrulama

```bash
php artisan test --filter=Auth
php artisan test --filter=Team
php artisan test --filter=Invitation
```

### Manuel Test:
1. Email ile kayıt → Onboarding → Firma oluştur → Dashboard
2. Google ile kayıt → Onboarding → Firma oluştur → Dashboard
3. Admin olarak Team → Davet gönder
4. Davet linki → Kayıt ol → Aynı firmaya katıl
5. Member olarak Team sayfasına erişim dene → Yasak

---

## Dosya Listesi

```
app/
├── Actions/
│   └── Auth/
│       ├── CreateCompanyAction.php
│       ├── SendInvitationAction.php
│       └── AcceptInvitationAction.php
├── Data/
│   └── InvitationData.php
├── Http/
│   ├── Controllers/
│   │   └── Auth/
│   │       └── SocialiteController.php
│   └── Middleware/
│       └── EnsureHasCompany.php
├── Models/
│   └── Invitation.php
└── Notifications/
    └── InviteUserNotification.php

resources/views/livewire/
├── onboarding/
│   └── create-company.blade.php    # YENİ
├── invitation/
│   └── accept.blade.php            # YENİ
└── team/
    └── index.blade.php             # YENİ

database/migrations/
└── create_invitations_table.php
```

---

## Güvenlik Notları

- [x] CSRF koruması aktif (Livewire otomatik)
- [x] Rate limiting: login route `throttle:5,1`
- [x] Password hash: bcrypt (Laravel default)
- [ ] Session timeout: config/session.php
- [ ] Invitation token: 48 saat geçerli, tek kullanımlık
