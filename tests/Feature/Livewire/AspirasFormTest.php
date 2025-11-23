<?php

namespace Tests\Feature\Livewire;

use App\Livewire\AspirasForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class AspirasFormTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(AspirasForm::class)
            ->assertStatus(200);
    }
}
