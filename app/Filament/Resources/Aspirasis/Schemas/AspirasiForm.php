<?php

namespace App\Filament\Resources\Aspirasis\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class AspirasiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Form Aspirasi')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        Hidden::make('user_id')
                            ->default(fn() => auth()->id())
                            ->required(),
                        TextInput::make('user_name')
                            ->label('Nama Pengirim')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1)
                            ->default(fn (?Model $record) => self::resolveIdentityValues($record)[0])
                            ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                                ['name' => $name] = self::resolveDisplayIdentity($record);

                                $component->state($name);
                            }),
                        TextInput::make('user_nis')
                            ->label('NIS')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1)
                            ->default(fn (?Model $record) => self::resolveIdentityValues($record)[1])
                            ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                                ['nis' => $nis] = self::resolveDisplayIdentity($record);

                                $component->state($nis);
                            }),
                        Select::make('tujuan_id')
                            ->label('Tujuan Aspirasi')
                            ->relationship('tujuan', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        Select::make('kategori_id')
                            ->label('Kategori')
                            ->relationship('kategori', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        RichEditor::make('keterangan')
                            ->label('Keterangan')
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->label('Foto (opsional)')
                            ->directory('aspirasi')
                            ->image()
                            ->columnSpanFull(),
                        Toggle::make('is_anonymous')
                            ->label('Kirim sebagai anonim')
                            ->helperText('Jika diaktifkan, identitas pengirim tidak ditampilkan.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?Model $record, bool $state) {
                                self::syncIdentityFields($set, $get, $record, $state);
                            })
                            ->columnSpan(2),
                    ]),
            ]);
    }

    protected static function syncIdentityFields(Set $set, Get $get, ?Model $record, bool $isAnonymous): void
    {
        [$name, $nis] = self::resolveIdentityValues($record, $get);
        $showIdentity = self::shouldRevealIdentity($isAnonymous);

        $set('user_name', $showIdentity ? $name : 'Anonim');
        $set('user_nis', $showIdentity ? $nis : 'Anonim');
    }

    protected static function resolveIdentityValues(?Model $record, ?Get $get = null): array
    {
        $userId = $record?->user_id ?? $get?->get('user_id') ?? auth()->id();

        if (! $userId) {
            return [null, null];
        }

        $user = User::find($userId);

        return [$user?->name, $user?->nis];
    }

    protected static function resolveDisplayIdentity(?Model $record): array
    {
        [$name, $nis] = self::resolveIdentityValues($record);
        $isAnonymous = $record?->is_anonymous ?? false;
        $showIdentity = self::shouldRevealIdentity($isAnonymous);

        return [
            'name' => $showIdentity ? $name : 'Anonim',
            'nis' => $showIdentity ? $nis : 'Anonim',
        ];
    }

    protected static function shouldRevealIdentity(bool $isAnonymous): bool
    {
        if (! $isAnonymous) {
            return true;
        }

        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'siswa']) ?? false;
    }
}
