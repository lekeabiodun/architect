<?php

use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;

it('sums estimated and actual task costs across phases into the project budget', function () {
    $project = Project::factory()->create();
    $phaseA = Phase::factory()->create(['project_id' => $project->id]);
    $phaseB = Phase::factory()->create(['project_id' => $project->id]);

    Task::factory()->create(['phase_id' => $phaseA->id, 'estimated_cost' => 1000, 'actual_cost' => 800]);
    Task::factory()->create(['phase_id' => $phaseA->id, 'estimated_cost' => 500, 'actual_cost' => 600]);
    Task::factory()->create(['phase_id' => $phaseB->id, 'estimated_cost' => 2000, 'actual_cost' => 1500]);

    $costs = $project->calculateTotalTaskCosts();

    expect($costs['estimated'])->toBe(3500.0)
        ->and($costs['actual'])->toBe(2900.0)
        ->and($costs['variance'])->toBe(600.0);

    $project->updateActualCostFromTasks();
    expect((float) $project->fresh()->actual_cost)->toBe(2900.0);
});

it('computes costs from fresh data even when the phases relation is cached and stale', function () {
    $project = Project::factory()->create();
    $phase = Phase::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create(['phase_id' => $phase->id, 'actual_cost' => 0]);

    // Mimic the Budget screen: load the project with phases/tasks eager-loaded.
    $loaded = Project::with('phases.tasks')->find($project->id);

    // A task cost changes after that eager load (stale cached relation).
    $task->update(['actual_cost' => 750]);

    $loaded->updateActualCostFromTasks();

    // Must reflect the new cost, not the stale cached 0.
    expect((float) $loaded->fresh()->actual_cost)->toBe(750.0);
});

it('returns zero totals for a project with no tasks', function () {
    $project = Project::factory()->create();

    $costs = $project->calculateTotalTaskCosts();

    expect($costs['estimated'])->toBe(0.0)
        ->and($costs['actual'])->toBe(0.0)
        ->and($costs['variance'])->toBe(0.0);
});
