<?php

namespace App\Filament\Resources\Aspirasis\Schemas;

use App\Models\Aspirasi;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                        Select::make('user_gender')
                            ->label('Jenis Kelamin')
                            ->disabled()
                            ->dehydrated(false)
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->columnSpan(1)
                            ->default(fn (?Model $record) => self::resolveIdentityValues($record)[2] ?? null)
                            ->afterStateHydrated(function (Select $component, $state, ?Model $record) {
                                ['gender' => $gender] = self::resolveDisplayIdentity($record);

                                $component->state($gender);
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
        [$name, $nis, $gender] = self::resolveIdentityValues($record, $get);
        $showIdentity = self::shouldRevealIdentity($record, $isAnonymous);

        $set('user_name', $showIdentity ? $name : 'Anonim');
        $set('user_nis', $showIdentity ? $nis : 'Anonim');
        $set('user_gender', $showIdentity ? $gender : null);
    }

    protected static function resolveIdentityValues(?Model $record, ?Get $get = null): array
    {
        $userId = $record?->user_id ?? $get?->integer('user_id', true) ?? auth()->id();

        if (! $userId) {
            return [null, null];
        }

        $user = User::find($userId);

        return [$user?->name, $user?->nis, $user?->gender];
    }

    protected static function resolveDisplayIdentity(?Model $record): array
    {
        [$name, $nis, $gender] = self::resolveIdentityValues($record);
        $isAnonymous = $record?->is_anonymous ?? false;
        $showIdentity = self::shouldRevealIdentity($record, $isAnonymous);

        return [
            'name' => $showIdentity ? $name : 'Anonim',
            'nis' => $showIdentity ? $nis : 'Anonim',
            'gender' => $showIdentity ? $gender : null,
        ];
    }

    protected static function shouldRevealIdentity(?Model $record, bool $isAnonymous): bool
    {
        if (! $isAnonymous) {
            return true;
        }

        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $recordForCheck = $record instanceof Aspirasi
            ? clone $record
            : new Aspirasi(['user_id' => auth()->id()]);

        $recordForCheck->is_anonymous = $isAnonymous;

        return $user->can('viewIdentity', $recordForCheck);
    }
}
