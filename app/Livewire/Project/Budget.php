<?php

namespace App\Livewire\Project;

use App\Models\Project;
use Livewire\Component;

class Budget extends Component
{
    public $project;

    public function mount($id)
    {
        $this->project = Project::with(['phases.tasks', 'manager'])->findOrFail($id);
        $this->authorize('viewBudget', $this->project);
    }

    public function updateActualCost()
    {
        $this->project->updateActualCostFromTasks();
        $this->project->refresh();
    }

    public function render()
    {
        $costs = $this->project->calculateTotalTaskCosts();
        
        // Get phase-wise breakdown
        $phaseBreakdown = [];
        foreach ($this->project->phases as $phase) {
            $phaseEstimated = 0;
            $phaseActual = 0;
            
            foreach ($phase->tasks as $task) {
                $phaseEstimated += $task->estimated_cost ?? 0;
                $phaseActual += $task->actual_cost ?? 0;
            }
            
            $phaseBreakdown[] = [
                'phase' => $phase,
                'estimated' => $phaseEstimated,
                'actual' => $phaseActual,
                'variance' => $phaseEstimated - $phaseActual,
                'utilization' => $phaseEstimated > 0 ? ($phaseActual / $phaseEstimated) * 100 : 0,
            ];
        }
        
        return view('livewire.project.budget', [
            'costs' => $costs,
            'phaseBreakdown' => $phaseBreakdown,
        ]);
    }
}
