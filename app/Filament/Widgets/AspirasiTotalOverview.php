<?php

namespace App\Filament\Widgets;

use App\Models\Aspirasi;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AspirasiTotalOverview extends StatsOverviewWidget
{
    protected ?string $heading = null;

    protected function getStats(): array
    {
        $query = $this->getScopedQuery();
        $total = (clone $query)->count();

        $label = $this->canViewAny()
            ? 'Total Aspirasi Masuk'
            : 'Total Aspirasi Kamu';

        return [
            Stat::make($label, $total . ' Laporan')
                ->color('warning'),
        ];
    }

    protected function getColumns(): int | array | null
    {
        return 1;
    }

    protected function getScopedQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = Aspirasi::query();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $this->canViewAny()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    protected function canViewAny(): bool
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
