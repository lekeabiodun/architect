<?php

namespace App\Livewire\Material;

use App\Models\Material;
use App\Models\MaterialRequest as MaterialRequestModel;
use App\Models\Project;
use App\Models\Phase;
use App\Models\Task;
use Livewire\Component;
use Livewire\WithPagination;

class MaterialRequest extends Component
{
    use WithPagination;

    public $showRequestModal = false;
    public $selectedRequest = null;
    
    // Request fields
    public $request_material_id = '';
    public $request_project_id = '';
    public $request_phase_id = '';
    public $request_task_id = '';
    public $request_quantity = '';
    public $request_required_date = '';
    public $request_purpose = '';
    public $request_justification = '';

    // Approval fields
    public $approved_quantity = '';
    public $approval_notes = '';
    public $disbursed_quantity = '';
    public $disbursement_notes = '';
    public $confirmation_notes = '';

    // Filters
    public $status_filter = '';
    public $project_filter = '';

    protected $queryString = ['status_filter', 'project_filter'];

    public function openRequestModal()
    {
        $this->authorize('create', MaterialRequestModel::class);
        $this->resetRequestForm();
        $this->showRequestModal = true;
    }

    public function saveRequest()
    {
        $this->validate([
            'request_material_id' => 'required|exists:materials,id',
            'request_project_id' => 'required|exists:projects,id',
            'request_quantity' => 'required|numeric|min:0',
            'request_required_date' => 'required|date',
            'request_purpose' => 'required|string',
        ]);

        MaterialRequestModel::create([
            'material_id' => $this->request_material_id,
            'project_id' => $this->request_project_id,
            'phase_id' => $this->request_phase_id ?: null,
            'task_id' => $this->request_task_id ?: null,
            'requested_quantity' => $this->request_quantity,
            'required_date' => $this->request_required_date,
            'purpose' => $this->request_purpose,
            'justification' => $this->request_justification,
            'requested_by' => auth()->id(),
            'status' => 'pending',
        ]);

        $this->showRequestModal = false;
        $this->resetRequestForm();
    }

    public function approveRequest($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('approve', $request);

        $this->validate([
            'approved_quantity' => 'required|numeric|min:0',
        ]);

        $request->approve(auth()->user(), $this->approved_quantity, $this->approval_notes);
        
        $this->selectedRequest = null;
        $this->approved_quantity = '';
        $this->approval_notes = '';
    }

    public function rejectRequest($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('reject', $request);

        $this->validate([
            'approval_notes' => 'required|string',
        ]);

        $request->reject(auth()->user(), $this->approval_notes);
        
        $this->selectedRequest = null;
        $this->approval_notes = '';
    }

    public function disburseRequest($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('disburse', $request);

        $this->validate([
            'disbursed_quantity' => 'required|numeric|min:0',
        ]);

        $request->disburse(auth()->user(), $this->disbursed_quantity, $this->disbursement_notes);
        
        $this->selectedRequest = null;
        $this->disbursed_quantity = '';
        $this->disbursement_notes = '';
    }

    public function confirmRequest($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('confirm', $request);

        $this->validate([
            'confirmation_notes' => 'required|string',
        ]);

        $request->confirm(auth()->user(), $this->confirmation_notes);
        
        $this->selectedRequest = null;
        $this->confirmation_notes = '';
    }

    public function selectRequest($requestId)
    {
        $this->selectedRequest = $requestId;
    }

    private function resetRequestForm()
    {
        $this->request_material_id = '';
        $this->request_project_id = '';
        $this->request_phase_id = '';
        $this->request_task_id = '';
        $this->request_quantity = '';
        $this->request_required_date = '';
        $this->request_purpose = '';
        $this->request_justification = '';
    }

    public function render()
    {
        $query = MaterialRequestModel::query()
            ->with(['material', 'project', 'phase', 'task', 'requester', 'approver', 'disburser', 'confirmer']);

        // Filter by status
        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        // Filter by project
        if ($this->project_filter) {
            $query->where('project_id', $this->project_filter);
        }

        // Filter by user role
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            // Show requests for projects user is assigned to
            $projectIds = $user->projects()->pluck('projects.id');
            $query->whereIn('project_id', $projectIds);
        }

        $requests = $query->latest()->paginate(15);
        $materials = Material::orderBy('name')->get();
        $projects = $user->getAccessibleProjects();

        return view('livewire.material.material-request', [
            'requests' => $requests,
            'materials' => $materials,
            'projects' => $projects,
        ]);
    }
}
