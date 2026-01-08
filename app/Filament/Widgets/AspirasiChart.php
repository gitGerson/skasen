<?php

namespace App\Filament\Widgets;

use App\Models\Aspirasi;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class AspirasiChart extends ChartWidget
{
    private const STATUS_BELUM = 'Belum Ditindaklanjuti';

    protected ?string $heading = 'Grafik Perkembangan Aspirasi';

    protected static ?int $sort = 99;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return $this->emptyData();
        }

        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();
        $monthMap = $this->buildMonthMap($start, $end);

        $query = $this->getScopedQuery($user)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN is_verify = 1 THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw(
                'SUM(CASE WHEN status != ? THEN 1 ELSE 0 END) as tindak_count',
                [self::STATUS_BELUM]
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $totals = $monthMap;
        $verified = $monthMap;
        $tindak = $monthMap;

        foreach ($query as $row) {
            $key = sprintf('%04d-%02d', $row->year, $row->month);
            $totals[$key] = (int) $row->total_count;
            $verified[$key] = (int) $row->verified_count;
            $tindak[$key] = (int) $row->tindak_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Aspirasi',
                    'data' => array_values($totals),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                    'fill' => false,
                ],
                [
                    'label' => 'Terverifikasi',
                    'data' => array_values($verified),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.15)',
                    'fill' => false,
                ],
                [
                    'label' => 'Tindak Lanjut',
                    'data' => array_values($tindak),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => false,
                ],
            ],
            'labels' => array_map(
                static fn (string $key): string => Carbon::createFromFormat('Y-m', $key)->format('M Y'),
                array_keys($monthMap)
            ),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getScopedQuery($user): Builder
    {
        $query = Aspirasi::query();

        if (! $this->viewAdminWidgets()) {
            $query->where('user_id', $user->getAuthIdentifier());
        }

        return $query;
    }

    protected function viewAdminWidgets(): bool
    {
        $user = Filament::auth()->user();

        return $user?->can('viewAdminWidgets', Aspirasi::class) ?? false;
    }

    protected function emptyData(): array
    {
        return [
            'datasets' => [],
            'labels' => [],
        ];
    }

    protected function buildMonthMap(Carbon $start, Carbon $end): array
    {
        $map = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $map[$cursor->format('Y-m')] = 0;
            $cursor->addMonthNoOverflow();
        }

        return $map;
    }

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('viewAdminWidgets', Aspirasi::class)
            || $user->can('view', new Aspirasi());
    }
}
