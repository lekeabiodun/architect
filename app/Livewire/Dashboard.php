<?php

namespace App\Livewire;

use App\Models\Task;
use App\Models\Project;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function mount()
    {
        $user = Auth::user();

        // Roles that cannot view the dashboard are redirected to their own
        // landing page instead of hitting a 403 on '/' (the login/home route).
        if (! $user->can('viewDashboard', $user)) {
            return redirect()->route($user->homeRoute());
        }
    }
    public function render()
    {

        $user = Auth::user();

        $projects = Project::query()
            ->with(['manager', 'client', 'inspector', 'phases'])
            ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('manager_id', $user->id)
                        ->orWhereHas('users', function ($uq) use ($user) {
                            $uq->where('user_id', $user->id);
                        });
                });
            })
            ->paginate(12);

        $tasks = Task::query()
            ->with(['phase.project', 'assignedUser', 'phase'])
            ->whereNotNull('phase_id')
            ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                $query->whereHas('phase.project', function ($pq) use ($user) {
                    $pq->where('manager_id', $user->id)
                        ->orWhereHas('users', function ($uq) use ($user) {
                            $uq->where('user_id', $user->id);
                        });
                });
            })
            ->paginate(12);

        return view('livewire.dashboard', [
            'projects' => $projects,
            'tasks' => $tasks,
        ]);
    }
}
