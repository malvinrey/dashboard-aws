# Real-time Polling Testing Guide

## Overview

This guide helps you verify that the real-time polling API is working correctly and updating the graph continuously when the tab is active.

## Expected Behavior

When the real-time toggle is enabled, the graph should:

-   **Update every 5 seconds** automatically
-   **Show new data points** as they arrive
-   **Work continuously** without requiring tab switches
-   **Display smooth animations** when new data is added

## Testing Steps

### Step 1: Enable Real-time Updates

1. **Open the Analysis Chart page**
2. **Select metrics** (e.g., temperature, humidity)
3. **Choose time interval** (hour, day, minute, second)
4. **Enable real-time toggle** (switch should be ON)
5. **Load historical data** to initialize the chart

### Step 2: Monitor Browser Console

Open Developer Tools (F12) and check the Console tab. You should see these logs **every 5 seconds**:

```javascript
// Initialization
"Initializing realtime polling...";
"Starting realtime polling...";
"Realtime polling started successfully";

// Every 5 seconds
"Polling check: {hasChart: true, selectedTags: [...], interval: 'hour', toggleChecked: true}";
"Fetching from: /api/latest-data?interval=hour&tags[]=temperature&tags[]=humidity";
"API Response status: 200";
"API Response data: {timestamp: '...', metrics: {...}}";
"Update last point event received: {data: {...}}";
"Processing new data: {timestamp: '...', metrics: {...}}";
```

### Step 3: Check Network Tab

In Developer Tools → Network tab:

1. **Filter by Fetch/XHR**
2. **Look for calls** to `/api/latest-data`
3. **Verify frequency**: Should see calls every 5 seconds
4. **Check response**: Should return 200 status with data

### Step 4: Visual Verification

**Watch the chart for these indicators**:

-   **New points appearing** on the right side of the chart
-   **Smooth line extensions** as new data arrives
-   **Updated values** in tooltips when hovering
-   **Continuous movement** without gaps or jumps

## Manual Testing Commands

### Test API Endpoint Directly

```bash
# Test the API endpoint
curl "http://localhost:8000/api/latest-data?tags[]=temperature&interval=hour"

# Expected response:
{
    "timestamp": "2024-01-15 10:30:00",
    "metrics": {
        "temperature": 25.5
    }
}
```

### Test JavaScript Polling

In browser console, run these commands:

```javascript
// Check if polling is active
console.log('Polling interval:', realtimePollingInterval);

// Check toggle state
console.log('Toggle checked:', document.getElementById('realtime-toggle').checked);

// Check selected tags
console.log('Selected tags:', @this.get('selectedTags'));

// Manually trigger polling
startRealtimePolling();

// Test event dispatch manually
Livewire.dispatch('update-last-point', {
    data: {
        timestamp: new Date().toISOString().slice(0, 19).replace('T', ' '),
        metrics: {temperature: Math.random() * 10 + 20}
    }
});
```

## Troubleshooting

### Issue 1: No Console Logs

**Problem**: No polling logs appear in console

**Solutions**:

1. **Check if toggle is enabled**
2. **Verify selected tags exist**
3. **Ensure chart is loaded**
4. **Check for JavaScript errors**

### Issue 2: API Returns 204

**Problem**: Console shows "No new data available"

**Solutions**:

1. **Check database for recent data**
2. **Verify selected tags match available data**
3. **Check time range settings**

### Issue 3: Chart Not Updating

**Problem**: API returns data but chart doesn't change

**Solutions**:

1. **Check event listener registration**
2. **Verify chart element exists**
3. **Check data format compatibility**

### Issue 4: Polling Stops

**Problem**: Polling works initially but stops

**Solutions**:

1. **Check for JavaScript errors**
2. **Verify toggle state hasn't changed**
3. **Restart polling manually**

## Performance Verification

### Response Time Check

Monitor API response times in Network tab:

-   **Target**: < 100ms
-   **Acceptable**: < 500ms
-   **Problem**: > 1000ms

### Polling Frequency Check

Verify polling occurs every 5 seconds:

-   **Check console timestamps**
-   **Monitor Network tab calls**
-   **Use browser dev tools timing**

### Memory Usage Check

Monitor for memory leaks:

-   **Check if intervals are cleared properly**
-   **Verify no duplicate polling instances**
-   **Monitor chart data growth**

## Expected Console Output

### Successful Polling Cycle

```
Polling check: {hasChart: true, selectedTags: ['temperature', 'humidity'], interval: 'hour', toggleChecked: true}
Fetching from: /api/latest-data?interval=hour&tags[]=temperature&tags[]=humidity
API Response status: 200
API Response data: {timestamp: '2024-01-15 10:30:00', metrics: {temperature: 25.5, humidity: 65.2}}
Update last point event received: {data: {timestamp: '2024-01-15 10:30:00', metrics: {temperature: 25.5, humidity: 65.2}}}
Processing new data: {timestamp: '2024-01-15 10:30:00', metrics: {temperature: 25.5, humidity: 65.2}}
Metric: temperature, New: 25.5, Last: 25.3, Time: 2024-01-15T10:30:00.000Z
Added new point for temperature
Metric: humidity, New: 65.2, Last: 64.8, Time: 2024-01-15T10:30:00.000Z
Added new point for humidity
```

### No Data Available

```
Polling check: {hasChart: true, selectedTags: ['temperature'], interval: 'hour', toggleChecked: true}
Fetching from: /api/latest-data?interval=hour&tags[]=temperature
API Response status: 204
No new data available
```

## Success Criteria

The real-time polling is working correctly if:

✅ **Console logs appear every 5 seconds**
✅ **Network calls to `/api/latest-data` are regular**
✅ **Chart updates with new data points**
✅ **No JavaScript errors in console**
✅ **Toggle switch controls polling properly**
✅ **Performance is smooth without lag**

## Common Issues and Fixes

### Issue: Polling Not Starting

**Fix**: Check initialization order

```javascript
// Ensure this runs after chart is loaded
document.addEventListener("chart-data-updated", () => {
    startRealtimePolling();
});
```

### Issue: Data Not Updating

**Fix**: Verify data format

```javascript
// Check if data structure matches expected format
if (!newData || !newData.metrics || !newData.timestamp) {
    console.log("Invalid data format:", newData);
    return;
}
```

### Issue: Performance Problems

**Fix**: Optimize polling frequency

```javascript
// Reduce polling frequency if needed
}, 10000); // Change from 5000 to 10000 for 10-second intervals
```

## Conclusion

With proper testing and monitoring, the real-time polling should provide smooth, continuous updates to the graph without requiring tab switches. The enhanced logging makes it easy to debug any issues and ensure optimal performance.
