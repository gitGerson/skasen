<?php

namespace App\Filament\Resources\Aspirasis\Pages;

use App\Filament\Resources\Aspirasis\AspirasiResource;
use App\Models\Aspirasi;
use App\Services\PriorityClassifier;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListAspirasis extends ListRecords
{
    protected static string $resource = AspirasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_unclassified')
                ->label('Klasifikasi Belum Terproses')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'bk']) ?? false)
                ->form([
                    TextInput::make('limit')
                        ->label('Batas data diproses')
                        ->numeric()
                        ->default(30)
                        ->minValue(1)
                        ->maxValue(30)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Proses klasifikasi aspirasi belum terproses')
                ->modalDescription('Aksi ini akan mencari aspirasi yang belum memiliki prioritas lalu menjalankan klasifikasi.')
                ->action(function (array $data): void {
                    $limit = (int) ($data['limit'] ?? 50);
                    $classifier = app(PriorityClassifier::class);

                    $processed = 0;
                    $success = 0;
                    $failed = 0;
                    $skipped = 0;

                    Aspirasi::query()
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
                        ->orderBy('id')
                        ->limit($limit)
                        ->get()
                        ->each(function (Aspirasi $aspirasi) use ($classifier, &$processed, &$success, &$failed, &$skipped): void {
                            $processed++;
                            $kategori = $aspirasi->kategori?->name;
                            $text = trim(strip_tags((string) $aspirasi->keterangan));

                            if ($kategori === null || $kategori === '' || $text === '') {
                                $skipped++;
                                return;
                            }

                            try {
                                $result = $classifier->classify($kategori, $text);
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
                                $success++;
                            } catch (Throwable $exception) {
                                $failed++;
                                Log::warning('Failed to classify aspirasi from list action.', [
                                    'aspirasi_id' => $aspirasi->id,
                                    'error' => $exception->getMessage(),
                                ]);
                            }
                        });

                    Notification::make()
                        ->title('Proses klasifikasi selesai')
                        ->body("Diproses: {$processed} | Berhasil: {$success} | Gagal: {$failed} | Diskip: {$skipped}")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
