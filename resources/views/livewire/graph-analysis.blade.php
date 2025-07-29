<div wire:poll.5s>
    {{-- Filter Controls dengan direktif Livewire --}}
    <div class="filters">
        <div class="filter-group">
            <label>Select Metrics:</label>
            <div id="tag-checkboxes">
                @foreach ($allTags as $tag)
                    <div class="checkbox-item">
                        <input type="checkbox" id="tag-{{ $loop->index }}" wire:model.live="selectedTags"
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
            <input type="date" id="start-date" wire:model.live="startDate">
        </div>
        <div class="filter-group">
            <label for="end-date">End Date:</label>
            <input type="date" id="end-date" wire:model.live="endDate">
        </div>
        {{-- Tombol "Apply Filter" tidak lagi diperlukan karena .live membuat update instan --}}
    </div>

    {{-- Chart Canvas. wire:ignore penting agar Livewire tidak mengganggu Chart.js --}}
    <div class="chart-container" wire:ignore>
        <canvas id="historicalChart"></canvas>
    </div>
</div>
