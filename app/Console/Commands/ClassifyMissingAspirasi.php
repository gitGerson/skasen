<?php

namespace App\Console\Commands;

use App\Models\Aspirasi;
use App\Services\PriorityClassifier;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassifyMissingAspirasi extends Command
{
    protected $signature = 'aspirasi:classify-missing
        {--chunk=100 : Jumlah data per batch}
        {--limit=0 : Batas total data yang diproses (0 = semua)}
        {--dry-run : Simulasi tanpa menyimpan hasil}';

    protected $description = 'Klasifikasikan aspirasi yang belum memiliki prioritas';

    public function handle(PriorityClassifier $classifier): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $processed = 0;
        $classified = 0;
        $failed = 0;
        $skipped = 0;
        $stop = false;

        $query = Aspirasi::query()
            ->with('kategori')
            ->where(function (Builder $builder): void {
                $builder
                    ->doesntHave('prioritas')
                    ->orWhereHas('prioritas', function (Builder $prioritasBuilder): void {
                        $prioritasBuilder
                            ->whereNull('prioritas')
                            ->orWhereNull('classified_at');
                    });
            })
            ->orderBy('id');

        $totalCandidate = (clone $query)->count();
        $this->info("Kandidat aspirasi belum terklasifikasi: {$totalCandidate}");
        $this->info($dryRun ? 'Mode: DRY RUN (tidak ada penyimpanan)' : 'Mode: SIMPAN HASIL');

        $query->chunkById($chunkSize, function ($aspirasis) use (
            $classifier,
            $limit,
            $dryRun,
            &$processed,
            &$classified,
            &$failed,
            &$skipped,
            &$stop
        ): void {
            foreach ($aspirasis as $aspirasi) {
                if ($limit > 0 && $processed >= $limit) {
                    $stop = true;

                    return;
                }

                $processed++;

                $kategori = $aspirasi->kategori?->name;
                $text = trim(strip_tags((string) $aspirasi->keterangan));

                if ($kategori === null || $kategori === '' || $text === '') {
                    $skipped++;
                    Log::warning('Skip classify missing aspirasi karena data tidak lengkap.', [
                        'aspirasi_id' => $aspirasi->id,
                        'has_kategori' => $kategori !== null && $kategori !== '',
                        'text_length' => strlen($text),
                    ]);

                    continue;
                }

                try {
                    $result = $classifier->classify($kategori, $text);

                    if (! $dryRun) {
                        $aspirasi->prioritas()->updateOrCreate(
                            ['aspirasi_id' => $aspirasi->id],
                            [
                                'prioritas' => $result['prioritas'],
                                'confidence' => $result['confidence'],
                                'alasan_singkat' => $result['alasan_singkat'],
                                'model' => config('services.groq.model_priority', 'meta-llama/llama-4-scout-17b-16e-instruct'),
                                'vector_store_id' => null,
                                'classified_at' => now(),
                            ]
                        );
                    }

                    $classified++;
                    $this->line("OK aspirasi_id={$aspirasi->id} prioritas={$result['prioritas']}");
                } catch (Throwable $exception) {
                    $failed++;

                    Log::warning('Gagal classify missing aspirasi.', [
                        'aspirasi_id' => $aspirasi->id,
                        'error' => $exception->getMessage(),
                    ]);

                    $this->warn("FAIL aspirasi_id={$aspirasi->id}");
                }
            }
        });

        $this->newLine();
        $this->info('Selesai klasifikasi missing aspirasi.');
        $this->line("Diproses   : {$processed}");
        $this->line("Berhasil   : {$classified}");
        $this->line("Gagal      : {$failed}");
        $this->line("Diskip     : {$skipped}");
        $this->line('Dihentikan : '.($stop ? 'ya (mencapai limit)' : 'tidak'));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
