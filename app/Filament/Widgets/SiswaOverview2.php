<?php

namespace App\Filament\Widgets;

use App\Models\Aspirasi;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SiswaOverview2 extends StatsOverviewWidget
{
    private const STATUS_BELUM = 'Belum Ditindaklanjuti';
    private const STATUS_SEDANG = 'Sedang Ditindaklanjuti';
    private const STATUS_SELESAI = 'Selesai';

    protected ?string $heading = 'Status Tindak Lanjut';

    protected static ?int $sort = 3;


    protected function getStats(): array
    {
        $query = $this->getScopedQuery();

        $belum = (clone $query)->where('status', self::STATUS_BELUM)->count();
        $sedang = (clone $query)->where('status', self::STATUS_SEDANG)->count();
        $selesai = (clone $query)->where('status', self::STATUS_SELESAI)->count();
        $total = $belum + $sedang + $selesai;

        return [
            Stat::make('Belum Ditindaklanjuti', $belum . ' Laporan')
                ->description($belum > 0 ? 'Masih menunggu tindak lanjut.' : ($total > 0 ? 'Tidak ada antrean awal.' : 'Belum ada laporan yang diproses.'))
                ->descriptionIcon($belum > 0 ? Heroicon::OutlinedQueueList : Heroicon::OutlinedInformationCircle)
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color($belum > 0 ? 'primary' : 'gray'),
            Stat::make('Sedang Ditindaklanjuti', $sedang . ' Laporan')
                ->description($sedang > 0 ? 'Sedang berada dalam proses penanganan.' : ($total > 0 ? 'Tidak ada laporan yang sedang diproses.' : 'Belum ada progres tindak lanjut.'))
                ->descriptionIcon($sedang > 0 ? Heroicon::OutlinedArrowPath : Heroicon::OutlinedInformationCircle)
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->color($sedang > 0 ? 'warning' : 'gray'),
            Stat::make('Sudah Ditindaklanjuti', $selesai . ' Laporan')
                ->description($selesai > 0 ? 'Penanganan laporan sudah selesai.' : ($total > 0 ? 'Belum ada laporan yang selesai ditindaklanjuti.' : 'Belum ada penyelesaian untuk ditampilkan.'))
                ->descriptionIcon($selesai > 0 ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedInformationCircle)
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color($selesai > 0 ? 'success' : 'gray'),
        ];
    }

    protected function getColumns(): int|array|null
    {
        return 3;
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
