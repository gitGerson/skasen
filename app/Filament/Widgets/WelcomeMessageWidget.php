<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

class WelcomeMessageWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.welcome-message-widget';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $user = Filament::auth()->user();
        $now = now();
        $hour = (int) $now->format('H');

        [$greeting, $message] = match (true) {
            $hour < 11 => ['Selamat pagi', 'Semoga kegiatan belajar hari ini berjalan lancar. Jika ada saran, aspirasi, atau kendala di lingkungan sekolah, kamu bisa menyampaikannya melalui portal ini.'],
            $hour < 15 => ['Selamat siang', 'Semoga aktivitas belajar hari ini tetap semangat. Gunakan portal ini untuk menyampaikan masukan atau memantau aspirasi yang pernah kamu kirim.'],
            $hour < 18 => ['Selamat sore', 'Semoga harimu berjalan baik. Jika ada hal yang ingin disampaikan terkait kegiatan sekolah, portal ini siap membantu menampung aspirasi kamu.'],
            default => ['Selamat malam', 'Saat yang tenang untuk meninjau aspirasi yang sudah kamu kirim atau menuliskan masukan baru dengan lebih jelas dan terarah.'],
        };

        $displayName = trim((string) ($user?->name ?? 'Siswa'));
        $shortName = Str::of($displayName)->explode(' ')->take(2)->implode(' ');

        return [
            'user' => $user,
            'displayName' => $shortName,
            'greeting' => $greeting,
            'message' => $message,
            'portalLabel' => 'Portal Aspirasi Siswa',
        ];
    }

    public static function canView(): bool
    {
        return Filament::auth()->check();
    }
}
