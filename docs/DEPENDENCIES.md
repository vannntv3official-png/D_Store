# D STORE - Dependencies & Runtime Environment

## Verified Runtimes
- **PHP**: 8.3.36 (cli) (ZTS Visual C++ 2019 x64)
  - Extensions active: `bcmath`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `intl`, `json`, `libxml`, `mbstring`, `mysqlnd`, `openssl`, `pcre`, `PDO`, `pdo_mysql`, `pdo_sqlite`, `Phar`, `session`, `SimpleXML`, `SPL`, `sqlite3`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `zlib`.
- **Composer**: 2.11.1
- **Node.js**: v24.20.0 (LTS compatible)
- **NPM**: 11.19.0
- **Git**: 2.55.0.windows.5
- **Database Engine Target**: MySQL 8.0+ / SQLite (in-memory/file driver fallback for fast isolated testing while MySQL provides concurrency verification).

## Target Application Stack
- **Framework**: Laravel 11.x (Stable release compatible with PHP 8.3)
- **Frontend Stack**: Vue 3 (Composition API, `<script setup lang="ts">`), Inertia.js v2, Vite, Tailwind CSS v3/v4.
- **Iconography**: `lucide-vue-next`
- **Charts**: `chart.js` & `vue-chartjs`
- **Testing**: PHPUnit / Pest for backend tests, Vitest / Playwright for frontend/E2E smoke testing.
