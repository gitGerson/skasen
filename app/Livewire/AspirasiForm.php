<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;       
use App\Models\Aspirasi;
use App\Models\User;
use App\Models\Tujuan;
use App\Models\Kategori;

class AspirasiForm extends Component
{
    #[Validate('required')] public $user_id = '';
    #[Validate('required')] public $tujuan_id = '';
    #[Validate('required')] public $kategori_id = '';
    #[Validate('required')] public $keterangan = '';
    #[Validate('required')] public $image_path = '';
    #[Validate('required')] public $is_anonymous = '';
    #[Validate('required')] public $status = ''; // add rule

    public $users = [];
    public $tujuans = [];
    public $kategoris = [];


    public function submit()
    {
        $this->validate();

        Aspirasi::create([
            'user_id' => $this->user_id,
            'tujuan_id' => $this->tujuan_id,
            'kategori_id' => $this->kategori_id,
            'keterangan' => $this->keterangan,
            'image_path' => $this->image_path,
            'is_anonymous' => $this->is_anonymous,
            'status' => $this->status,
        ]);

        $this->reset('user_id', 'tujuan_id', 'kategori_id', 'keterangan', 'image_path', 'is_anonymous', 'status');

        $this->dispatch('aspirasi-created');
    }

    public function render()
    {
        $this->users = User::all();
        $this->tujuans = Tujuan::all();
        $this->kategoris = Kategori::all();
        return view('livewire.aspirasi-form') ;
    }
}


