<?php

namespace Tests\Feature\Livewire;

use App\Livewire\AspirasiTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class AspirasiTableTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(AspirasiTable::class)
            ->assertStatus(200);
    }
}
