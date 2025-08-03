# Race Condition Fix for Real-time Polling

## Problem Description

The real-time polling was experiencing a race condition that caused graphs to only update when leaving the active tab, rather than continuously updating while the tab was active.

## Root Cause Analysis

### Race Condition Flow

1. **User leaves tab** → Browser pauses `setInterval` for polling
2. **User returns to tab** → `visibilitychange` event triggers immediately
3. **Catch-up logic activates** → `globalState.isCatchingUp = true`
4. **Polling resumes** → `setInterval` continues and fetches latest data
5. **Real-time data arrives** → `update-last-point` event dispatched
6. **Update blocked** → Event listener sees `isCatchingUp = true` and skips update
7. **Catch-up data arrives later** → Chart updates with old data
8. **Catch-up completes** → `isCatchingUp = false`

### The Problematic Code

```javascript
// BEFORE: This caused the race condition
document.addEventListener("update-last-point", (event) => {
    if (globalState.isCatchingUp) {
        console.log("Skipping update - catching up");
        return; // This blocked real-time updates!
    }
    // ... rest of update logic
});
```

## Solution Implemented

### 1. Remove Blocking Logic

**Removed the problematic blocking condition** that prevented real-time updates during catch-up:

```javascript
// AFTER: Removed the blocking condition
document.addEventListener("update-last-point", (event) => {
    // HAPUS BLOK 'if (globalState.isCatchingUp)' - INI MENYEBABKAN RACE CONDITION
    // Logic pembaruan sudah cukup cerdas untuk menangani data berdasarkan timestamp

    const plotlyChart = document.getElementById("plotlyChart");
    // ... rest of update logic (unchanged)
});
```

### 2. Smart Catch-up Logic

**Enhanced the visibility change logic** to only trigger catch-up when there's a significant gap:

```javascript
// BEFORE: Always triggered catch-up
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && globalState.lastKnownTimestamp) {
        globalState.isCatchingUp = true;
        window.Livewire.dispatch('catchUpMissedData', {...});
    }
});

// AFTER: Only trigger catch-up for significant gaps
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && globalState.lastKnownTimestamp) {
        const now = new Date();
        const lastKnown = new Date(globalState.lastKnownTimestamp);
        const gapInSeconds = (now - lastKnown) / 1000;

        if (gapInSeconds > 30) {
            console.log(`Significant gap detected: ${gapInSeconds.toFixed(1)} seconds, triggering catch-up`);
            globalState.isCatchingUp = true;
            window.Livewire.dispatch('catchUpMissedData', {...});
        } else {
            console.log(`Small gap detected: ${gapInSeconds.toFixed(1)} seconds, skipping catch-up`);
        }
    }
});
```

## Why This Fix Works

### 1. Timestamp-Based Logic is Sufficient

The existing update logic already handles data intelligently based on timestamps:

```javascript
if (newPointTimestamp.getTime() === lastChartTimestamp.getTime()) {
    // Update existing point
    currentTrace.y[lastIndex] = newValue;
} else if (newPointTimestamp.getTime() > lastChartTimestamp.getTime()) {
    // Add new point
    Plotly.extendTraces('plotlyChart', {...});
}
```

This logic ensures:

-   **No duplicate points** are added
-   **Older data** doesn't overwrite newer data
-   **Data integrity** is maintained regardless of arrival order

### 2. Eliminates Race Condition

By removing the blocking condition:

-   **Real-time updates** work immediately when tab is active
-   **Catch-up data** can still be processed when needed
-   **No conflicts** between real-time and catch-up updates

### 3. Reduces Unnecessary Catch-up

By only triggering catch-up for significant gaps (>30 seconds):

-   **Small tab switches** don't trigger unnecessary catch-up
-   **Real-time polling** handles most updates
-   **Better performance** with fewer server requests

## Expected Behavior After Fix

### ✅ When Tab is Active

-   **Continuous updates** every 5 seconds
-   **Smooth animations** as new data arrives
-   **No interruptions** from catch-up logic

### ✅ When Returning to Tab

-   **Immediate resumption** of real-time polling
-   **Smart catch-up** only for significant gaps
-   **Seamless transition** back to real-time updates

### ✅ Data Integrity

-   **No duplicate points** in the chart
-   **Correct timestamp ordering** maintained
-   **No data loss** during transitions

## Testing the Fix

### 1. Active Tab Testing

```javascript
// Monitor console for continuous updates
"Polling check: {hasChart: true, selectedTags: [...], interval: 'hour', toggleChecked: true}";
"Fetching from: /api/latest-data?...";
"API Response status: 200";
"Update last point event received: {...}";
"Processing new data: {...}";
```

### 2. Tab Switch Testing

```javascript
// Small gap (should skip catch-up)
"Small gap detected: 5.2 seconds, skipping catch-up";

// Large gap (should trigger catch-up)
"Significant gap detected: 45.8 seconds, triggering catch-up";
```

### 3. Visual Verification

-   **Chart updates continuously** when tab is active
-   **No delays** when switching back to tab
-   **Smooth animations** without jumps or gaps

## Performance Benefits

### Before Fix

-   **Race condition** caused missed updates
-   **Unnecessary catch-up** for small gaps
-   **Poor user experience** with delayed updates

### After Fix

-   **Immediate real-time updates** when tab is active
-   **Smart catch-up** only when needed
-   **Optimal performance** with minimal server load

## Conclusion

The race condition fix ensures that real-time polling works correctly and continuously when the tab is active, while still maintaining the ability to catch up on missed data when there are significant gaps. The solution is elegant because it leverages the existing timestamp-based logic rather than adding complex synchronization mechanisms.
