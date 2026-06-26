<?php

use App\Livewire\Material\Index as MaterialIndex;
use App\Livewire\Material\MaterialRequest as MaterialRequestComponent;
use App\Models\Inventory;
use App\Models\MaterialRequest;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('forbids a user without permission from creating a material request via saveRequest', function () {
    // Bare user: no role, so lacks "create material requests".
    actingAs(User::factory()->create());

    Livewire::test(MaterialRequestComponent::class)
        ->call('saveRequest')
        ->assertForbidden();

    expect(MaterialRequest::count())->toBe(0);
});

it('forbids a user without permission from creating inventory via saveInventory', function () {
    actingAs(User::factory()->create());

    Livewire::test(MaterialIndex::class)
        ->call('saveInventory')
        ->assertForbidden();

    expect(Inventory::count())->toBe(0);
});

it('still allows a privileged user past the create guards', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    expect($admin->can('create', MaterialRequest::class))->toBeTrue()
        ->and($admin->can('create', Inventory::class))->toBeTrue();
});
