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
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AspirasisTable
{
    public static function configure(Table $table): Table
    {
        $columns = [
            TextColumn::make('user.name')
                ->label('Pengirim')
                ->formatStateUsing(fn ($state, $record) => self::canViewIdentity($record) ? $state : 'Anonim')
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('user.nis')
                ->label('NIS')
                ->formatStateUsing(fn ($state, $record) => self::canViewIdentity($record) ? $state : 'Anonim')
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
            ->columns($columns)
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Belum Ditindaklanjuti' => 'Belum Ditindaklanjuti',
                        'Sedang Ditindaklanjuti' => 'Sedang Ditindaklanjuti',
                        'Selesai' => 'Selesai',
                    ])->selectablePlaceholder(false),
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
                    ->visible(fn () => self::canDownloadPdf())
                    ->action(fn () => self::downloadPdf()),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function canViewIdentity(Model $record): bool
    {
        if (!$record->is_anonymous) {
            return true;
        }

        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'bk', 'siswa']);
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

        $lines = [
            'Laporan Aspirasi',
            "Diunduh: {$now}",
            ' ',
            'No | Nama | NIS | Tujuan | Kategori | Status | Anonim | Dibuat',
        ];

        foreach ($records as $index => $record) {
            $canViewIdentity = self::canViewIdentity($record);
            $name = $canViewIdentity ? ($record->user?->name ?? '-') : 'Anonim';
            $nis = $canViewIdentity ? ($record->user?->nis ?? '-') : 'Anonim';
            $lines[] = sprintf(
                '%d | %s | %s | %s | %s | %s | %s | %s',
                $index + 1,
                $name ?? '-',
                $nis ?? '-',
                $record->tujuan?->name ?? '-',
                $record->kategori?->name ?? '-',
                $record->status ?? '-',
                $record->is_anonymous ? 'Ya' : 'Tidak',
                optional($record->created_at)?->format('d/m/Y H:i') ?? '-'
            );
        }

        $pdf = self::buildSimplePdf($lines);

        return response()->streamDownload(
            fn () => print($pdf),
            'aspirasi.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    protected static function buildSimplePdf(array $lines): string
    {
        $escape = fn (string $text): string => str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text
        );

        $contentLines = array_map($escape, $lines);
        $leading = 16;
        $startY = 780;
        $stream = "BT\n/F1 10 Tf\n1 0 0 1 50 {$startY} Tm\n{$leading} TL\n";
        $first = true;

        foreach ($contentLines as $line) {
            if (! $first) {
                $stream .= "T*\n";
            }

            $stream .= "({$line}) Tj\n";
            $first = false;
        }

        $stream .= "ET";

        $streamLength = strlen($stream);

        $objects = [];
        $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $objects[] = "4 0 obj<< /Length {$streamLength} >>\nstream\n{$stream}\nendstream\nendobj\n";
        $objects[] = "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefPosition = strlen($pdf);
        $totalObjects = count($objects) + 1;

        $pdf .= "xref\n0 {$totalObjects}\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer<< /Size {$totalObjects} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPosition}\n%%EOF";

        return $pdf;
    }
}
