# 🐳 Docker Deployment Guide - Coolify

Bu dokümantasyon, **OpsMind** Laravel uygulamasının Coolify üzerinde nasıl deploy edileceğini açıklar.

## 📋 Ön Gereksinimler

Coolify'da aşağıdaki kaynakları oluşturmuş olmalısın:

- ✅ **PostgreSQL Database** (external resource)
- ✅ **Redis** (external resource)

## 🏗️ Mimari Özeti

```
┌─────────────────────────────────────┐
│      Single Container (Alpine)      │
├─────────────────────────────────────┤
│  • Nginx (port 80)                  │
│  • PHP-FPM 8.4                      │
│  • Supervisord:                     │
│    ├── Queue Worker (Redis)         │
│    └── Laravel Scheduler (cron)     │
└─────────────────────────────────────┘
          ↓               ↓
    PostgreSQL         Redis
   (Coolify DB)   (Coolify Redis)
```

## 🚀 Coolify Deployment Adımları

### 1. Yeni Proje Oluştur

Coolify'da:
- **New Resource** → **Public Repository** veya **Private Repository**
- Repository URL'ni gir (GitHub/GitLab)
- Branch seç (genellikle `main` veya `production`)

### 2. Build Pack Ayarla

- **Build Pack**: `Dockerfile`
- **Dockerfile Location**: `docker/Dockerfile`
- **Port**: `80`

### 3. Environment Variables (Zorunlu)

Coolify'da aşağıdaki environment variable'ları ekle:

```bash
# App
APP_NAME=OpsMind
APP_ENV=production
APP_KEY=base64:XXXXXXXXX  # php artisan key:generate ile üret
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (Coolify PostgreSQL resource'undan)
DB_CONNECTION=pgsql
DB_HOST=<coolify-postgres-host>
DB_PORT=5432
DB_DATABASE=opsmind
DB_USERNAME=<db-user>
DB_PASSWORD=<db-password>

# Redis (Coolify Redis resource'undan)
REDIS_HOST=<coolify-redis-host>
REDIS_PASSWORD=<redis-password>
REDIS_PORT=6379

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Mail (SMTP ayarlarını ekle)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# AWS S3 (File Storage)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=opsmind-storage
AWS_USE_PATH_STYLE_ENDPOINT=false

# Google OAuth (optional)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Auto Migration (önerilir)
AUTO_MIGRATE=true
```

### 4. Health Check Ayarı

Coolify → **Advanced** → **Health Check**:
- **Health Check Path**: `/up`
- **Health Check Interval**: `30s`
- **Health Check Timeout**: `3s`

### 5. Deploy Et!

**Deploy** butonuna tıkla ve container'ın build olmasını bekle.

---

## 📁 Dizin Yapısı

```
opsmind/
├── docker/
│   ├── Dockerfile           # Multi-stage production image
│   ├── nginx.conf           # Nginx web server config
│   ├── supervisord.conf     # Process manager (FPM, Queue, Scheduler)
│   ├── php.ini              # PHP production settings
│   └── entrypoint.sh        # Container startup script
├── .dockerignore            # Build context optimizasyonu
└── ...
```

---

## 🔧 Teknik Detaylar

### Container İçinde Çalışan Servisler

| Servis | Port | Açıklama |
|--------|------|----------|
| **Nginx** | 80 | Web server (reverse proxy) |
| **PHP-FPM** | 9000 | FastCGI Process Manager |
| **Queue Worker** | - | `php artisan queue:work redis` |
| **Scheduler** | - | `php artisan schedule:run` (her dakika) |

### PHP Extensions

- `pdo_pgsql`, `pgsql` - PostgreSQL desteği
- `redis` (phpredis) - Redis client
- `bcmath`, `gd`, `intl`, `zip` - Laravel gereksinimleri
- `opcache` - Performance optimization
- `pcntl` - Queue worker için process control

### Build Optimizasyonları

- ✅ **Multi-stage build**: Node.js ve Composer ayrı stage'lerde
- ✅ **Asset pre-build**: `npm run build` image içinde
- ✅ **Composer production**: `--no-dev --optimize-autoloader`
- ✅ **OPcache enabled**: PHP bytecode caching
- ✅ **Gzip compression**: Nginx static asset sıkıştırma

---

## 🛠️ Troubleshooting

### Container başlamıyor?

```bash
# Coolify logs'u kontrol et
docker logs <container-name>

# Veya Coolify UI'dan "Logs" sekmesine bak
```

### Database bağlantı hatası?

- PostgreSQL resource'unun **internal hostname**'ini kullan
- Coolify'da PostgreSQL'in aynı network'te olduğundan emin ol

### Queue çalışmıyor?

```bash
# Container içine gir
docker exec -it <container-name> sh

# Queue worker kontrolü
supervisorctl status queue-worker

# Queue restart
supervisorctl restart queue-worker
```

### Scheduler çalışmıyor?

```bash
# Scheduler kontrolü
supervisorctl status scheduler

# Manuel test
php artisan schedule:list
```

---

## 🚨 Önemli Notlar

1. **İlk deployment'ta** `AUTO_MIGRATE=true` ile migration otomatik çalışır
2. **S3 credentials** doğru ayarlı olmalı (file upload için)
3. **APP_KEY** kesinlikle üretilmeli (`php artisan key:generate`)
4. **Session driver** Redis olduğu için Redis bağlantısı kritik
5. **Google OAuth** kullanıyorsan, callback URL'i Google Console'da güncelle

---

## 🔮 Gelecek Güncellemeler

İleride eklenecek özellikler:

- 🔄 **Laravel Reverb**: Real-time WebSocket desteği
- 📊 **Laravel Horizon**: Queue monitoring dashboard
- 🎯 **Redis cache**: Ayrı Redis instance cache için

Bu özellikler eklendiğinde, sadece `composer require` ile kurulup environment variable'lar güncellenecek.

---

## 📞 Yardım

Sorun yaşarsan:
1. Coolify deployment logs'unu kontrol et
2. Container logs'unu incele (`docker logs`)
3. Supervisord status'unu kontrol et (`supervisorctl status`)

**Happy Deploying!** 🚀
