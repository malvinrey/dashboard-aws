# 🔧 Dokumentasi Lengkap Perbaikan Nginx & PHP Configuration

## 📋 **Ringkasan Masalah yang Ditemukan**

Aplikasi SCADA Dashboard mengalami beberapa masalah kritis yang menyebabkan:

1. **Warning PHP "Module already loaded"** saat menjalankan PHP-CGI
2. **API endpoint `/api/aws/receiver` tidak berfungsi** dan mengembalikan halaman HTML dashboard
3. **Laravel routing tidak bekerja** dengan benar
4. **Konfigurasi Nginx yang terlalu kompleks** dan membingungkan

## 🎯 **Akar Masalah yang Ditemukan**

### 1. **Duplikasi Ekstensi PHP di `C:\php\php.ini`**

File `C:\php\php.ini` memiliki ekstensi yang di-declare **dua kali** dengan format berbeda:

```ini
; Format 1 (tanpa .dll)
extension=openssl
extension=mbstring
extension=fileinfo
extension=pdo_mysql
extension=curl

; Format 2 (dengan .dll) - DUPLIKASI!
extension=php_openssl.dll
extension=php_mbstring.dll
extension=php_fileinfo.dll
extension=php_pdo_mysql.dll
extension=php_curl.dll
```

**Dampak:** PHP memuat modul yang sama dua kali, menyebabkan warning "Module is already loaded"

### 2. **Konfigurasi Nginx yang Terlalu Kompleks**

File `nginx/config/nginx.conf` memiliki:

-   Multiple `location` blocks yang tumpang tindih
-   Blok `/api/` dan `/` yang bisa membingungkan Laravel
-   Path relatif yang tidak konsisten
-   Konfigurasi yang terlalu rumit untuk kebutuhan Laravel

**Dampak:** Laravel bingung tentang URI request yang sebenarnya, kembali ke route default (dashboard)

### 3. **Path File Konfigurasi yang Tidak Konsisten**

-   `mime.types` tidak ditemukan
-   `fastcgi_params` menggunakan path relatif
-   Working directory Nginx tidak jelas

## ✅ **Solusi yang Diterapkan**

### 1. **File PHP-FPM yang Bersih (`php-fpm.ini`)**

Dibuat file konfigurasi PHP baru yang:

-   **Tidak ada duplikasi ekstensi**
-   Hanya ekstensi yang diperlukan
-   Path absolut untuk semua file
-   Konfigurasi yang dioptimalkan untuk Laravel

```ini
[PHP]
; Extensions - hanya yang diperlukan, tanpa duplikasi
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip

; Zend extensions
zend_extension=opcache

; FastCGI specific settings
fastcgi.logging = 1
fastcgi.impersonate = 0
fastcgi.fix_pathinfo = 1
fastcgi.force_redirect = 0
fastcgi.connect_timeout = 300
fastcgi.read_timeout = 300
fastcgi.send_timeout = 300
```

### 2. **Konfigurasi Nginx yang Disederhanakan (`nginx/config/nginx.conf`)**

Dibuat konfigurasi Nginx yang:

-   **Satu `location /` block** yang bersih
-   Path absolut untuk semua file konfigurasi
-   Struktur sederhana sesuai praktik terbaik Laravel
-   Menghilangkan ambiguitas routing

```nginx
# nginx.conf
worker_processes  1;
events {
    worker_connections  1024;
}

http {
    include       "D:/dashboard-aws/mime.types";
    default_type  application/octet-stream;

    # Path log dan temp yang eksplisit
    error_log  logs/error.log;
    access_log logs/access.log;
    client_body_temp_path temp/client_body;
    proxy_temp_path       temp/proxy;
    fastcgi_temp_path     temp/fastcgi;
    uwsgi_temp_path       temp/uwsgi;
    scgi_temp_path        temp/scgi;

    sendfile        on;
    keepalive_timeout  65;

    server {
        listen       80;
        server_name  localhost;
        root         "D:/dashboard-aws/public";
        index        index.php index.html index.htm;

        # Blok location utama untuk semua request
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        # Blok location khusus untuk file PHP
        location ~ \.php$ {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include        "D:/dashboard-aws/nginx/fastcgi_params";
        }

        # Blokir akses ke file .htaccess
        location ~ /\.ht {
            deny all;
        }
    }
}
```

### 3. **File MIME Types (`mime.types`)**

Dibuat file `mime.types` di root folder dengan tipe MIME dasar yang diperlukan untuk aplikasi web.

### 4. **Update Script Batch Files**

Semua script batch diupdate untuk menggunakan file konfigurasi PHP yang bersih:

```batch
; Sebelum (menggunakan php.ini default)
start "PHP-CGI" cmd /k "php-cgi.exe -b 127.0.0.1:9000"

; Sesudah (menggunakan php-fpm.ini yang bersih)
start "PHP-CGI" cmd /k "php-cgi.exe -b 127.0.0.1:9000 -c D:\dashboard-aws\php-fpm.ini"
```

**Script yang diupdate:**

-   `start-nginx.bat`
-   `start-all-services.bat`

## 🚀 **Langkah-langkah Implementasi**

### **Langkah 1: Hentikan Semua Service**

```batch
.\stop-all-services.bat
```

### **Langkah 2: Test Konfigurasi Nginx**

```batch
C:\nginx\nginx.exe -t -c "D:\dashboard-aws\nginx\config\nginx.conf" -p "D:\dashboard-aws"
```

### **Langkah 3: Jalankan Service dengan Konfigurasi Baru**

```batch
.\start-all-services.bat
```

### **Langkah 4: Verifikasi Service Berjalan**

```powershell
# Cek Nginx
tasklist | findstr nginx

# Cek PHP-CGI
tasklist | findstr php-cgi

# Cek port yang digunakan
netstat -an | findstr ":80\|:9000"
```

### **Langkah 5: Test API Endpoint**

```powershell
Invoke-WebRequest -Uri "http://localhost/api/aws/receiver" -Method POST -ContentType "application/json" -Body '{"test": "data"}' -UseBasicParsing
```

## 🔍 **Verifikasi Perbaikan**

### **Sebelum Perbaikan:**

-   ❌ Warning PHP "Module already loaded"
-   ❌ API endpoint mengembalikan halaman HTML dashboard
-   ❌ Laravel routing tidak berfungsi
-   ❌ Konfigurasi Nginx terlalu kompleks

### **Setelah Perbaikan:**

-   ✅ **Tidak ada warning PHP**
-   ✅ **API endpoint mengembalikan response JSON yang valid**
-   ✅ **Laravel routing berfungsi dengan sempurna**
-   ✅ **Konfigurasi Nginx sederhana dan efisien**

## 📁 **Struktur File yang Diperbaiki**

```
dashboard-aws/
├── nginx/
│   ├── config/
│   │   └── nginx.conf          # ✅ Konfigurasi yang disederhanakan
│   └── fastcgi_params          # ✅ Parameter FastCGI
├── php-fpm.ini                 # ✅ File konfigurasi PHP yang bersih
├── mime.types                  # ✅ Tipe MIME yang diperlukan
├── start-nginx.bat             # ✅ Script yang diupdate
├── start-all-services.bat      # ✅ Script yang diupdate
└── stop-all-services.bat       # ✅ Script yang sudah benar
```

## ⚠️ **Peringatan Penting**

1. **Jangan edit file `C:\php\php.ini`** - gunakan `php-fpm.ini` yang sudah dibuat
2. **Jangan hapus file `php-fpm.ini`** - ini adalah konfigurasi utama aplikasi
3. **Pastikan folder `temp/` dan `logs/`** ada sebelum menjalankan service
4. **Gunakan script batch yang sudah diupdate** untuk konsistensi

## 🔧 **Troubleshooting**

### **Jika masih ada warning PHP:**

1. Pastikan `php-fpm.ini` ada dan tidak ada duplikasi ekstensi
2. Restart PHP-CGI dengan parameter `-c` yang benar
3. Periksa apakah ada proses PHP-CGI lama yang masih berjalan

### **Jika API masih tidak berfungsi:**

1. Test konfigurasi Nginx: `nginx.exe -t`
2. Periksa log Nginx di `logs/error.log`
3. Pastikan PHP-CGI berjalan di port 9000
4. Test koneksi: `netstat -an | findstr :9000`

### **Jika Nginx tidak start:**

1. Periksa apakah port 80 sudah digunakan
2. Pastikan semua file konfigurasi ada dengan path yang benar
3. Periksa permission folder dan file

## 📚 **Referensi**

-   [Laravel Documentation - Web Server Configuration](https://laravel.com/docs/web-servers)
-   [Nginx Documentation - FastCGI](https://nginx.org/en/docs/http/fastcgi.html)
-   [PHP Documentation - FastCGI](https://www.php.net/manual/en/install.fpm.php)

## 📝 **Catatan Perubahan**

-   **Tanggal:** 12 Agustus 2025
-   **Versi:** 1.0
-   **Status:** ✅ **SELESAI & BERFUNGSI**
-   **Tester:** User Dashboard AWS
-   **Hasil:** API endpoint `/api/aws/receiver` berfungsi dengan sempurna

---

**Kesimpulan:** Semua masalah telah berhasil diatasi dengan pendekatan yang sistematis dan konfigurasi yang bersih. Aplikasi SCADA Dashboard sekarang berjalan dengan stabil tanpa warning dan routing yang berfungsi sempurna.
