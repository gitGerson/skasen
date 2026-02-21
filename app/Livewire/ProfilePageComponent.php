<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class ProfilePageComponent extends MyProfileComponent
{
    protected string $view = "livewire.profile-page-component";

    public array $only = ['name', 'email', 'nis', 'tempat_lahir', 'tanggal_lahir', 'no_telepon', 'avatar_url'];
    public array $data;
    public $user;
    public $userClass;

    public function mount()
    {
        $this->user = Filament::getCurrentPanel()->auth()->user();
        $this->userClass = get_class($this->user);

        $this->form->fill($this->user->only($this->only));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Avatar Section
                FileUpload::make('avatar_url')
                    ->hiddenLabel()
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->directory('avatars')
                    ->circleCropper()
                    ->alignCenter()
                    ->columnSpanFull(),

                // Personal Info Section
                Section::make('Informasi Pribadi')
                    ->description('Kelola informasi pribadi Anda.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('nis')
                                    ->label('Nomor Induk Siswa Nasional')
                                    ->maxLength(50),

                                TextInput::make('tempat_lahir')
                                    ->label('Tempat Lahir')
                                    ->maxLength(255),

                                DatePicker::make('tanggal_lahir')
                                    ->label('Tgl Lahir')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),

                                TextInput::make('no_telepon')
                                    ->label('Nomor Telepon Aktif')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                    ]),

                // Password Section
                Section::make('Ubah Kata Sandi')
                    ->description('Perbarui kata sandi akun Anda.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('current_password')
                                    ->label('Kata Sandi Saat Ini')
                                    ->password()
                                    ->revealable()
                                    ->currentPassword()
                                    ->requiredWith('new_password'),

                                TextInput::make('new_password')
                                    ->label('Kata Sandi Baru')
                                    ->password()
                                    ->revealable()
                                    ->rule(Password::default())
                                    ->autocomplete('new-password')
                                    ->dehydrated(fn($state) => filled($state)),

                                TextInput::make('new_password_confirmation')
                                    ->label('Konfirmasi Kata Sandi Baru')
                                    ->password()
                                    ->revealable()
                                    ->same('new_password')
                                    ->requiredWith('new_password'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Update personal info
        $personalInfo = collect($data)->only($this->only)->all();
        $this->user->update($personalInfo);

        // Update password if provided
        if (filled($data['new_password'] ?? null)) {
            $this->user->update([
                'password' => Hash::make($data['new_password']),
            ]);
        }

        Notification::make()
            ->success()
            ->title(__('Profil berhasil diperbarui'))
            ->send();
    }
}
