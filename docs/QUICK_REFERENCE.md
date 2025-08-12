# 🚀 Quick Reference - SCADA Dashboard

## ⚡ **Start Services (Cepat)**

```batch
.\start-all-services.bat
```

## 🛑 **Stop Services (Cepat)**

```batch
.\stop-all-services.bat
```

## 🔍 **Check Service Status**

```powershell
# Cek Nginx
tasklist | findstr nginx

# Cek PHP-CGI
tasklist | findstr php-cgi

# Cek ports
netstat -an | findstr ":80\|:9000"
```

## 🧪 **Test API Endpoint**

```powershell
Invoke-WebRequest -Uri "http://localhost/api/aws/receiver" -Method POST -ContentType "application/json" -Body '{"test": "data"}' -UseBasicParsing
```

## ⚠️ **Jika Ada Masalah**

### **Warning PHP "Module already loaded"**

-   Pastikan menggunakan `php-fpm.ini` (bukan `C:\php\php.ini`)
-   Restart PHP-CGI dengan parameter `-c D:\dashboard-aws\php-fpm.ini`

### **API tidak berfungsi**

-   Test Nginx config: `C:\nginx\nginx.exe -t -c "D:\dashboard-aws\nginx\config\nginx.conf" -p "D:\dashboard-aws"`
-   Cek log: `logs/error.log`

### **Service tidak start**

-   Pastikan folder `temp/` dan `logs/` ada
-   Cek apakah port 80/9000 sudah digunakan

## 📁 **File Penting**

-   `php-fpm.ini` - Konfigurasi PHP (JANGAN DIHAPUS!)
-   `nginx/config/nginx.conf` - Konfigurasi Nginx
-   `start-all-services.bat` - Script utama

## 🔧 **Manual Start (Jika Script Bermasalah)**

```powershell
# 1. Start PHP-CGI
Start-Process -FilePath "C:\php\php-cgi.exe" -ArgumentList "-b", "127.0.0.1:9000", "-c", "D:\dashboard-aws\php-fpm.ini"

# 2. Start Nginx
C:\nginx\nginx.exe -c "D:\dashboard-aws\nginx\config\nginx.conf" -p "D:\dashboard-aws"
```

---

**📚 Dokumentasi Lengkap:** `docs/NGINX_PHP_FIX_COMPLETE.md`
