<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->confirmed()
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create'),
                TextInput::make('nis')
                    ->label('NISN / Nomor Induk Pegawai')
                    ->disabledOn('edit')
                    ->inputMode('numeric')
                    ->rule('regex:/^[0-9]+$/')
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'regex' => 'NIS/NIP harus berupa angka',
                        'unique' => 'NIS/NIP sudah terdaftar',
                    ])
                    ->required(),
                TextInput::make('password_confirmation')
                    ->required(fn(string $context): bool => $context === 'create')
                    ->password()
                    ->dehydrated(false),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Select::make('roles')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->label('Roles'),
                Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
            ]);
    }
}
