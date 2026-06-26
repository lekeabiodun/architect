<?php

namespace App\Livewire\Project;

use App\Models\BillOfQuantity;
use App\Models\Project;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BillOfQuantities extends Component
{
    public $project;

    public $showModal = false;

    public $editingItem = null;

    public $item_code = '';

    public $description = '';

    public $unit = '';

    public $quantity = '';

    public $requestable_quantity = '';

    public $unit_rate = '';

    public $category = '';

    public $notes = '';

    public $order = 0;

    protected $rules = [
        'item_code' => 'nullable|string|max:50',
        'description' => 'required|string|max:255',
        'unit' => 'required|string|max:50',
        'quantity' => 'required|numeric|min:0',
        'requestable_quantity' => 'required|numeric|min:0|lte:quantity',
        'unit_rate' => 'required|numeric|min:0',
        'category' => 'nullable|string|max:100',
        'notes' => 'nullable|string|max:1000',
        'order' => 'required|integer|min:0',
    ];

    public function mount($id)
    {
        $this->project = Project::findOrFail($id);

        $this->authorize('viewForProject', [BillOfQuantity::class, $this->project]);
    }

    public function render()
    {
        $billOfQuantities = $this->project->billOfQuantities()->orderBy('order')->get();
        $categories = $billOfQuantities->pluck('category')->filter()->unique()->values();

        return view('livewire.project.bill-of-quantities', [
            'billOfQuantities' => $billOfQuantities,
            'categories' => $categories,
            'canCreate' => auth()->user()->can('create', [BillOfQuantity::class, $this->project]),
        ]);
    }

    public function openModal()
    {
        $this->authorize('create', [BillOfQuantity::class, $this->project]);

        $this->resetForm();
        $this->order = $this->project->billOfQuantities()->count() + 1;
        $this->requestable_quantity = 0; // Default to 0, user will set it
        $this->showModal = true;
    }

    public function openEditModal($itemId)
    {
        $item = $this->project->billOfQuantities()->findOrFail($itemId);

        $this->authorize('update', $item);

        $this->editingItem = $item->id;
        $this->item_code = $item->item_code;
        $this->description = $item->description;
        $this->unit = $item->unit;
        $this->quantity = $item->quantity;
        $this->requestable_quantity = $item->requestable_quantity;
        $this->unit_rate = $item->unit_rate;
        $this->category = $item->category;
        $this->notes = $item->notes;
        $this->order = $item->order;

        $this->showModal = true;
    }

    public function updatedRequestableQuantity()
    {
        // Clear any validation errors for this field
        $this->resetErrorBag('requestable_quantity');
    }

    public function save()
    {
        // Authorize against the specific item when editing, or the project when creating.
        if ($this->editingItem) {
            $item = $this->project->billOfQuantities()->findOrFail($this->editingItem);
            $this->authorize('update', $item);
        } else {
            $this->authorize('create', [BillOfQuantity::class, $this->project]);
        }

        $this->validate();

        $data = [
            'project_id' => $this->project->id,
            'item_code' => $this->item_code,
            'description' => $this->description,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'requestable_quantity' => $this->requestable_quantity,
            'unit_rate' => $this->unit_rate,
            'category' => $this->category,
            'notes' => $this->notes,
            'order' => $this->order,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editingItem) {
                $this->project->billOfQuantities()->findOrFail($this->editingItem)->update($data);
            } else {
                BillOfQuantity::create($data);
            }

            $this->updateProjectTotal();
        });

        $this->showModal = false;
        $this->resetForm();

        Flux::toast('Bill of Quantities item saved successfully', variant: 'success');
    }

    public function delete($itemId)
    {
        $item = $this->project->billOfQuantities()->findOrFail($itemId);

        $this->authorize('delete', $item);

        DB::transaction(function () use ($item) {
            $item->delete();
            $this->updateProjectTotal();
        });

        Flux::toast('Bill of Quantities item deleted successfully', variant: 'success');
    }

    public function moveUp($itemId)
    {
        $item = $this->project->billOfQuantities()->findOrFail($itemId);

        $this->authorize('update', $item);

        $previousItem = $this->project->billOfQuantities()
            ->where('order', '<', $item->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousItem) {
            DB::transaction(function () use ($item, $previousItem) {
                [$item->order, $previousItem->order] = [$previousItem->order, $item->order];
                $item->save();
                $previousItem->save();
            });
        }
    }

    public function moveDown($itemId)
    {
        $item = $this->project->billOfQuantities()->findOrFail($itemId);

        $this->authorize('update', $item);

        $nextItem = $this->project->billOfQuantities()
            ->where('order', '>', $item->order)
            ->orderBy('order')
            ->first();

        if ($nextItem) {
            DB::transaction(function () use ($item, $nextItem) {
                [$item->order, $nextItem->order] = [$nextItem->order, $item->order];
                $item->save();
                $nextItem->save();
            });
        }
    }

    private function updateProjectTotal()
    {
        $total = $this->project->billOfQuantities()->sum('total_amount');
        $this->project->bill_of_quantities_total = $total;
        $this->project->save();
    }

    private function resetForm()
    {
        $this->editingItem = null;
        $this->item_code = '';
        $this->description = '';
        $this->unit = '';
        $this->quantity = '';
        $this->requestable_quantity = '';
        $this->unit_rate = '';
        $this->category = '';
        $this->notes = '';
        $this->order = 0;
        $this->resetErrorBag();
    }
}
