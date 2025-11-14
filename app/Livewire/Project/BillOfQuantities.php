<?php

namespace App\Livewire\Project;

use App\Models\BillOfQuantity;
use App\Models\Project;
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
        'requestable_quantity' => 'required|numeric|min:0',
        'unit_rate' => 'required|numeric|min:0',
        'category' => 'nullable|string|max:100',
        'notes' => 'nullable|string|max:1000',
        'order' => 'required|integer|min:0',
    ];

    public function mount($id)
    {
        $this->project = Project::findOrFail($id);
    }

    public function render()
    {
        $billOfQuantities = $this->project->billOfQuantities()->orderBy('order')->get();
        $categories = BillOfQuantity::where('project_id', $this->project->id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');
        
        return view('livewire.project.bill-of-quantities', [
            'billOfQuantities' => $billOfQuantities,
            'categories' => $categories,
        ]);
    }

    public function openModal()
    {
        $this->resetForm();
        $this->order = $this->project->billOfQuantities()->count() + 1;
        $this->requestable_quantity = 0; // Default to 0, user will set it
        $this->showModal = true;
    }

    public function openEditModal($itemId)
    {
        $item = BillOfQuantity::findOrFail($itemId);
        
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

        if ($this->editingItem) {
            $item = BillOfQuantity::findOrFail($this->editingItem);
            $item->update($data);
        } else {
            BillOfQuantity::create($data);
        }

        $this->updateProjectTotal();
        $this->showModal = false;
        $this->resetForm();
        
        session()->flash('message', 'Bill of Quantities item saved successfully');
    }

    public function delete($itemId)
    {
        $item = BillOfQuantity::findOrFail($itemId);
        $item->delete();
        $this->updateProjectTotal();
    }

    public function moveUp($itemId)
    {
        $item = BillOfQuantity::findOrFail($itemId);
        $previousItem = $this->project->billOfQuantities()
            ->where('order', '<', $item->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousItem) {
            $tempOrder = $item->order;
            $item->order = $previousItem->order;
            $previousItem->order = $tempOrder;
            $item->save();
            $previousItem->save();
        }
    }

    public function moveDown($itemId)
    {
        $item = BillOfQuantity::findOrFail($itemId);
        $nextItem = $this->project->billOfQuantities()
            ->where('order', '>', $item->order)
            ->orderBy('order')
            ->first();

        if ($nextItem) {
            $tempOrder = $item->order;
            $item->order = $nextItem->order;
            $nextItem->order = $tempOrder;
            $item->save();
            $nextItem->save();
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
