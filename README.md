# AWS Dashboard - SCADA Data Monitoring System

A real-time monitoring dashboard for AWS (Automatic Weather Station) SCADA data built with Laravel and Livewire. This application provides comprehensive weather data visualization, historical analysis, and data logging capabilities.

## 🌟 Features

### 📊 Real-time Dashboard

-   **Live Weather Metrics**: Temperature, humidity, pressure, rainfall, wind speed, and direction
-   **Interactive Gauges**: Visual representation of current weather conditions
-   **System Status**: Real-time connection status indicator
-   **Auto-refresh**: Livewire-powered automatic updates

### 📈 Historical Data Analysis

-   **Interactive Charts**: Multi-metric visualization with Chart.js
-   **Flexible Time Intervals**: Second, minute, hour, and day views
-   **Date Range Filtering**: Custom start and end date/time selection
-   **Multi-metric Selection & Filtering**: Choose one or more weather parameters (metrics) to display. Only selected metrics will be visualized and queried from the backend, ensuring efficient and relevant data display. You can select all, clear all, or pick specific metrics as needed. If no metric is selected, the chart will be cleared automatically.
-   **Smart Data Aggregation**: Database-level aggregation automatically optimizes large datasets for fast rendering while preserving data accuracy
-   **Zoom & Pan**: Interactive chart navigation
-   **Responsive Design**: Works on desktop and mobile devices

### 📋 Data Logging

-   **Comprehensive Logs**: Detailed SCADA data records
-   **Pagination**: Efficient data browsing
-   **Search & Filter**: Find specific data entries quickly
-   **Export Capabilities**: Download data for external analysis

### 🔌 SCADA Integration

-   **REST API Endpoint**: `/api/aws/receiver` for data ingestion
-   **Batch Processing**: Efficient handling of multiple data points
-   **Error Handling**: Robust error management and logging
-   **Data Validation**: Ensures data integrity
-   **Performance Optimization**: Intelligent data downsampling for large datasets
-   **Real-time API**: Lightweight `/api/latest-data` endpoint for efficient polling

## 🚀 Installation

### Prerequisites

-   PHP 8.1 or higher
-   Composer
-   MySQL/MariaDB database
-   Web server (Apache/Nginx)

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd dashboard-aws
```

### Step 2: Install Dependencies

```bash
composer install
npm install
```

### Step 3: Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your database credentials and SCADA optimization settings:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# SCADA Performance Optimization
SCADA_MAX_POINTS_PER_SERIES=1000
SCADA_DOWNSAMPLING_ENABLED=true
SCADA_MIN_POINTS_THRESHOLD=1000
SCADA_MAX_BATCH_SIZE=1000
SCADA_ENABLE_LOGGING=true
```

### Step 4: Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### Step 5: Build Assets

```bash
npm run build
```

### Step 6: Start the Application

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## 📖 Usage Guide

### Dashboard Overview

1. **Navigate to Home**: Access the main dashboard at `/`
2. **View Live Data**: See real-time weather metrics with interactive gauges
3. **Monitor System Status**: Check the connection indicator in the header
4. **Auto-refresh**: Data updates automatically every few seconds

### Historical Analysis

1. **Access Analysis Page**: Click "Analysis Chart" in the navigation
2. **Select Metrics**: Choose one or more weather parameters to display (temperature, humidity, etc.). The metric filter uses checkboxes, and you can select all, clear all, or pick specific metrics. The chart will only show data for the selected metrics. If no metric is selected, the chart will be cleared automatically.
3. **Set Time Range**:
    - Choose time interval (second, minute, hour, day)
    - Set start and end dates/times
4. **Apply Filters**: Click "Load Historical Data" to update the chart, or simply change the metric/interval/date to auto-update.
5. **Interact with Chart**:
    - Zoom in/out using mouse wheel
    - Pan by dragging
    - Reset zoom with "Reset Zoom" button

### Data Logging

1. **Access Log Page**: Click "Log Data" in the navigation
2. **Browse Records**: View paginated SCADA data
3. **Search Data**: Use search functionality to find specific entries
4. **Export Data**: Download data for external analysis

### SCADA Data Integration

Send POST requests to `/api/aws/receiver` with the following JSON structure:

```json
{
    "DataArray": [
        {
            "_groupTag": "weather_station_1",
            "_terminalTime": "2024-01-15 10:30:00",
            "temperature": 25.5,
            "humidity": 65.2,
            "pressure": 1013.25,
            "rainfall": 0.0,
            "wind_speed": 12.3,
            "wind_direction": 180
        }
    ]
}
```

## 🏗️ Architecture

### Backend Structure

```
app/
├── Http/Controllers/
│   ├── DashboardController.php    # Main dashboard logic
│   ├── AnalysisController.php     # Chart data API
│   └── Api/ReceiverController.php # SCADA data receiver
├── Livewire/
│   ├── Dashboard.php              # Live dashboard component
│   └── ScadaLogTable.php          # Data table component
├── Models/
│   └── ScadaDataTall.php          # Data model
└── Services/
    └── ScadaDataService.php       # Business logic with database aggregation
```

### Frontend Components

```
resources/views/
├── components/                    # Reusable UI components
│   ├── thermometer.blade.php
│   ├── humidity-gauge.blade.php
│   ├── pressure-gauge.blade.php
│   ├── rainfall gauge.blade.php
│   └── compass.blade.php
├── livewire/                      # Livewire components
│   ├── dashboard.blade.php
│   ├── graph-analysis.blade.php
│   └── log-data.blade.php
└── views-*.blade.php              # Main page views
```

## 🔧 Configuration

### Supported Weather Metrics

-   **Temperature** (°C)
-   **Humidity** (%)
-   **Pressure** (hPa)
-   **Rainfall** (mm)
-   **Wind Speed** (m/s)
-   **Wind Direction** (°)
-   **PAR Sensor** (μmol/m²/s)
-   **Solar Radiation** (W/m²)

### Chart Configuration

-   **Time Intervals**: Second, minute, hour, day
-   **Chart Type**: Line charts with area fill
-   **Colors**: Automatic color palette assignment
-   **Responsive**: Adapts to screen size

### Performance Optimization

-   **Database Aggregation**: Efficient data aggregation at database level
-   **Configurable Thresholds**: Adjustable data point limits per series
-   **Automatic Optimization**: Applied when data exceeds minimum threshold
-   **Visual Fidelity**: Preserves chart shape while reducing data points by up to 90%

## 🛠️ Development

### Running Tests

```bash
php artisan test
```

### Testing Downsampling

```bash
# Run downsampling unit tests
php artisan test tests/Unit/ScadaDataServiceTest.php

# Demonstrate downsampling with test data
php artisan scada:demo-downsampling --points=5000 --threshold=1000
```

### Testing API Endpoints

```bash
# Run API endpoint tests
php artisan test tests/Feature/LatestDataApiTest.php
```

### Code Quality

```bash
composer test
```

### Database Migrations

```bash
php artisan migrate
php artisan migrate:rollback
```

### Clearing Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📊 API Endpoints

### GET `/api/analysis-data`

Retrieve historical chart data with parameters:

-   `tag[]`: Array of metric names
-   `interval`: Time interval (second, minute, hour, day)
-   `start_date`: Start date (YYYY-MM-DD)
-   `end_date`: End date (YYYY-MM-DD)
-   `start_time`: Start time (HH:MM)
-   `end_time`: End time (HH:MM)

### GET `/api/latest-data`

Lightweight endpoint for real-time data updates:

-   `tags[]`: Array of metric names
-   `interval`: Time interval (second, minute, hour, day)
-   **Response**: Latest data points or 204 (no new data)
-   **Performance**: < 100ms response time

### POST `/api/aws/receiver`

Receive SCADA data payload

## 🔒 Security

-   CSRF protection enabled
-   Input validation and sanitization
-   SQL injection prevention
-   Rate limiting on API endpoints

## 🐛 Troubleshooting

### Common Issues

1. **Routes Not Working**

    ```bash
    php artisan route:clear
    php artisan config:clear
    ```

2. **Database Connection Issues**

    - Check `.env` configuration
    - Verify database server is running
    - Ensure database exists

3. **Chart Not Loading**

    - Check browser console for JavaScript errors
    - Verify API endpoint is accessible
    - Ensure data exists for selected metrics

4. **Livewire Not Updating**

    ```bash
    php artisan view:clear
    npm run build
    ```

5. **Slow Chart Loading with Large Datasets**
    - Verify database aggregation is working correctly
    - Check interval selection (minute, hour, day for aggregation)
    - Monitor logs for aggregation performance
    - Consider using wider date ranges for better aggregation

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📞 Support

For support and questions:

-   Create an issue in the repository
-   Contact the development team
-   Check the troubleshooting section above
-   Review the [Interval Aggregation Documentation](docs/INTERVAL_AGGREGATION_FIX.md)

---

**Built with ❤️ using Laravel, Livewire, and Chart.js**

## 🚀 Performance Features

### Data Aggregation Optimization

This application includes advanced data aggregation at the database level to handle large datasets efficiently:

-   **Automatic Optimization**: Applied based on selected interval (minute, hour, day)
-   **90% Data Reduction**: Reduces transfer time and memory usage
-   **Data Accuracy**: Preserves statistical accuracy with AVG aggregation
-   **Configurable**: Different aggregation levels based on user interval selection
-   **Performance Monitoring**: Built-in logging and statistics

For detailed information about the aggregation implementation, see [docs/INTERVAL_AGGREGATION_FIX.md](docs/INTERVAL_AGGREGATION_FIX.md).

### Real-time Polling Optimization

Replaced `wire:poll` with efficient JavaScript-based polling using a lightweight API endpoint:

-   **70-80% Server Load Reduction**: Eliminates Livewire overhead
-   **5-10x Faster Response Times**: Direct JSON API calls
-   **Lightweight API**: Dedicated `/api/latest-data` endpoint
-   **Performance Monitoring**: Built-in timing and logging
-   **Seamless Integration**: Works with existing chart functionality

For detailed information about the polling optimization, see [docs/POLLING_API_OPTIMIZATION.md](docs/POLLING_API_OPTIMIZATION.md).
