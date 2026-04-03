<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class ProfilePageComponent extends MyProfileComponent
{
    protected string $view = "livewire.profile-page-component";

    public array $only = ['name', 'email', 'nis', 'tempat_lahir', 'tanggal_lahir', 'no_telepon', 'gender', 'avatar_url'];
    public array $updatable = ['name', 'email', 'tempat_lahir', 'tanggal_lahir', 'no_telepon', 'gender', 'avatar_url'];
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
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->numeric()
                                    ->validationMessages([
                                        'regex' => 'NIS harus berupa angka',
                                    ])
                                    ->maxLength(50),

                                TextInput::make('tempat_lahir')
                                    ->label('Tempat Lahir')
                                    ->maxLength(255),

                                DatePicker::make('tanggal_lahir')
                                    ->label('Tanggal Lahir')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),

                                TextInput::make('no_telepon')
                                    ->label('Nomor Telepon Aktif')
                                    ->tel()
                                    ->maxLength(20),

                                Select::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'L' => 'Laki-laki',
                                        'P' => 'Perempuan',
                                    ]),
                            ]),
                    ]),

            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Update personal info
        $personalInfo = collect($data)->only($this->updatable)->all();
        $this->user->update($personalInfo);

        Notification::make()
            ->success()
            ->title(__('Profil berhasil diperbarui'))
            ->send();
    }
}
