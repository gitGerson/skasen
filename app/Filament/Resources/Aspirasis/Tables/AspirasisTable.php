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
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class AspirasisTable
{
    protected const STATUS_OPTIONS = [
        'Belum Ditindaklanjuti' => 'Belum Ditindaklanjuti',
        'Sedang Ditindaklanjuti' => 'Sedang Ditindaklanjuti',
        'Selesai' => 'Selesai',
    ];

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
                ->label('NISN')
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
                ->options(self::STATUS_OPTIONS)
                ->selectablePlaceholder(false)
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
                    ->label('Status')
                    ->options(self::STATUS_OPTIONS)
                    ->placeholder('Semua status'),
                TernaryFilter::make('is_verify')
                    ->label('Status Verifikasi')
                    ->placeholder('Semua')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum Terverifikasi'),
                SelectFilter::make('prioritas.prioritas')
                    ->label('Prioritas')
                    ->placeholder('Semua prioritas'),
                DateRangeFilter::make('created_at')
                    ->label('Rentang Waktu Dibuat')
                    ->minDate(Carbon::create(2020, 1, 1))
                    ->maxDate(Carbon::now()),
                TernaryFilter::make('is_anonymous')
                    ->label('Anonim')
                    ->placeholder('Semua')
                    ->trueLabel('Anonim')
                    ->falseLabel('Tidak anonim'),
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
                    ->action(fn (?Component $livewire = null) => self::downloadPdf($livewire)),
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

    protected static function downloadPdf(?Component $livewire = null): StreamedResponse
    {
        $query = Aspirasi::query()
            ->with(['user', 'tujuan', 'kategori'])
            ->orderByDesc('created_at');

        if ($livewire) {
            self::applyActiveTableFilters($query, $livewire);
        }

        $records = $query->get();

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

    protected static function applyActiveTableFilters(Builder $query, Component $livewire): void
    {
        $filters = is_array($livewire->tableFilters ?? null) ? $livewire->tableFilters : [];

        $status = Arr::get($filters, 'status.value');
        if (filled($status)) {
            $query->where('status', $status);
        }

        $isVerify = Arr::get($filters, 'is_verify.value');
        if (is_bool($isVerify)) {
            $query->where('is_verify', $isVerify);
        }

        $isAnonymous = Arr::get($filters, 'is_anonymous.value');
        if (is_bool($isAnonymous)) {
            $query->where('is_anonymous', $isAnonymous);
        }

        $prioritas = Arr::get($filters, 'prioritas.prioritas.value');
        if (filled($prioritas)) {
            $query->whereHas('prioritas', fn (Builder $builder) => $builder->where('prioritas', $prioritas));
        }

        $dateRange = Arr::get($filters, 'created_at.created_at');
        [$startDate, $endDate] = self::parseDateRange($dateRange);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
    }

    protected static function parseDateRange(mixed $dateRange): array
    {
        if (! is_string($dateRange) || ! str_contains($dateRange, ' - ')) {
            return [null, null];
        }

        [$startRaw, $endRaw] = array_map('trim', explode(' - ', $dateRange, 2));

        try {
            $startDate = Carbon::createFromFormat('d/m/Y', $startRaw)->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $endRaw)->endOfDay();
        } catch (\Throwable) {
            return [null, null];
        }

        return [$startDate, $endDate];
    }
}
