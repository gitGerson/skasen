<?php

namespace App\Filament\Resources\Aspirasis\Tables;

use App\Models\Aspirasi;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class AspirasisTable
{
    public static function configure(Table $table): Table
    {
        $columns = [
            TextColumn::make('user.name')
                ->label('Pengirim')
                ->formatStateUsing(fn($state, $record) => self::canViewIdentity($record) ? $state : 'Anonim')
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('user.nis')
                ->label('NIS')
                ->formatStateUsing(fn($state, $record) => self::canViewIdentity($record) ? $state : 'Anonim')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('tujuan.name')
                ->label('Tujuan')
                ->badge()
                ->sortable()
                ->searchable(),
            TextColumn::make('kategori.name')
                ->label('Kategori')
                ->badge()
                ->sortable()
                ->searchable(),
            TextColumn::make('prioritas.prioritas')
                ->label('Prioritas')
                ->badge()
                ->placeholder('Belum diklasifikasi')
                ->tooltip('Klik untuk detail klasifikasi')
                ->color(fn(?string $state): string => match ($state) {
                    'Tinggi' => 'danger',
                    'Sedang' => 'warning',
                    'Rendah' => 'success',
                    default => 'gray',
                })
                ->action(
                    Action::make('priorityDetail')
                        // ->label('Detail Klasifikasi')
                        // ->modalHeading('Detail Klasifikasi')
                        // ->modalDescription(fn (Aspirasi $record): string => 'Aspirasi #' . $record->id)
                        ->modalContent(fn(Aspirasi $record) => view('filament.aspirasi.priority-detail', [
                            'record' => $record,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                ),
        ];

        if (self::canManageStatus()) {
            $columns[] = SelectColumn::make('status')
                ->label('Status')
                ->options([
                    'Belum Ditindaklanjuti' => 'Belum Ditindaklanjuti',
                    'Sedang Ditindaklanjuti' => 'Sedang Ditindaklanjuti',
                    'Selesai' => 'Selesai',
                ])->selectablePlaceholder(false)
                ->sortable();

            $columns[] = ToggleColumn::make('is_verify')
                ->label('Terverifikasi')
                ->sortable();
        } else {
            $columns[] = TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->colors([
                    'warning' => 'Belum Ditindaklanjuti',
                    'info' => 'Sedang Ditindaklanjuti',
                    'success' => 'Selesai',
                ])
                ->sortable();

            $columns[] = IconColumn::make('is_verify')
                ->label('Terverifikasi')
                ->boolean()
                ->sortable();
        }

        $columns = [
            ...$columns,
            IconColumn::make('is_anonymous')
                ->label('Anonim')
                ->boolean(),
            TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime()
                ->sortable()
                ->toggleable(),
        ];

        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('prioritas'))
            ->columns($columns)
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Belum Ditindaklanjuti' => 'Belum Ditindaklanjuti',
                        'Sedang Ditindaklanjuti' => 'Sedang Ditindaklanjuti',
                        'Selesai' => 'Selesai',
                    ])->selectablePlaceholder(false),
                SelectFilter::make('prioritas.prioritas')
                    ->label('Prioritas'),
                TernaryFilter::make('is_anonymous')
                    ->label('Anonim'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                Action::make('download_pdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->authorize('downloadPdf')
                    ->action(fn() => self::downloadPdf()),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize('deleteAny'),
                ]),
            ]);
    }

    protected static function canViewIdentity(Model $record): bool
    {
        if (!$record->is_anonymous) {
            return true;
        }

        $user = Filament::auth()->user();

        return $user?->can('viewIdentity', $record) ?? false;
    }

    protected static function canManageStatus(): bool
    {
        $user = Filament::auth()->user();

        return $user?->hasAnyRole(['super_admin', 'bk']) ?? false;
    }

    protected static function canDownloadPdf(): bool
    {
        $user = Filament::auth()->user();

        return $user?->hasAnyRole(['super_admin', 'bk']) ?? false;
    }

    protected static function downloadPdf(): StreamedResponse
    {
        $records = Aspirasi::query()
            ->with(['user', 'tujuan', 'kategori'])
            ->orderByDesc('created_at')
            ->get();

        $now = now()->format('d/m/Y H:i');
        $headerImagePath = public_path('export/KOP.jpg');

        $pdf = Pdf::loadView('pdf.pdf', [
            'records' => $records,
            'downloadedAt' => $now,
            'headerImagePath' => $headerImagePath,
        ]);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'aspirasi-' . now()->format('d-m-Y') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
