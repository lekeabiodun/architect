<?php

use App\Livewire\Project\BillOfQuantities;
use App\Models\BillOfQuantity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function makeManager(string $role = 'project_manager'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function projectManagedBy(User $user): Project
{
    return Project::create([
        'name' => 'Test Project',
        'status' => 'active',
        'currency' => 'USD',
        'manager_id' => $user->id,
    ]);
}

it('blocks a manager from deleting a BOQ item belonging to another project (IDOR)', function () {
    $managerA = makeManager();
    $projectA = projectManagedBy($managerA);

    $managerB = makeManager();
    $projectB = projectManagedBy($managerB);

    // Item lives under project B; manager A only controls project A.
    $foreignItem = BillOfQuantity::create([
        'project_id' => $projectB->id,
        'description' => 'Cement',
        'unit' => 'bag',
        'quantity' => 10,
        'requestable_quantity' => 10,
        'unit_rate' => 5,
        'order' => 1,
    ]);

    actingAs($managerA);

    // The scoped lookup never finds the foreign item, so it cannot be deleted.
    expect(fn () => Livewire::test(BillOfQuantities::class, ['id' => $projectA->id])
        ->call('delete', $foreignItem->id))
        ->toThrow(ModelNotFoundException::class);

    expect(BillOfQuantity::find($foreignItem->id))->not->toBeNull();
});

it('allows the project manager to delete a BOQ item of their own project', function () {
    $manager = makeManager();
    $project = projectManagedBy($manager);

    $item = BillOfQuantity::create([
        'project_id' => $project->id,
        'description' => 'Sand',
        'unit' => 'ton',
        'quantity' => 4,
        'requestable_quantity' => 4,
        'unit_rate' => 20,
        'order' => 1,
    ]);

    actingAs($manager);

    Livewire::test(BillOfQuantities::class, ['id' => $project->id])
        ->call('delete', $item->id)
        ->assertHasNoErrors();

    expect(BillOfQuantity::find($item->id))->toBeNull();
});

it('forbids a worker from viewing the BOQ screen', function () {
    $manager = makeManager();
    $project = projectManagedBy($manager);

    $worker = User::factory()->create();
    $worker->assignRole('worker');
    $project->users()->attach($worker->id);

    actingAs($worker);

    Livewire::test(BillOfQuantities::class, ['id' => $project->id])
        ->assertForbidden();
});
