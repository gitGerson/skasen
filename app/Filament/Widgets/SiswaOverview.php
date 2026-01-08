<?php

namespace App\Filament\Widgets;

use App\Models\Aspirasi;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SiswaOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Verifikasi';

    protected function getStats(): array
    {
        $query = $this->getScopedQuery();

        $unverified = (clone $query)->where('is_verify', false)->count();
        $verified = (clone $query)->where('is_verify', true)->count();

        return [
            Stat::make('Belum Terverifikasi', $unverified . ' Laporan')
                ->color('primary'),
            Stat::make('Sudah Terverifikasi', $verified . ' Laporan')
                ->color('success'),
        ];
    }

    protected function getColumns(): int | array | null
    {
        return 2;
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

        return $user?->can('view_admin_widgets', Aspirasi::class) ?? false;
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
