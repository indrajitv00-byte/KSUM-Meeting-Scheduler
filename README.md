# KSUM Meeting Portal

Book Zoom meetings in seconds: pick a time and the portal finds a free Zoom host account for you.
Built on the TALL stack (Laravel 13, Livewire, Filament, Tailwind) with SQLite.

## Features

**For everyone**
- Book a meeting (title, date/time, duration, department, participants, agenda)
- Automatic free-account matching, lowest *priority* number first, with a 10 minute buffer after each meeting
- When every account is busy: up to 5 free slots nearby, one click to book one
- Join link + passcode, "Copy invitation", **Start as host** (fresh Zoom start link every click)
- My meetings: Upcoming / Past / Cancelled, cancel a meeting, download cloud recordings
- Confirmation email, and a reminder email 30 minutes before the meeting
- Log in with email + password, Google, or Startup Mission ID

**For admins** (sidebar > Admin)
- Overview: active accounts, meetings today, upcoming, requests with no free slot, usage per account
- Zoom accounts: add a host (verified with Zoom), change priority, enable / disable
- Day schedule: every account on one 8:00-20:00 timeline
- All meetings: filter, start, cancel, recordings
- Users and Roles (permission based, Spatie)

## Setup

```bash
composer install
cp .env.example .env          # then edit it (see below)
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed           # permissions, roles (super / admin / user) and the first admin
npm install && npm run build
php artisan serve
```

### 1. Zoom (required)

Create a **Server-to-Server OAuth** app at https://marketplace.zoom.us (Develop > Build App) and activate it.
Scopes: `meeting:write:meeting:admin`, `meeting:read:meeting:admin`, `user:read:user:admin`,
`cloud_recording:read:list_recording_files:admin`.

```env
ZOOM_ACCOUNT_ID=...
ZOOM_CLIENT_ID=...
ZOOM_CLIENT_SECRET=...
```

Then log in as admin > **Zoom accounts** > *Add a host* with the email of a licensed Zoom user.
Add one host per Zoom licence; the portal books on whichever is free.

### 2. First administrator

Set `ADMIN_EMAIL` (and optionally `ADMIN_NAME`, `ADMIN_PASSWORD`) in `.env` before `php artisan db:seed`.
With no `ADMIN_PASSWORD` a random one is printed once.

### 3. Sign-in options (optional)

- **Google**: set `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`; authorised redirect URI = `APP_URL/google/callback`.
- **Startup Mission ID**: set `UID_KEY` / `UID_SECRET`; redirect URI = `APP_URL/uid/callback`.
- The buttons only appear when configured. First-time sign-ins get the `user` role.
  Limit who can self-register with `PORTAL_ALLOWED_DOMAINS=startupmission.in`.

### 4. Emails and reminders

Set the `MAIL_*` values (the default `log` mailer only writes emails to `storage/logs`).
Reminders need the Laravel scheduler running: add this cron entry, or run `php artisan schedule:work`.

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Roles

| Role  | Can do |
|-------|--------|
| user  | book, view and cancel own meetings |
| admin | + admin pages, add/edit Zoom accounts, start/cancel/recordings of anyone's meetings |
| super | everything |

Permissions live in `config/perms.php`; run `php artisan db:seed --class=PermissionSeeder` after changing them.

## How it works

| Piece | Where |
|-------|-------|
| Booking rules (duration, buffer, working hours, reminder time) | `config/portal.php` |
| Free-account search, booking, cancel, recordings, add host | `app/Services/MeetingService.php` |
| Zoom REST calls | `app/Services/Zoom/ZoomClient.php` |
| Pages | `app/Livewire/Meeting`, `app/Livewire/Admin` |
| Reminder job | `app/Console/Commands/SendMeetingReminders.php` |

All times use `APP_TIMEZONE` (default `Asia/Kolkata`).
Two people can't get the same slot: the free-account check and the reservation run under a cache lock
(`Cache::lock`), so keep `CACHE_STORE` on `database`, `redis` or `file` (not `array`) in production.

Run the tests with `php artisan test`.

---

## Development (Docker / Sail)

```
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

```
sail up
```



## Deployment

build the docker for the project

```
docker build -t my-laravel-app .
```

in the deployment server create the following files

### nginx.conf

```
server {
    listen 80;
    server_name _;
    root /var/www/public;

    index index.php index.html index.htm;
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Dockerfile

```
# New Nginx image to copy assets
FROM nginx:latest as nginx
COPY --from=my-laravel-app /var/www/public /var/www/public
COPY nginx.conf /etc/nginx/conf.d/default.conf
```

### docker-compose.yml

```
version: '3.8'

services:
  nginx:
    build:
      context: .
      dockerfile: Dockerfile
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - laravel

  app:
    image: my-laravel-app
    restart: unless-stopped
    networks:
      - laravel
    volumes:
      - .env:/var/www/.env
    depends_on:
      - db
      - redis
      - queue
      - scheduler

  queue:
    image: my-laravel-app  # Use the same Laravel app image
    restart: unless-stopped
    networks:
      - laravel
    depends_on:
      - redis
    command: ["php", "artisan", "queue:work"]  

  scheduler:
    image: my-laravel-app  # Use the same Laravel app image
    restart: unless-stopped
    networks:
      - laravel
    command: ["sh", "-c", "while true; do php artisan schedule:work; sleep 60; done"]  # Run scheduler in a loop

  redis:
    image: redis:alpine
    restart: unless-stopped
    networks:
      - laravel

  db:
    image: postgres:15
    restart: unless-stopped
    networks:
      - laravel
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db-data:/var/lib/postgresql/data

volumes:
  db-data:

networks:
  laravel:
    driver: bridge

```

### .env

add env file as required.
