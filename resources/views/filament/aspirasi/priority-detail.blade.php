@php
    $prioritas = $record->prioritas;

    $priorityLabel = $prioritas?->prioritas ?? null;

    // Badge style by priority (sesuaikan label/enum kamu)
    $priorityStyles = match (strtolower((string) $priorityLabel)) {
        'tinggi', 'high' => 'bg-red-50 text-red-700 ring-red-200',
        'sedang', 'medium' => 'bg-yellow-50 text-yellow-800 ring-yellow-200',
        'rendah', 'low' => 'bg-green-50 text-green-700 ring-green-200',
        default => 'bg-gray-50 text-gray-700 ring-gray-200',
    };

    $confidence = $prioritas?->confidence;
    $confidence = is_null($confidence) ? null : (float) $confidence;

    // normalisasi (mungkin 0..1 atau 0..100)
    $confidencePercent = is_null($confidence) ? null : ($confidence <= 1 ? $confidence * 100 : $confidence);
    $confidenceScore01 = is_null($confidence) ? null : ($confidence <= 1 ? $confidence : $confidence / 100);

    $confidenceText = is_null($confidenceScore01) ? '-' : number_format($confidenceScore01, 3);
    $confidencePctText = is_null($confidencePercent) ? '-' : ((int) round($confidencePercent)) . '%';

    $classifiedAt = $prioritas?->classified_at?->format('d/m/Y H:i') ?? '-';
@endphp

<div class="space-y-5">
    @if (! $prioritas)
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="text-sm font-medium text-gray-900">Belum ada hasil klasifikasi</div>
            <div class="mt-1 text-sm text-gray-600">Prioritas belum diklasifikasi untuk aspirasi ini.</div>
        </div>
    @else
        {{-- Header ringkas: judul + badge + confidence (1 kali saja) --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-gray-900">Detail Klasifikasi</div>
                <div class="mt-0.5 text-xs text-gray-500">Aspirasi #{{ $record->id }}</div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $priorityStyles }}">
                    {{ $priorityLabel ?? '-' }}
                </span>

                {{-- tampilkan confidence hanya sekali --}}
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200 bg-white">
                    {{ $confidencePctText }} • {{ $confidenceText }}
                </span>
            </div>
        </div>

        {{-- Konten utama --}}
        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Prioritas (tanpa duplikasi badge, fokus info) --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-xs font-medium text-gray-500">Prioritas</div>
                <div class="mt-1 text-base font-semibold text-gray-900">
                    {{ $priorityLabel ?? '-' }}
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    Ditentukan dari hasil klasifikasi.
                </div>
            </div>

            {{-- Confidence (bar + angka) --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-gray-500">Confidence</div>
                    <div class="text-xs font-semibold text-gray-700">
                        {{ $confidencePctText }}
                    </div>
                </div>

                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    <div
                        class="h-full rounded-full bg-blue-600"
                        style="width: {{ (int) max(0, min(100, $confidencePercent ?? 0)) }}%;"
                    ></div>
                </div>

                <div class="mt-1 text-[11px] text-gray-500">
                    Skor: {{ $confidenceText }}
                </div>
            </div>

            {{-- Alasan --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4 sm:col-span-2">
                <div class="text-xs font-medium text-gray-500">Alasan Singkat</div>
                <div class="mt-2 text-sm text-gray-900 whitespace-pre-line leading-relaxed">
                    {{ $prioritas->alasan_singkat ?: '-' }}
                </div>
            </div>
        </div>

        {{-- Meta info --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <div class="text-xs font-medium text-gray-500">Model</div>
                    <div class="mt-1 text-sm font-medium text-gray-900">
                        {{ $prioritas->model ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-medium text-gray-500">Vector Store</div>
                    <div class="mt-1 text-sm font-medium text-gray-900">
                        {{ $prioritas->vector_store_id ?? '-' }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-medium text-gray-500">Diklasifikasi</div>
                    <div class="mt-1 text-sm font-medium text-gray-900">
                        {{ $classifiedAt }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
