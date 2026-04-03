<?php

namespace App\Filament\Widgets;

use App\Models\Aspirasi;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SiswaOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Verifikasi';

    protected static ?int $sort = 2;


    protected function getStats(): array
    {
        $query = $this->getScopedQuery();

        $unverified = (clone $query)->where('is_verify', false)->count();
        $verified = (clone $query)->where('is_verify', true)->count();
        $total = $unverified + $verified;

        return [
            Stat::make('Belum Terverifikasi', $unverified . ' Laporan')
                ->description($unverified > 0 ? 'Masih menunggu proses verifikasi.' : ($total > 0 ? 'Tidak ada antrean verifikasi.' : 'Belum ada data untuk diverifikasi.'))
                ->descriptionIcon($unverified > 0 ? Heroicon::OutlinedClock : Heroicon::OutlinedCheckCircle)
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->color($unverified > 0 ? 'primary' : 'gray'),
            Stat::make('Sudah Terverifikasi', $verified . ' Laporan')
                ->description($verified > 0 ? 'Sudah lolos proses verifikasi.' : ($total > 0 ? 'Belum ada laporan yang selesai diverifikasi.' : 'Belum ada data verifikasi.'))
                ->descriptionIcon($verified > 0 ? Heroicon::OutlinedCheckBadge : Heroicon::OutlinedInformationCircle)
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color($verified > 0 ? 'success' : 'gray'),
        ];
    }

    protected function getColumns(): int|array|null
    {
        return 2;
    }

    protected function getScopedQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = Aspirasi::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (!$this->viewAdminWidgets()) {
            $query->where('user_id', $user->getAuthIdentifier());
        }

        return $query;
    }

    protected function viewAdminWidgets(): bool
    {
        $user = Filament::auth()->user();

        return $user?->can('viewAdminWidgets', Aspirasi::class) ?? false;
    }

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return false;
        }

        return $user->can('viewAdminWidgets', Aspirasi::class)
            || $user->can('view', new Aspirasi());
    }
}
