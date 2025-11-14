<?php

namespace App\Livewire\Material;

use App\Models\MaterialRequest as MaterialRequestModel;
use App\Models\Project;
use App\Models\BillOfQuantity;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

class MaterialRequest extends Component
{
    use WithPagination;

    public $showRequestModal = false;
    public $showApproveModal = false;
    public $showRejectModal = false;
    public $showDisburseModal = false;
    public $showConfirmModal = false;
    public $selectedRequest = null;

    // Request fields
    public $request_material_id = '';
    public $request_project_id = '';
    public $request_bill_of_quantity_id = '';
    public $request_phase_id = '';
    public $request_task_id = '';
    public $request_quantity = '';
    public $request_required_date = '';
    public $request_purpose = '';
    public $request_justification = '';

    // BOQ info for display
    public $availableBoqQuantity = 0;
    public $selectedBoqItem = null;
    public $boqItems = [];

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

    public function updatedRequestProjectId()
    {
        $this->request_bill_of_quantity_id = '';
        $this->availableBoqQuantity = 0;
        $this->selectedBoqItem = null;
        $this->boqItems = [];

        if ($this->request_project_id) {
            $this->boqItems = BillOfQuantity::where('project_id', $this->request_project_id)
                ->where('requestable_quantity', '>', 0)
                ->orderBy('order')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'item_code' => $item->item_code,
                        'unit' => $item->unit,
                        'quantity' => $item->quantity,
                        'requestable_quantity' => $item->requestable_quantity,
                        'remaining_quantity' => $item->remaining_quantity,
                        'unit_rate' => $item->unit_rate,
                        'category' => $item->category,
                    ];
                })
                ->toArray();
        }
    }

    public function updatedRequestBillOfQuantityId()
    {
        if ($this->request_bill_of_quantity_id) {
            $this->selectedBoqItem = BillOfQuantity::find($this->request_bill_of_quantity_id);
            $this->availableBoqQuantity = $this->selectedBoqItem->remaining_quantity;
        } else {
            $this->availableBoqQuantity = 0;
            $this->selectedBoqItem = null;
        }
    }

    public function updatedRequestQuantity()
    {
        // Clear any previous BOQ quantity errors when quantity changes
        if ($this->request_bill_of_quantity_id) {
            $this->resetErrorBag('request_quantity');
        }
    }

    public function openRequestModal()
    {
        $this->authorize('create', MaterialRequestModel::class);
        $this->resetRequestForm();
        $this->showRequestModal = true;
    }

    public function saveRequest()
    {
        $this->validate([
            'request_project_id' => 'required|exists:projects,id',
            'request_bill_of_quantity_id' => 'required|exists:bill_of_quantities,id',
            'request_quantity' => 'required|numeric|min:0',
            'request_required_date' => 'required|date',
            'request_purpose' => 'required|string',
        ]);

        // Get the selected BOQ item
        $boq = BillOfQuantity::find($this->request_bill_of_quantity_id);

        // Check BOQ limits
        if (!$boq->canRequestQuantity($this->request_quantity)) {
            $this->addError(
                'request_quantity',
                "Insufficient BOQ quantity. Available: {$boq->remaining_quantity} {$boq->unit}, Requested: {$this->request_quantity} {$boq->unit}"
            );
            return;
        }

        $request = MaterialRequestModel::create([
            'project_id' => $this->request_project_id,
            'phase_id' => $this->request_phase_id ?: null,
            'task_id' => $this->request_task_id ?: null,
            'bill_of_quantity_id' => $this->request_bill_of_quantity_id,
            'requested_quantity' => $this->request_quantity,
            'required_date' => $this->request_required_date,
            'purpose' => $this->request_purpose,
            'justification' => $this->request_justification,
            'requested_by' => auth()->id(),
            'status' => 'pending',
        ]);

        $this->resetRequestForm();
        $this->showRequestModal = false;

        Flux::toast('Material request submitted successfully', variant: 'success');
    }

    public function approveRequest($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('approve', $request);

        $this->validate([
            'approved_quantity' => 'required|numeric|min:0',
        ]);

        // Check BOQ limits if BOQ is selected
        if ($request->bill_of_quantity_id) {
            $boq = $request->billOfQuantity;
            if (!$boq->canRequestQuantity($this->approved_quantity)) {
                $this->addError(
                    'approved_quantity',
                    "Insufficient BOQ quantity. Available: {$boq->remaining_quantity} {$boq->unit}, Requested: {$this->approved_quantity} {$boq->unit}"
                );
                return;
            }
        }

        $request->approve(auth()->user(), $this->approved_quantity, $this->approval_notes);

        // Consume from BOQ if applicable
        if ($request->bill_of_quantity_id) {
            $request->billOfQuantity->consumeQuantity($this->approved_quantity);
        }

        $this->selectedRequest = null;
        $this->approved_quantity = '';
        $this->approval_notes = '';
        $this->showApproveModal = false;
        $this->resetApprovalForm();

        Flux::toast('Request approved successfully', variant: 'success');
    }

    public function rejectRequest($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('reject', $request);

        $this->validate([
            'approval_notes' => 'required|string',
        ]);

        // Return to BOQ if previously approved
        if ($request->bill_of_quantity_id && $request->approved_quantity) {
            $request->billOfQuantity->returnQuantity($request->approved_quantity);
        }

        $request->reject(auth()->user(), $this->approval_notes);

        $this->selectedRequest = null;
        $this->approval_notes = '';
        $this->showRejectModal = false;
        $this->resetApprovalForm();

        Flux::toast('Request rejected', variant: 'warning');
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
        $this->showDisburseModal = false;
        $this->resetDisbursementForm();

        Flux::toast('Materials disbursed successfully', variant: 'success');
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
        $this->showConfirmModal = false;
        $this->resetConfirmationForm();

        Flux::toast('Delivery confirmed successfully', variant: 'success');
    }

    public function openApproveModal($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('approve', $request);

        $this->selectedRequest = $requestId;
        $this->approved_quantity = $request->requested_quantity;
        $this->approval_notes = '';
        $this->showApproveModal = true;
    }

    public function openRejectModal($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('reject', $request);

        $this->selectedRequest = $requestId;
        $this->approval_notes = '';
        $this->showRejectModal = true;
    }

    public function openDisburseModal($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('disburse', $request);

        $this->selectedRequest = $requestId;
        $this->disbursed_quantity = $request->approved_quantity;
        $this->disbursement_notes = '';
        $this->showDisburseModal = true;
    }

    public function openConfirmModal($requestId)
    {
        $request = MaterialRequestModel::findOrFail($requestId);
        $this->authorize('confirm', $request);

        $this->selectedRequest = $requestId;
        $this->confirmation_notes = '';
        $this->showConfirmModal = true;
    }

    private function resetRequestForm()
    {
        $this->request_material_id = '';
        $this->request_project_id = '';
        $this->request_phase_id = '';
        $this->request_task_id = '';
        $this->request_bill_of_quantity_id = '';
        $this->request_quantity = '';
        $this->request_required_date = '';
        $this->request_purpose = '';
        $this->request_justification = '';
        $this->availableBoqQuantity = 0;
        $this->selectedBoqItem = null;
        $this->boqItems = [];
    }

    private function resetApprovalForm()
    {
        $this->selectedRequest = null;
        $this->approved_quantity = '';
        $this->approval_notes = '';
    }

    private function resetDisbursementForm()
    {
        $this->selectedRequest = null;
        $this->disbursed_quantity = '';
        $this->disbursement_notes = '';
    }

    private function resetConfirmationForm()
    {
        $this->selectedRequest = null;
        $this->confirmation_notes = '';
    }

    public function render()
    {
        $query = MaterialRequestModel::query()
            ->with(['project', 'phase', 'task', 'billOfQuantity', 'requester', 'approver', 'disburser', 'confirmer']);

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
        $projects = $user->getAccessibleProjects();

        return view('livewire.material.material-request', [
            'requests' => $requests,
            'projects' => $projects,
            'boqItems' => $this->boqItems,
        ]);
    }
}
