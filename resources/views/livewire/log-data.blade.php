<div wire:poll.3s>
    {{-- Atribut wire:poll akan me-refresh komponen ini setiap 3 detik --}}

    <br>

    <table class="log-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Waktu</th>
                <th>Nama Grup</th>
                <th>Nama Tag</th>
                <th>Nilai</th>
            </tr>
        </thead>
        <tbody>
            {{-- Inisialisasi variabel untuk melacak batch_id sebelumnya --}}
            @php $previousBatchId = null; @endphp

            @forelse ($logs as $log)
                {{-- Cek jika batch_id saat ini berbeda dari sebelumnya untuk membuat pemisah --}}
                @if ($log->batch_id !== $previousBatchId && !$loop->first)
                    <tr class="batch-separator">
                        <td colspan="5"></td>
                    </tr>
                @endif

                <tr wire:key="{{ $log->id }}">
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->timestamp_device->format('d M Y, H:i:s') }}</td>
                    <td>{{ $log->nama_group }}</td>
                    <td>{{ $log->nama_tag }}</td>
                    <td>{{ $log->nilai_tag }}</td>
                </tr>

                {{-- Update batch_id sebelumnya untuk iterasi berikutnya --}}
                @php $previousBatchId = $log->batch_id; @endphp
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Belum ada data log yang tercatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <br>

    <div class="load-more-container">
        {{-- Tampilkan tombol hanya jika data yang ditampilkan lebih sedikit dari total data --}}
        @if ($amount < $totalRecords)
            <button wire:click="loadMore" wire:loading.attr="disabled" class="load-more-button">
                <span wire:loading.remove wire:target="loadMore">
                    Load More
                </span>
                <span wire:loading wire:target="loadMore">
                    Loading...
                </span>
            </button>
        @else
            <p style="text-align: center; color: #777;">Sudah menampilkan semua data.</p>
        @endif
    </div>
</div>
