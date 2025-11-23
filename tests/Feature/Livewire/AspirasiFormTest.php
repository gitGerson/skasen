<?php

namespace Tests\Feature\Livewire;

use App\Livewire\AspirasiForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class AspirasiFormTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(AspirasiForm::class)
            ->assertStatus(200);
    }
}
