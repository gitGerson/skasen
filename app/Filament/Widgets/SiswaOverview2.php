<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiswaOverview2 extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Average time on page', '3:12'),
            Stat::make('Bounce rate', '21%'),
            Stat::make('Average time on page', '3:12'),
        ];
    }
    protected ?string $heading = 'Status Tindak Lanjut';
}
