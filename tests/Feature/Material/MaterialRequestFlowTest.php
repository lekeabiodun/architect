<?php

use App\Livewire\Material\MaterialRequest as MaterialRequestComponent;
use App\Models\BillOfQuantity;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('runs a material request through create, approve and disburse, consuming the BOQ', function () {
    $project = Project::factory()->create();
    $boq = BillOfQuantity::factory()->create([
        'project_id' => $project->id,
        'requestable_quantity' => 100,
        'consumed_quantity' => 0,
    ]);

    // super_admin can drive every step of the workflow.
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    actingAs($admin);

    $component = Livewire::test(MaterialRequestComponent::class)
        ->set('request_project_id', $project->id)
        ->set('request_bill_of_quantity_id', $boq->id)
        ->set('request_quantity', 30)
        ->set('request_required_date', now()->addWeek()->toDateString())
        ->set('request_purpose', 'Pour foundation')
        ->call('saveRequest')
        ->assertHasNoErrors();

    $request = MaterialRequest::sole();
    expect($request->status)->toBe('pending')
        ->and((float) $request->requested_quantity)->toBe(30.0);

    // Approve for the full requested quantity.
    $component
        ->set('approved_quantity', 30)
        ->set('approval_notes', 'Approved')
        ->call('approveRequest', $request->id)
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe('approved');
    // BOQ should now reflect the consumed quantity.
    expect((float) $boq->fresh()->consumed_quantity)->toBe(30.0)
        ->and((float) $boq->fresh()->remaining_quantity)->toBe(70.0);

    // Disburse the approved quantity.
    $component
        ->set('disbursed_quantity', 30)
        ->set('disbursement_notes', 'Handed over')
        ->call('disburseRequest', $request->id)
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe('disbursed')
        ->and((float) $request->fresh()->disbursed_quantity)->toBe(30.0);
});

it('rejects a material request that exceeds the available BOQ quantity', function () {
    $project = Project::factory()->create();
    $boq = BillOfQuantity::factory()->create([
        'project_id' => $project->id,
        'requestable_quantity' => 10,
        'consumed_quantity' => 0,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    actingAs($admin);

    Livewire::test(MaterialRequestComponent::class)
        ->set('request_project_id', $project->id)
        ->set('request_bill_of_quantity_id', $boq->id)
        ->set('request_quantity', 25) // more than the 10 available
        ->set('request_required_date', now()->addWeek()->toDateString())
        ->set('request_purpose', 'Too much')
        ->call('saveRequest')
        ->assertHasErrors('request_quantity');

    expect(MaterialRequest::count())->toBe(0);
});
