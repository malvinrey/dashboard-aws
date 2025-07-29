<div wire:poll.5s="getLatestDataPoint">
    <div class="filters">
        <div class="filter-group">
            <label>Select Metrics:</label>
            <div id="tag-checkboxes">
                @foreach ($allTags as $tag)
                    <div class="checkbox-item">
                        <input type="checkbox" id="tag-{{ $loop->index }}" wire:model="selectedTags"
                            value="{{ $tag }}">
                        <label for="tag-{{ $loop->index }}">{{ $tag }}</label>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="filter-group">
            <label>Select Interval:</label>
            <div class="interval-buttons">
                <button wire:click="$set('interval', 'hour')"
                    class="{{ $interval === 'hour' ? 'active' : '' }}">Hour</button>
                <button wire:click="$set('interval', 'day')"
                    class="{{ $interval === 'day' ? 'active' : '' }}">Day</button>
                <button wire:click="$set('interval', 'minute')"
                    class="{{ $interval === 'minute' ? 'active' : '' }}">Minute</button>
                <button wire:click="$set('interval', 'second')"
                    class="{{ $interval === 'second' ? 'active' : '' }}">Second</button>
            </div>
        </div>
        <div class="filter-group">
            <label for="start-date">Start Date:</label>
            <input type="date" id="start-date" wire:model="startDate">
        </div>
        <div class="filter-group">
            <label for="end-date">End Date:</label>
            <input type="date" id="end-date" wire:model="endDate">
        </div>
        <div class="filter-group">
            <button wire:click="loadChartData">Apply Filter</button>
        </div>
    </div>

    <div class="chart-container" wire:ignore>
        <canvas id="historicalChart"></canvas>
    </div>
</div>
