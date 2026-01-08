<?php

namespace App\Filament\Widgets;

use App\Models\Aspirasi;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SiswaOverview2 extends StatsOverviewWidget
{
    private const STATUS_BELUM = 'Belum Ditindaklanjuti';
    private const STATUS_SEDANG = 'Sedang Ditindaklanjuti';
    private const STATUS_SELESAI = 'Selesai';

    protected ?string $heading = 'Status Tindak Lanjut';

    protected function getStats(): array
    {
        $query = $this->getScopedQuery();

        $belum = (clone $query)->where('status', self::STATUS_BELUM)->count();
        $sedang = (clone $query)->where('status', self::STATUS_SEDANG)->count();
        $selesai = (clone $query)->where('status', self::STATUS_SELESAI)->count();

        return [
            Stat::make('Belum Ditindaklanjuti', $belum . ' Laporan')
                ->color('primary'),
            Stat::make('Sedang Ditindaklanjuti', $sedang . ' Laporan')
                ->color('warning'),
            Stat::make('Sudah Ditindaklanjuti', $selesai . ' Laporan')
                ->color('success'),
        ];
    }

    protected function getColumns(): int | array | null
    {
        return 3;
    }

    protected function getScopedQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = Aspirasi::query();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $this->viewAdminWidgets()) {
            $query->where('user_id', $user->getAuthIdentifier());
        }

        return $query;
    }

    protected function viewAdminWidgets(): bool
    {
        $user = Filament::auth()->user();

        return $user?->can('viewAny', Aspirasi::class) ?? false;
    }

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('viewAny', Aspirasi::class)
            || $user->can('view', new Aspirasi());
    }
}
