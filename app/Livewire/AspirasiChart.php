<?php

namespace App\Livewire;

use Filament\Widgets\ChartWidget;

class AspirasiChart extends ChartWidget
{
    protected ?string $heading = 'Aspirasi Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
