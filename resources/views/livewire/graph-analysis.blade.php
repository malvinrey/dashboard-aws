<div wire:poll.5s="getLatestDataPoint">
    {{-- Filter Controls dengan direktif Livewire --}}

    <div class="loading-overlay" wire:loading wire:target="loadChartData">
        <div class="spinner"></div>
    </div>

    <div class="filters">
        <div class="filter-group"> {{-- wire:ignore dihapus --}}
            <label>Select Metrics:</label>
            <div id="tag-checkboxes-livewire" class="tag-checkboxes">
                @forelse ($allTags as $tag)
                    <div class="checkbox-item">
                        {{-- wire:model ditambahkan kembali --}}
                        <input type="checkbox" id="tag-livewire-{{ $loop->index }}" wire:model="selectedTags"
                            value="{{ $tag }}">
                        <label for="tag-livewire-{{ $loop->index }}">{{ $tag }}</label>
                    </div>
                @empty
                    <p style="padding: 10px;">No metrics available.</p>
                @endforelse
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
            <label for="start-date-livewire">Start Date:</label>
            <input type="date" id="start-date-livewire" wire:model="startDate">
        </div>

        <div class="filter-group">
            <label for="end-date-livewire">End Date:</label>
            <input type="date" id="end-date-livewire" wire:model="endDate">
        </div>

        <div class="filter-group">
            {{-- Tombol untuk memicu pembaruan data secara manual --}}
            <button wire:click="loadChartData">Apply Filter</button>
        </div>
        <div class="filter-group">
            {{-- Tombol untuk sync selected tags dan load chart --}}
            <button onclick="syncAndLoadChart()">Sync & Load Chart</button>
        </div>
    </div>

    {{-- Chart Canvas. wire:ignore penting agar Livewire tidak mengganggu Chart.js --}}
    <div class="chart-container" wire:ignore>
        <canvas id="historicalChart"></canvas>
        <div style="margin-top: 10px; font-size: 12px; color: #6c757d; text-align: center;">
            <strong>Zoom & Pan Controls:</strong><br>
            • Mouse wheel: Zoom in/out | • Ctrl + Drag: Pan | • Drag to select area for zoom | • Touch: Pinch to zoom,
            drag to pan
        </div>
    </div>
</div>
