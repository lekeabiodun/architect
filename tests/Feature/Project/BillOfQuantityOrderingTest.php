<?php

use App\Livewire\Project\BillOfQuantities;
use App\Models\BillOfQuantity;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function boqManagerFor(Project $project): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin'); // bypasses fine-grained checks for the flow

    return $user;
}

function addItem($component, string $description): void
{
    $component
        ->set('item_code', strtoupper(substr($description, 0, 3)))
        ->set('description', $description)
        ->set('unit', 'bag')
        ->set('quantity', 100)
        ->set('requestable_quantity', 50)
        ->set('unit_rate', 10)
        ->call('save')
        ->assertHasNoErrors();
}

it('auto-assigns a unique, increasing order to new BOQ items', function () {
    $project = Project::factory()->create();
    actingAs(boqManagerFor($project));

    $component = Livewire::test(BillOfQuantities::class, ['id' => $project->id]);
    addItem($component, 'First');
    addItem($component, 'Second');
    addItem($component, 'Third');

    $orders = BillOfQuantity::where('project_id', $project->id)
        ->orderBy('order')
        ->pluck('order')
        ->all();

    expect($orders)->toBe([1, 2, 3])
        ->and(array_unique($orders))->toHaveCount(3);
});

it('keeps order gap-tolerant and unique after deleting a middle item', function () {
    $project = Project::factory()->create();
    actingAs(boqManagerFor($project));

    $component = Livewire::test(BillOfQuantities::class, ['id' => $project->id]);
    addItem($component, 'Alpha');
    addItem($component, 'Bravo');
    addItem($component, 'Charlie');

    // Delete the middle item (order 2), leaving a gap [1, 3].
    $middle = BillOfQuantity::where('project_id', $project->id)->where('order', 2)->first();
    $component->call('delete', $middle->id)->assertHasNoErrors();

    // A new item must take max+1 = 4, not collide with the existing orders.
    addItem($component, 'Delta');

    $orders = BillOfQuantity::where('project_id', $project->id)->orderBy('order')->pluck('order')->all();
    expect($orders)->toBe([1, 3, 4])
        ->and(array_unique($orders))->toHaveCount(3);
});

it('preserves an item order when it is edited', function () {
    $project = Project::factory()->create();
    actingAs(boqManagerFor($project));

    $component = Livewire::test(BillOfQuantities::class, ['id' => $project->id]);
    addItem($component, 'First');
    addItem($component, 'Second');

    $second = BillOfQuantity::where('project_id', $project->id)->where('order', 2)->first();

    $component
        ->call('openEditModal', $second->id)
        ->set('description', 'Second (edited)')
        ->call('save')
        ->assertHasNoErrors();

    $second->refresh();
    expect($second->description)->toBe('Second (edited)')
        ->and((int) $second->order)->toBe(2);
});

it('swaps order values when moving an item up', function () {
    $project = Project::factory()->create();
    actingAs(boqManagerFor($project));

    $component = Livewire::test(BillOfQuantities::class, ['id' => $project->id]);
    addItem($component, 'First');
    addItem($component, 'Second');

    $second = BillOfQuantity::where('project_id', $project->id)->where('order', 2)->first();
    $component->call('moveUp', $second->id)->assertHasNoErrors();

    expect((int) $second->fresh()->order)->toBe(1);

    $orders = BillOfQuantity::where('project_id', $project->id)->pluck('order')->sort()->values()->all();
    expect($orders)->toBe([1, 2]); // still unique
});
