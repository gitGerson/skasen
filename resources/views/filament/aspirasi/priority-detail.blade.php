@php
    $prioritas = $record->prioritas;
@endphp

<div class="space-y-4">
    @if (! $prioritas)
        <p class="text-sm text-gray-600">Prioritas belum diklasifikasi.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <div class="text-xs font-medium text-gray-500">Prioritas</div>
                <div class="text-base font-semibold text-gray-900">{{ $prioritas->prioritas }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500">Confidence</div>
                <div class="text-base text-gray-900">{{ number_format((float) $prioritas->confidence, 3) }}</div>
            </div>
            <div class="sm:col-span-2">
                <div class="text-xs font-medium text-gray-500">Alasan Singkat</div>
                <div class="text-sm text-gray-900 whitespace-pre-line">{{ $prioritas->alasan_singkat }}</div>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <div class="text-xs font-medium text-gray-500">Model</div>
                <div class="text-sm text-gray-900">{{ $prioritas->model ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500">Vector Store</div>
                <div class="text-sm text-gray-900">{{ $prioritas->vector_store_id ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500">Diklasifikasi</div>
                <div class="text-sm text-gray-900">{{ optional($prioritas->classified_at)->format('d/m/Y H:i') ?? '-' }}</div>
            </div>
        </div>
    @endif
</div>
