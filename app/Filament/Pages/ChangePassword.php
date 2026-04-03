<?php

namespace App\Filament\Pages;

use App\Filament\Support\ProfilePasswordForm;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected string $view = 'filament.pages.change-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'change-password';
    }

    public static function getLabel(): string
    {
        return 'Ganti Password';
    }

    public function getTitle(): string|Htmlable
    {
        return static::getLabel();
    }

    public function getHeading(): string|Htmlable
    {
        return static::getLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Perbarui kata sandi akun Anda.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ProfilePasswordForm::makeSection(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        Filament::auth()->user()->update([
            'password' => Hash::make($data['new_password']),
        ]);

        $this->form->fill();

        Notification::make()
            ->success()
            ->title('Kata sandi berhasil diperbarui')
            ->send();
    }
}
