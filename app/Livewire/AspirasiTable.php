<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Aspirasi;

class AspirasiTable extends Component
{
    #[On('aspirasi-created')]
    public function refreshTable(): void
    {
        // Event hook to re-render when a new record is created.
    }

    public function render()
    {
        return view('livewire.aspirasi-table', [
            'aspirasis' => Aspirasi::with(['user', 'tujuan', 'kategori'])->latest()->get(),
        ]);
    }
}
