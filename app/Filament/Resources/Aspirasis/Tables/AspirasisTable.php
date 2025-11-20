<?php

namespace App\Filament\Resources\Aspirasis\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class AspirasisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'Belum Ditindaklanjuti',
                        'info' => 'Sedang Ditindaklanjuti',
                        'success' => 'Selesai',
                    ])
                    ->sortable(),
                IconColumn::make('is_anonymous')
                    ->label('Anonim')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Belum Ditindaklanjuti' => 'Belum Ditindaklanjuti',
                        'Sedang Ditindaklanjuti' => 'Sedang Ditindaklanjuti',
                        'Selesai' => 'Selesai',
                    ]),
                TernaryFilter::make('is_anonymous')
                    ->label('Anonim'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
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

        return $user?->hasAnyRole(['admin', 'siswa']);
    }
}
