<?php

namespace App\Filament\Resources\Aspirasis\Pages;

use App\Filament\Resources\Aspirasis\AspirasiResource;
use App\Services\PriorityClassifier;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateAspirasi extends CreateRecord
{
    protected static string $resource = AspirasiResource::class;

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if (! $record) {
            return;
        }

        DB::afterCommit(function () use ($record): void {
            $record->loadMissing('kategori');

            $kategori = $record->kategori?->name;
            $text = trim(strip_tags((string) $record->keterangan));

            if (! $kategori || $text === '') {
                Log::warning('Aspirasi missing data for classification.', [
                    'aspirasi_id' => $record->id,
                ]);

                return;
            }

            try {
                $classifier = app(PriorityClassifier::class);
                $result = $classifier->classify($kategori, $text);

                $record->prioritas()->updateOrCreate(
                    ['aspirasi_id' => $record->id],
                    [
                        'prioritas' => $result['prioritas'],
                        'confidence' => $result['confidence'],
                        'alasan_singkat' => $result['alasan_singkat'],
                        'model' => config('openai.model_priority', 'gpt-4o-mini'),
                        'vector_store_id' => config('openai.skasen_vector_store_id'),
                        'classified_at' => now(),
                    ]
                );
            } catch (Throwable $exception) {
                Log::warning('Failed to classify aspirasi.', [
                    'aspirasi_id' => $record->id,
                    'error' => $exception->getMessage(),
                ]);

                Notification::make()
                    ->warning()
                    ->title('Klasifikasi prioritas gagal')
                    ->body('Aspirasi tersimpan, tetapi prioritas belum terklasifikasi.')
                    ->send();
            }
        });
    }
}
