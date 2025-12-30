<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiswaOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Unique views', '192.1k'),
            Stat::make('Bounce rate', '21%'),
        ];
    }
    protected ?string $heading = 'Verifikasi';

    protected function getColumns(): int | array | null
    {
        return 2;
    }
}
