<?php

namespace App\Filament\Widgets;

use App\Models\Aspirasi;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Database\Eloquent\Builder;

class AspirasiChart extends ChartWidget
{
    use HasFiltersSchema;

    private const RANGE_DAILY = 'daily';
    private const RANGE_WEEKLY = 'weekly';
    private const RANGE_MONTHLY = 'monthly';
    private const RANGE_YEARLY = 'yearly';

    private const STATUS_BELUM = 'Belum Ditindaklanjuti';

    protected ?string $heading = 'Grafik Perkembangan Aspirasi';

    protected static ?int $sort = 4;

    /**
     * Custom filters di pojok widget (Filament v4)
     * - range: daily/weekly/monthly/yearly
     * - date/week/month/year: parameter sesuai range
     *
     * Nilai bisa dibaca di $this->filters[...] :contentReference[oaicite:1]{index=1}
     */
    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('range')
                ->label('Rentang')
                ->options([
                    self::RANGE_DAILY => 'Harian',
                    self::RANGE_WEEKLY => 'Mingguan',
                    self::RANGE_MONTHLY => 'Bulanan',
                    self::RANGE_YEARLY => 'Tahunan',
                ])
                ->default(self::RANGE_MONTHLY)
                ->live(), // penting supaya visible() field lain ikut berubah

            DatePicker::make('date')
                ->label('Tanggal')
                ->default(now())
                ->visible(fn(Get $get): bool => ($get('range') ?? self::RANGE_MONTHLY) === self::RANGE_DAILY),

            Select::make('week')
                ->label('Minggu')
                ->options($this->weekOptions(20))
                ->default(now()->format('o-\WW')) // contoh: 2026-W02
                ->searchable()
                ->visible(fn(Get $get): bool => ($get('range') ?? self::RANGE_MONTHLY) === self::RANGE_WEEKLY),

            Select::make('month')
                ->label('Bulan')
                ->options($this->monthOptions(18))
                ->default(now()->format('Y-m')) // 2026-01
                ->searchable()
                ->visible(fn(Get $get): bool => ($get('range') ?? self::RANGE_MONTHLY) === self::RANGE_MONTHLY),

            Select::make('year')
                ->label('Tahun')
                ->options($this->yearOptions(6))
                ->default((string) now()->year)
                ->searchable()
                ->visible(fn(Get $get): bool => ($get('range') ?? self::RANGE_MONTHLY) === self::RANGE_YEARLY),
        ]);
    }

    protected function getData(): array
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return $this->emptyData();
        }

        $range = (string) ($this->filters['range'] ?? self::RANGE_MONTHLY);

        [$start, $end, $periodMap, $periodSelect] = $this->resolveRange($range);

        $rows = $this->getScopedQuery($user)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw($periodSelect . ' as period')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN is_verify = 1 THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN status != ? THEN 1 ELSE 0 END) as tindak_count', [self::STATUS_BELUM])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $totals = array_fill_keys(array_keys($periodMap), 0);
        $verified = array_fill_keys(array_keys($periodMap), 0);
        $tindak = array_fill_keys(array_keys($periodMap), 0);

        foreach ($rows as $row) {
            $key = (string) $row->period;

            if (!array_key_exists($key, $totals)) {
                continue;
            }

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
            'labels' => array_values($periodMap),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        // hilangkan skala minus saat semua data 0
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon,2:array<string,string>,3:string}
     */
    protected function resolveRange(string $range): array
    {
        $now = now();

        switch ($range) {
            case self::RANGE_DAILY: {
                $date = $this->filters['date'] ?? $now->toDateString();
                $base = Carbon::parse($date);

                $start = $base->copy()->startOfDay();
                $end = $base->copy()->endOfDay();

                // key: YYYY-mm-dd HH:00:00
                $periodSelect = "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')";

                $map = [];
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $key = $cursor->format('Y-m-d H:00:00');
                    $map[$key] = $cursor->format('H:00');
                    $cursor->addHour();
                }

                return [$start, $end, $map, $periodSelect];
            }

            case self::RANGE_WEEKLY: {
                // value contoh: 2026-W02
                $weekValue = (string) ($this->filters['week'] ?? $now->format('o-\WW'));

                if (preg_match('/^(\d{4})-W(\d{2})$/', $weekValue, $m)) {
                    $isoYear = (int) $m[1];
                    $isoWeek = (int) $m[2];
                    $base = Carbon::now()->setISODate($isoYear, $isoWeek)->startOfWeek(Carbon::MONDAY);
                } else {
                    $base = $now->copy()->startOfWeek(Carbon::MONDAY);
                }

                $start = $base->copy()->startOfDay();
                $end = $base->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

                // key: YYYY-mm-dd
                $periodSelect = "DATE(created_at)";

                $map = [];
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $key = $cursor->format('Y-m-d');
                    $map[$key] = $cursor->format('D d'); // Mon 08
                    $cursor->addDay();
                }

                return [$start, $end, $map, $periodSelect];
            }

            case self::RANGE_YEARLY: {
                $year = (int) ($this->filters['year'] ?? $now->year);

                $start = Carbon::create($year, 1, 1)->startOfDay();
                $end = Carbon::create($year, 12, 31)->endOfDay();

                // key: YYYY-mm
                $periodSelect = "DATE_FORMAT(created_at, '%Y-%m')";

                $map = [];
                $cursor = $start->copy()->startOfMonth();
                while ($cursor->lte($end)) {
                    $key = $cursor->format('Y-m');
                    $map[$key] = $cursor->format('M'); // Jan..Dec
                    $cursor->addMonthNoOverflow();
                }

                return [$start, $end, $map, $periodSelect];
            }

            case self::RANGE_MONTHLY:
            default: {
                $monthValue = (string) ($this->filters['month'] ?? $now->format('Y-m'));
                $base = Carbon::createFromFormat('Y-m', $monthValue)->startOfMonth();

                $start = $base->copy()->startOfDay();
                $end = $base->copy()->endOfMonth()->endOfDay();

                // key: YYYY-mm-dd
                $periodSelect = "DATE(created_at)";

                $map = [];
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $key = $cursor->format('Y-m-d');
                    $map[$key] = $cursor->format('d'); // 01..31
                    $cursor->addDay();
                }

                return [$start, $end, $map, $periodSelect];
            }
        }
    }

    protected function getScopedQuery($user): Builder
    {
        $query = Aspirasi::query();

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

    protected function emptyData(): array
    {
        return ['datasets' => [], 'labels' => []];
    }

    // ===== Options generator (untuk select minggu/bulan/tahun) =====

    protected function weekOptions(int $countBack = 20): array
    {
        $options = [];
        $cursor = now()->startOfWeek(Carbon::MONDAY);

        for ($i = 0; $i < $countBack; $i++) {
            $value = $cursor->format('o-\WW'); // 2026-W02
            $label = 'Minggu ke-' . $cursor->isoWeek . ' ' . $cursor->isoWeekYear;
            $options[$value] = $label;

            $cursor->subWeek();
        }

        return $options;
    }

    protected function monthOptions(int $countBack = 18): array
    {
        $options = [];
        $cursor = now()->startOfMonth();

        for ($i = 0; $i < $countBack; $i++) {
            $value = $cursor->format('Y-m'); // 2026-01
            $label = $cursor->format('M Y'); // Jan 2026
            $options[$value] = $label;

            $cursor->subMonthNoOverflow();
        }

        return $options;
    }

    protected function yearOptions(int $countBack = 6): array
    {
        $options = [];
        $year = now()->year;

        for ($i = 0; $i <= $countBack; $i++) {
            $y = (string) ($year - $i);
            $options[$y] = $y;
        }

        return $options;
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
