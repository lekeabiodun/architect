<?php

use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;

it('rolls weighted task completion up into phase and project progress', function () {
    $project = Project::factory()->create();
    $phaseA = Phase::factory()->create(['project_id' => $project->id, 'weight' => 70]);
    $phaseB = Phase::factory()->create(['project_id' => $project->id, 'weight' => 30]);

    $a1 = Task::factory()->create(['phase_id' => $phaseA->id, 'weight' => 50, 'status' => 'pending']);
    Task::factory()->create(['phase_id' => $phaseA->id, 'weight' => 50, 'status' => 'pending']);
    $b1 = Task::factory()->create(['phase_id' => $phaseB->id, 'weight' => 100, 'status' => 'pending']);

    // Complete one of the two equally-weighted tasks in phase A → phase A is 50%.
    $a1->update(['status' => 'completed']);
    Phase::find($phaseA->id)->updateProgress();

    expect((float) $phaseA->fresh()->progress)->toBe(50.0)
        // Project = 50% * 0.70 (phase A) + 0% * 0.30 (phase B) = 35%.
        ->and((float) $project->fresh()->overall_progress)->toBe(35.0);

    // Complete the second task in phase A → phase A is 100%.
    Task::where('phase_id', $phaseA->id)->update(['status' => 'completed']);
    Phase::find($phaseA->id)->updateProgress();

    expect((float) $project->fresh()->overall_progress)->toBe(70.0);

    // Complete phase B → project is fully complete.
    $b1->update(['status' => 'completed']);
    Phase::find($phaseB->id)->updateProgress();

    expect((float) $project->fresh()->overall_progress)->toBe(100.0);
});

it('cascades through toggleStatus and updates phase status', function () {
    $project = Project::factory()->create();
    $phase = Phase::factory()->create(['project_id' => $project->id, 'weight' => 100]);

    $t1 = Task::factory()->create(['phase_id' => $phase->id, 'weight' => 50, 'status' => 'pending']);
    $t2 = Task::factory()->create(['phase_id' => $phase->id, 'weight' => 50, 'status' => 'pending']);

    // toggleStatus cycles pending → in_progress → completed.
    $t1->toggleStatus();
    $t1->toggleStatus();

    expect($t1->fresh()->status)->toBe('completed')
        ->and((float) $phase->fresh()->progress)->toBe(50.0)
        ->and($phase->fresh()->status)->toBe('in_progress')
        ->and((float) $project->fresh()->overall_progress)->toBe(50.0);

    $t2->toggleStatus();
    $t2->toggleStatus();

    expect((float) $phase->fresh()->progress)->toBe(100.0)
        ->and($phase->fresh()->status)->toBe('completed')
        ->and((float) $project->fresh()->overall_progress)->toBe(100.0);
});

it('counts tasks equally when no weights are set', function () {
    $project = Project::factory()->create();
    $phase = Phase::factory()->create(['project_id' => $project->id, 'weight' => 100]);

    // Four unweighted tasks → each completion is 25%.
    $tasks = Task::factory()->count(4)->create(['phase_id' => $phase->id, 'weight' => 0, 'status' => 'pending']);

    $tasks[0]->update(['status' => 'completed']);
    Phase::find($phase->id)->updateProgress();

    expect((float) $phase->fresh()->progress)->toBe(25.0);
});
