# SEO 80/20 System

**80% Automatic · 20% Manual**

## Quick Setup

### Run with Docker

Requirements: Docker Engine and the Docker Compose plugin.

1. Copy `.env.example` to `.env` and add any API keys you use.
2. Start the app:

   ```bash
   docker compose up --build -d
   ```

3. Open [http://localhost:8080](http://localhost:8080). Set `APP_PORT` in `.env`
   to use a different host port.

The SQLite database, uploads, logs, and `config.local.php` are stored in named
Docker volumes and remain available when the container is recreated. The
database is initialized automatically on first use. Configure API keys in `.env`
or through the app's settings. A fresh database uses the application's default
admin credentials (`admin` / `admin123`); change the password immediately and
before exposing the app publicly. Browser-based Selenium posting still requires
its separate Python/browser environment or PC agent.

### 1. Database
```sql
-- Import database.sql in phpMyAdmin or run:
mysql -u root -p < database.sql
```

### 2. Config
Edit `config.php` (database only). API keys go in **API Keys** page or `config.local.php`:
```php
define('DB_HOST', '127.0.0.1:3307');  // XAMPP MySQL port
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'seo_system');
```
Copy `config.local.php.example` → `config.local.php` OR use **api-setup.php** after login.

**Required API:** [OpenAI ChatGPT](https://platform.openai.com/api-keys) (`sk-...`)  
**Optional:** [DataForSEO](https://app.dataforseo.com) rank

Open `setup.php` in browser to verify installation.

### 3. Upload
Upload all files to your server's `seo-system/` folder.

### 4. Register & Login
Go to `http://yourdomain.com/seo-system/register.php`

### 5. Add Project
- Website URL: your site
- Target Keyword: e.g., "power bi training in btm"
- Target Site: site to analyze

### 6. Run SEO
Click **"Run All SEO"** button — system does 80% automatically!

---

## Cron Jobs Setup

```bash
# Daily rank check at 9 AM
0 9 * * * php /path/to/seo-system/cron-daily.php

# Weekly report every Monday at 9 AM
0 9 * * 1 php /path/to/seo-system/cron-weekly.php
```

---

## What's 80% Auto vs 20% Manual

| Feature | 80% Auto | 20% Manual |
|---------|----------|------------|
| Backlinks | Prepares 35+ sites with instructions | You submit on each site |
| On-Page SEO | Fetches & analyzes your site | You apply the fixes |
| Rank Tracking | Checks Google rank daily | Nothing needed |
| Keywords | Fetches 100+ from Google Suggest | You select which to target |
| Competitors | Finds & analyzes top 5 | You review & act |
| Content | Generates 5 SEO articles | You edit & publish |
| Excel Report | Auto-generates full report | One-click download |

---

## Files

```
seo-system/
├── config.php          # Database & settings
├── database.sql        # DB schema
├── index.php           # Login
├── register.php        # Register
├── logout.php          # Logout
├── dashboard.php       # Main dashboard
├── add-project.php     # Add new project
├── seo-80-20.php       # Main SEO hub
├── backlink-system.php # 35+ backlink opportunities
├── onpage-analyzer.php # On-page SEO checker
├── rank-tracker.php    # Google rank tracking
├── keyword-research.php# Google Suggest keywords
├── competitor-analysis.php # Top 5 competitors
├── content-generator.php   # 5 SEO articles
├── export-excel.php    # CSV/Excel export
├── cron-daily.php      # Daily rank check
├── cron-weekly.php     # Weekly report
├── style.css           # Styles
├── script.js           # JavaScript
├── .htaccess           # Security & performance
└── includes/
    └── navbar.php      # Navigation
```
