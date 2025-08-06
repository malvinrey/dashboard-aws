# Log Data Interactive Features

## Overview

The log data page now includes three main interactive features that allow users to better analyze and filter the SCADA data:

1. **Date Range Filtering** - Filter data by specific date ranges
2. **Search Functionality** - Search across all columns for specific values
3. **Column Sorting** - Sort data by any column in ascending or descending order
4. **Data Export** - Export filtered data to CSV format

## Features Implementation

### 1. Date Range Filtering

**What it does:**

-   Allows users to select a start date and end date to filter log data
-   Shows only records within the specified date range
-   Default range is set to the last 7 days

**How to use:**

1. Select a start date using the "Start Date" input
2. Select an end date using the "End Date" input
3. Click "Apply Filters" to apply the date range
4. Use "Clear Filters" to reset to default range

**Technical Implementation:**

-   Uses `wire:model` to bind date inputs to Livewire properties
-   Filters are applied using `whereDate()` queries in the database
-   Date format: YYYY-MM-DD

### 2. Search Functionality

**What it does:**

-   Searches across all columns (ID, nama_group, par_sensor, solar_radiation, wind_speed, wind_direction, temperature, humidity, pressure, rainfall)
-   Real-time search with 500ms debounce to prevent excessive queries
-   Shows search term and number of matching records

**How to use:**

1. Type in the search box
2. Search is performed automatically after 500ms of no typing
3. Results show all records containing the search term in any column
4. Clear search by clicking "Clear Filters"

**Technical Implementation:**

-   Uses `wire:model.debounce.500ms` for real-time search
-   Implements `updatedSearch()` method to handle search changes
-   Uses `LIKE` queries with `%` wildcards for partial matching

### 3. Column Sorting

**What it does:**

-   Makes all table headers clickable for sorting
-   Toggles between ascending and descending order
-   Shows visual indicators (arrows) for current sort column and direction
-   Maintains sort state across filter changes

**How to use:**

1. Click on any column header to sort by that column
2. Click again to reverse the sort order
3. Visual arrows (↑↓) indicate current sort direction
4. Sort works in combination with other filters

**Technical Implementation:**

-   Uses `wire:click="sortBy('field_name')"` for header clicks
-   Implements `sortBy()` method to handle sort logic
-   Uses CSS classes for visual feedback
-   Maintains sort state in Livewire properties

## User Interface

### Filter Controls Section

```
┌─────────────────────────────────────────────────────────────┐
│ Filter Controls                                             │
├─────────────────────────────────────────────────────────────┤
│ Start Date: [2025-01-01]  End Date: [2025-12-31]           │
│ Search: [Search in all columns...]                          │
│                                                             │
│ [Apply Filters] [Clear Filters] [Export CSV]                │
│                                                             │
│ Active Filters: Start: 01 Jan 2025 End: 31 Dec 2025        │
│ (150 records found)                                         │
└─────────────────────────────────────────────────────────────┘
```

### Sortable Table Headers

```
┌─────┬─────────────┬─────────────┬─────────────┬─────────────┐
│ ID↑ │ Waktu       │ Nama Grup   │ PAR Sensor  │ Solar Rad.  │
├─────┼─────────────┼─────────────┼─────────────┼─────────────┤
│ 1   │ 01 Jan 2025 │ Group A     │ 150.5       │ 850.2       │
│ 2   │ 01 Jan 2025 │ Group B     │ 145.8       │ 845.1       │
└─────┴─────────────┴─────────────┴─────────────┴─────────────┘
```

## Technical Details

### Livewire Component Properties

```php
// Date filtering properties
public $startDate = '';
public $endDate = '';

// Search property
public $search = '';

// Sorting properties
public $sortField = 'id';
public $sortDirection = 'desc';
```

### Service Methods

```php
// Get filtered data
public function getLogDataWithFilters(
    int $limit = 50,
    string $startDate = '',
    string $endDate = '',
    string $search = '',
    string $sortField = 'id',
    string $sortDirection = 'desc'
)

// Get filtered total count
public function getTotalRecordsWithFilters(
    string $startDate = '',
    string $endDate = '',
    string $search = ''
): int
```

### Component Methods

```php
// Apply filters
public function applyFilters()

// Clear all filters
public function clearFilters()

// Handle column sorting
public function sortBy($field)

// Handle search updates
public function updatedSearch()

// Export data to CSV
public function exportCsv()
```

## Performance Considerations

1. **Search Debouncing**: 500ms debounce prevents excessive database queries
2. **Efficient Queries**: Uses indexed columns for better performance
3. **Lazy Loading**: Loads data in chunks (50 records at a time)
4. **Caching**: Livewire handles component state caching

## Responsive Design

The filter controls and table are fully responsive:

-   **Desktop**: Horizontal layout with all filters in one row
-   **Tablet**: Filters stack vertically but remain in columns
-   **Mobile**: Single column layout with stacked filter groups

## Testing

Use the test script to verify functionality:

```bash
php scripts/test_log_data_filters.php
```

This script tests:

-   Date range filtering
-   Search functionality
-   Column sorting
-   Combined filters

For export functionality testing:

```bash
php scripts/test_export_functionality.php
```

This script tests:

-   Basic CSV export
-   Export with search filters
-   Export with date range filters
-   Export all data

## Future Enhancements

Potential improvements for future versions:

1. **Advanced Search**: Boolean operators (AND, OR, NOT)
2. **Export Functionality**: Export filtered data to CSV/Excel
3. **Saved Filters**: Save and reuse filter combinations
4. **Column Visibility**: Show/hide specific columns
5. **Bulk Actions**: Select multiple records for operations
