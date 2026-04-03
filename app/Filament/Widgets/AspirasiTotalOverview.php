<?php

namespace App\Filament\Widgets;

use App\Models\Aspirasi;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AspirasiTotalOverview extends StatsOverviewWidget
{
    protected ?string $heading = null;

    protected static ?int $sort = 1;


    protected function getStats(): array
    {
        $query = $this->getScopedQuery();
        $total = (clone $query)->count();

        $label = $this->canViewAdminWidgets()
            ? 'Total Aspirasi Masuk'
            : 'Total Aspirasi Kamu';

        return [
            Stat::make($label, $total . ' Laporan')
                ->description($total > 0 ? 'Ringkasan jumlah aspirasi yang sudah tercatat.' : 'Belum ada aspirasi yang tercatat.')
                ->descriptionIcon($total > 0 ? Heroicon::OutlinedInboxStack : Heroicon::OutlinedInformationCircle)
                ->icon(Heroicon::OutlinedDocumentText)
                ->color($total > 0 ? 'warning' : 'gray'),
        ];
    }

    protected function getColumns(): int|array|null
    {
        return 1;
    }

    protected function getScopedQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = Aspirasi::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (!$this->canViewAdminWidgets()) {
            $query->where('user_id', $user->getAuthIdentifier());
        }

        return $query;
    }

    protected function canViewAdminWidgets(): bool
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
