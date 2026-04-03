<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Validation\Rules\Password;

class ProfilePasswordForm
{
    public static function makeSection(): Section
    {
        return Section::make('Ubah Kata Sandi')
            ->description('Perbarui kata sandi akun Anda.')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Kata Sandi Saat Ini')
                            ->password()
                            ->revealable()
                            ->currentPassword()
                            ->required(),

                        TextInput::make('new_password')
                            ->label('Kata Sandi Baru')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->required()
                            ->dehydrated(fn ($state) => filled($state)),

                        TextInput::make('new_password_confirmation')
                            ->label('Konfirmasi Kata Sandi Baru')
                            ->password()
                            ->revealable()
                            ->same('new_password')
                            ->required(),
                    ]),
            ]);
    }
}
