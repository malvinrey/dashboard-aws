# Polling API Troubleshooting Guide

## Problem: Graph Only Updates When Leaving Active Tab

### Symptoms

-   Real-time updates only occur when switching browser tabs
-   Graph appears static when tab is active
-   No console errors visible
-   Toggle switch appears to work but no updates

### Root Cause Analysis

The issue occurs because:

1. **Visibility Change Logic**: The `visibilitychange` event triggers `catchUpMissedData` when returning to tab
2. **Polling API Not Working**: The JavaScript polling may not be functioning correctly
3. **Event Handling Issues**: The `update-last-point` event may not be processing data properly

## Debugging Steps

### 1. Check Browser Console

Open browser developer tools (F12) and look for these log messages:

```javascript
// Should see these messages every 5 seconds:
"Polling check: {hasChart: true, selectedTags: [...], interval: 'hour', toggleChecked: true}";
"Fetching from: /api/latest-data?interval=hour&tags[]=temperature&tags[]=humidity";
"API Response status: 200";
"API Response data: {timestamp: '...', metrics: {...}}";
"Update last point event received: {data: {...}}";
"Processing new data: {timestamp: '...', metrics: {...}}";
```

### 2. Verify API Endpoint

Test the API endpoint directly:

```bash
# Test with curl
curl "http://localhost:8000/api/latest-data?tags[]=temperature&interval=hour"

# Expected response:
{
    "timestamp": "2024-01-15 10:30:00",
    "metrics": {
        "temperature": 25.5
    }
}
```

### 3. Check Network Tab

In browser developer tools:

1. Go to **Network** tab
2. Filter by **Fetch/XHR**
3. Look for calls to `/api/latest-data`
4. Verify response status and data

### 4. Verify Toggle State

Check if the real-time toggle is working:

```javascript
// In browser console
document.getElementById("realtime-toggle").checked;
// Should return true when enabled
```

## Common Issues and Solutions

### Issue 1: No Polling Logs

**Symptoms**: No console logs from polling function

**Solution**:

```javascript
// Check if polling is initialized
console.log("Polling interval:", realtimePollingInterval);

// Manually start polling
startRealtimePolling();
```

### Issue 2: API Returns 204 (No Data)

**Symptoms**: Console shows "No new data available"

**Solution**:

1. Check if data exists in database
2. Verify selected tags match available data
3. Check time range for data

### Issue 3: Event Not Dispatching

**Symptoms**: API returns data but no chart update

**Solution**:

```javascript
// Manually test event dispatch
Livewire.dispatch("update-last-point", {
    data: {
        timestamp: "2024-01-15 10:30:00",
        metrics: { temperature: 25.5 },
    },
});
```

### Issue 4: Chart Not Found

**Symptoms**: "No chart data available" in console

**Solution**:

1. Ensure chart is loaded before polling starts
2. Check if `plotlyChart` element exists
3. Verify chart data is populated

## Manual Testing Steps

### Step 1: Enable Debug Logging

The updated code includes comprehensive logging. Check console for:

```
"Initializing realtime polling..."
"Starting realtime polling..."
"Realtime polling started successfully"
"Polling check: {...}"
"Fetching from: /api/latest-data?..."
"API Response status: 200"
"API Response data: {...}"
"Update last point event received: {...}"
"Processing new data: {...}"
```

### Step 2: Test API Directly

```bash
# Test API endpoint
curl "http://localhost:8000/api/latest-data?tags[]=temperature&interval=hour"

# Check response format
{
    "timestamp": "2024-01-15 10:30:00",
    "metrics": {
        "temperature": 25.5
    }
}
```

### Step 3: Verify Chart State

```javascript
// In browser console
const chart = document.getElementById('plotlyChart');
console.log('Chart exists:', !!chart);
console.log('Chart data:', chart?.data);
console.log('Selected tags:', @this.get('selectedTags'));
console.log('Interval:', @this.get('interval'));
```

### Step 4: Test Event Handling

```javascript
// Manually trigger update
Livewire.dispatch("update-last-point", {
    data: {
        timestamp: new Date().toISOString().slice(0, 19).replace("T", " "),
        metrics: { temperature: Math.random() * 10 + 20 },
    },
});
```

## Fixes Applied

### 1. Enhanced Logging

Added comprehensive console logging to track:

-   Polling initialization
-   API calls and responses
-   Event processing
-   Chart updates

### 2. Improved Error Handling

Better error messages and validation:

-   Check chart existence
-   Validate data format
-   Handle API errors gracefully

### 3. Debug Information

Added detailed logging for:

-   Polling conditions
-   API response data
-   Chart update process
-   Event dispatching

## Expected Behavior

With the fixes applied, you should see:

1. **Every 5 seconds**: Polling logs in console
2. **API calls**: Network requests to `/api/latest-data`
3. **Data updates**: Chart points updating in real-time
4. **Smooth updates**: No need to switch tabs

## If Issues Persist

### Check Livewire Component

Verify the Livewire component is working:

```php
// In AnalysisController.php
public function getLatestDataApi(Request $request)
{
    $startTime = microtime(true);

    try {
        // Add debug logging
        Log::info('API called with params:', $request->all());

        // ... rest of the method
    } catch (\Exception $e) {
        Log::error('API error:', ['error' => $e->getMessage()]);
    }
}
```

### Check Database

Verify data exists:

```sql
-- Check latest data
SELECT * FROM scada_data_tall
WHERE nama_tag IN ('temperature', 'humidity')
ORDER BY timestamp_device DESC
LIMIT 5;
```

### Check Routes

Verify API route is registered:

```bash
php artisan route:list --path=api
```

## Performance Monitoring

Monitor these metrics:

1. **API Response Time**: Should be < 100ms
2. **Polling Frequency**: Every 5 seconds
3. **Data Freshness**: Latest data within 5 seconds
4. **Error Rate**: Should be minimal

## Conclusion

The enhanced logging and error handling should resolve the issue where graphs only update when leaving the active tab. The polling API should now work continuously and provide real-time updates without requiring tab switches.
