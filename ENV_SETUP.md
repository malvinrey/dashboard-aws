# Environment Setup Guide

## File .env yang Bersih

File `env.clean` berisi konfigurasi environment yang bersih dan lengkap untuk SCADA Dashboard.

## Cara Penggunaan

1. **Copy file env.clean ke .env:**

    ```bash
    cp env.clean .env
    ```

2. **Generate APP_KEY:**

    ```bash
    php artisan key:generate
    ```

3. **Update konfigurasi database sesuai kebutuhan:**

    - `DB_HOST` - Host database (default: 127.0.0.1)
    - `DB_PORT` - Port database (default: 3306)
    - `DB_DATABASE` - Nama database (default: scada_dashboard)
    - `DB_USERNAME` - Username database (default: scada_user)
    - `DB_PASSWORD` - Password database (default: scada_password_2024)

4. **Update konfigurasi SCADA:**
    - `SCADA_DATA_INTERVAL` - Interval data dalam detik (default: 5)
    - `SCADA_MAX_RECORDS` - Maksimal record yang disimpan (default: 10000)
    - `SCADA_CACHE_TTL` - Cache TTL dalam detik (default: 300)
    - `SCADA_REALTIME_ENABLED` - Enable realtime updates (default: true)
    - `SCADA_WEBSOCKET_ENABLED` - Enable WebSocket connections (default: true)

## Konfigurasi yang Sudah Dihapus

-   Emoticon dan simbol yang tidak perlu
-   Konfigurasi yang tidak digunakan
-   Pengaturan yang berlebihan

## Konfigurasi yang Dipertahankan

-   Konfigurasi database MySQL yang lengkap
-   Pengaturan Laravel standar
-   Konfigurasi SCADA khusus
-   Pengaturan untuk realtime dan WebSocket

## Setelah Setup

1. Jalankan migrasi database:

    ```bash
    php artisan migrate
    ```

2. Jalankan aplikasi:

    ```bash
    php artisan serve
    ```

3. Atau gunakan startup scripts yang sudah dibuat:
    ```bash
    scripts/startup/start-all-services.bat
    ```
