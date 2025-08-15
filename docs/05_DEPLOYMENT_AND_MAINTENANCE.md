# 05. Deployment & Maintenance - Production Setup

## 🚀 Deployment Guide

### Overview

SCADA Dashboard dirancang untuk production environment dengan support untuk high availability, monitoring, dan maintenance yang mudah. Sistem ini menggunakan Redis untuk queue management dan Soketi untuk WebSocket server.

### 1. Production Environment Setup

#### 1.1 Server Requirements

```bash
# Minimum Requirements
- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.5+
- Redis 6.0+
- Node.js 18.0+
- Nginx / Apache
- 4GB RAM minimum
- 50GB storage minimum

# Recommended Requirements
- PHP 8.2+
- MySQL 8.0+
- Redis 7.0+
- Node.js 20.0+
- Nginx with PHP-FPM
- 8GB RAM
- 100GB SSD storage
```

#### 1.2 Environment Configuration

```env
# .env.production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scada_dashboard
DB_USERNAME=scada_user
DB_PASSWORD=strong_password_here

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=redis_password_here

# Broadcasting
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=12345
PUSHER_APP_KEY=your_production_key
PUSHER_APP_SECRET=your_production_secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_ENCRYPTED=false

# Queue
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database-uuids

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 2. Service Management

#### 2.1 Production Startup Scripts

```bash
# scripts/start-production-services.ps1
Write-Host "Starting Production SCADA Services..." -ForegroundColor Green

# 1. Start Redis with persistence
Start-Process -FilePath "redis-server" -ArgumentList "--port", "6379", "--save", "900", "1", "--save", "300", "10", "--save", "60", "10000" -WindowStyle Hidden

# 2. Start Soketi with PM2
Start-Process -FilePath "pm2" -ArgumentList "start", "soketi", "--name", "scada-soketi", "--", "start", "--config=soketi.json" -WindowStyle Hidden

# 3. Start Laravel Queue Workers
Start-Process -FilePath "php" -ArgumentList "artisan", "queue:work", "--sleep=3", "--tries=3", "--max-time=3600", "--queue=scada-processing,scada-large-datasets" -WindowStyle Hidden

# 4. Start Laravel with production server
Start-Process -FilePath "php" -ArgumentList "artisan", "serve", "--host=0.0.0.0", "--port=8000" -WindowStyle Hidden

Write-Host "All production services started!" -ForegroundColor Green
```

#### 2.2 PM2 Configuration

```json
// ecosystem.config.js
module.exports = {
  apps: [
    {
      name: 'scada-soketi',
      script: './node_modules/.bin/soketi',
      args: 'start --config=soketi.json',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'production',
        PORT: 6001
      }
    },
    {
      name: 'scada-queue-worker',
      script: 'php',
      args: 'artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=scada-processing,scada-large-datasets',
      instances: 2,
      autorestart: true,
      watch: false,
      max_memory_restart: '512M',
      env: {
        NODE_ENV: 'production'
      }
    }
  ]
};
```

#### 2.3 Systemd Services (Linux)

```ini
# /etc/systemd/system/scada-soketi.service
[Unit]
Description=SCADA Soketi WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/scada-dashboard
ExecStart=/usr/bin/node node_modules/.bin/soketi start --config=soketi.json
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```ini
# /etc/systemd/system/scada-queue.service
[Unit]
Description=SCADA Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/scada-dashboard
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### 3. Database Management

#### 3.1 Database Optimization

```sql
-- Optimize table structure
ALTER TABLE scada_data_wides
ENGINE=InnoDB
ROW_FORMAT=COMPRESSED
KEY_BLOCK_SIZE=8;

-- Add additional indexes for performance
CREATE INDEX idx_timestamp_created ON scada_data_wides(timestamp_device, created_at);
CREATE INDEX idx_group_timestamp ON scada_data_wides(nama_group, timestamp_device);

-- Partition table by date (for very large datasets)
ALTER TABLE scada_data_wides
PARTITION BY RANGE (YEAR(timestamp_device)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

#### 3.2 Backup Strategy

```bash
#!/bin/bash
# scripts/backup-database.sh

BACKUP_DIR="/backup/scada-dashboard"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="scada_dashboard"
DB_USER="scada_user"
DB_PASS="your_password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/scada_backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/scada_backup_$DATE.sql

# Keep only last 7 days of backups
find $BACKUP_DIR -name "scada_backup_*.sql.gz" -mtime +7 -delete

echo "Database backup completed: scada_backup_$DATE.sql.gz"
```

#### 3.3 Data Archiving

```php
// app/Console/Commands/ArchiveOldData.php
class ArchiveOldData extends Command
{
    protected $signature = 'scada:archive {--days=365}';
    protected $description = 'Archive old SCADA data to reduce table size';

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        // Archive old data
        $archivedCount = ScadaDataWide::where('timestamp_device', '<', $cutoffDate)
            ->chunk(1000, function ($records) {
                foreach ($records as $record) {
                    // Archive to separate table or file
                    $this->archiveRecord($record);
                }
            });

        // Delete archived data
        $deletedCount = ScadaDataWide::where('timestamp_device', '<', $cutoffDate)->delete();

        $this->info("Archived: $archivedCount records");
        $this->info("Deleted: $deletedCount records");
    }
}
```

### 4. Monitoring & Health Checks

#### 4.1 Health Check Endpoints

```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function check()
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'services' => []
        ];

        // Check database
        try {
            DB::connection()->getPdo();
            $health['services']['database'] = 'healthy';
        } catch (\Exception $e) {
            $health['services']['database'] = 'unhealthy';
            $health['status'] = 'unhealthy';
        }

        // Check Redis
        try {
            Redis::ping();
            $health['services']['redis'] = 'healthy';
        } catch (\Exception $e) {
            $health['services']['redis'] = 'unhealthy';
            $health['status'] = 'unhealthy';
        }

        // Check Soketi
        try {
            $response = Http::timeout(5)->get('http://127.0.0.1:6001');
            $health['services']['soketi'] = $response->successful() ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            $health['services']['soketi'] = 'unhealthy';
            $health['status'] = 'unhealthy';
        }

        // Check queue workers
        $queueSize = Queue::size('scada-processing') + Queue::size('scada-large-datasets');
        $health['services']['queue'] = $queueSize < 1000 ? 'healthy' : 'warning';

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;
        return response()->json($health, $statusCode);
    }
}
```

#### 4.2 Performance Monitoring

```php
// app/Console/Commands/MonitorPerformance.php
class MonitorPerformance extends Command
{
    protected $signature = 'scada:monitor';
    protected $description = 'Monitor SCADA system performance';

    public function handle()
    {
        $metrics = [
            'database_size' => $this->getDatabaseSize(),
            'queue_size' => $this->getQueueSize(),
            'memory_usage' => $this->getMemoryUsage(),
            'response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
        ];

        // Log metrics
        Log::info('Performance metrics', $metrics);

        // Alert if thresholds exceeded
        if ($metrics['queue_size'] > 5000) {
            $this->alert('Queue size too large: ' . $metrics['queue_size']);
        }

        if ($metrics['error_rate'] > 0.05) {
            $this->alert('High error rate: ' . $metrics['error_rate']);
        }

        $this->info('Performance monitoring completed');
    }
}
```

### 5. Security Configuration

#### 5.1 Production Security

```php
// config/app.php
'debug' => env('APP_DEBUG', false),
'env' => env('APP_ENV', 'production'),

// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 120),
'expire_on_close' => true,
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'lax',

// config/cors.php
'paths' => ['api/*'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_origins' => [env('FRONTEND_URL', 'https://your-domain.com')],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => false,
```

#### 5.2 Rate Limiting

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:60,1', // 60 requests per minute
        'throttle:1000,60', // 1000 requests per hour
    ],
];

// app/Http/Middleware/ScadaRateLimit.php
class ScadaRateLimit
{
    public function handle($request, Closure $next)
    {
        $key = 'scada_rate_limit:' . $request->ip();
        $maxRequests = 100; // Max 100 requests per minute for SCADA data

        if (RateLimiter::tooManyAttempts($key, $maxRequests)) {
            return response()->json([
                'error' => 'Rate limit exceeded'
            ], 429);
        }

        RateLimiter::hit($key, 60);
        return $next($request);
    }
}
```

### 6. Logging & Debugging

#### 6.1 Production Logging

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'ignore_exceptions' => false,
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/scada-dashboard.log'),
        'level' => env('LOG_LEVEL', 'error'),
        'days' => 14,
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'SCADA Dashboard',
        'emoji' => ':warning:',
        'level' => env('LOG_LEVEL', 'critical'),
    ],
],
```

#### 6.2 Error Tracking

```php
// app/Exceptions/Handler.php
public function register(): void
{
    $this->reportable(function (Throwable $e) {
        // Send critical errors to monitoring service
        if ($e instanceof \Exception) {
            Log::critical('Critical error occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    });
}
```

### 7. Backup & Recovery

#### 7.1 Automated Backups

```bash
#!/bin/bash
# scripts/automated-backup.sh

# Database backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > /backup/db_$(date +%Y%m%d_%H%M%S).sql

# File backup
tar -czf /backup/files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/scada-dashboard

# Upload to cloud storage (example with AWS S3)
aws s3 sync /backup s3://your-backup-bucket/scada-dashboard/

# Cleanup old backups
find /backup -name "*.sql" -mtime +7 -delete
find /backup -name "*.tar.gz" -mtime +7 -delete
```

#### 7.2 Disaster Recovery

```php
// app/Console/Commands/SystemRecovery.php
class SystemRecovery extends Command
{
    protected $signature = 'scada:recover';
    protected $description = 'Recover system from backup';

    public function handle()
    {
        $this->info('Starting system recovery...');

        // 1. Stop all services
        $this->stopServices();

        // 2. Restore database
        $this->restoreDatabase();

        // 3. Restore files
        $this->restoreFiles();

        // 4. Clear caches
        $this->clearCaches();

        // 5. Restart services
        $this->startServices();

        $this->info('System recovery completed!');
    }
}
```

### 8. Scaling & Load Balancing

#### 8.1 Horizontal Scaling

```nginx
# /etc/nginx/sites-available/scada-dashboard
upstream scada_backend {
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
}

upstream soketi_backend {
    server 127.0.0.1:6001;
    server 127.0.0.1:6002;
    server 127.0.0.1:6003;
}

server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://scada_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location /app/ {
        proxy_pass http://soketi_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

#### 8.2 Redis Cluster

```env
# .env
REDIS_CLUSTER=true
REDIS_CLUSTER_NODES=127.0.0.1:7000,127.0.0.1:7001,127.0.0.1:7002
```

---

**Status**: ✅ **READY FOR PRODUCTION** - Semua fitur deployment sudah siap
**Recommended**: Use PM2 for process management, systemd for Linux
**Last Updated**: January 2025
**Version**: 1.0.0
