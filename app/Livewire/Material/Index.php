<?php

namespace App\Livewire\Material;

use App\Models\Material;
use App\Models\Inventory;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $showMaterialModal = false;
    public $showInventoryModal = false;
    public $editingMaterial = null;

    // Material fields
    public $material_name = '';
    public $material_code = '';
    public $material_description = '';
    public $material_unit = 'pieces';
    public $material_unit_cost = '';
    public $material_currency = 'USD';
    public $material_category = 'cement';
    public $material_reorder_level = 100;
    public $material_specifications = '';

    // Inventory fields
    public $inventory_material_id = '';
    public $inventory_project_id = '';
    public $inventory_quantity = '';
    public $inventory_location = '';
    public $inventory_notes = '';

    // Filters
    public $search = '';
    public $category_filter = '';

    protected $queryString = ['search', 'category_filter'];

    public function openMaterialModal()
    {
        $this->authorize('create', Material::class);
        $this->resetMaterialForm();
        $this->showMaterialModal = true;
    }

    public function openEditMaterialModal($materialId)
    {
        $material = Material::findOrFail($materialId);
        $this->authorize('update', $material);

        $this->editingMaterial = $material->id;
        $this->material_name = $material->name;
        $this->material_code = $material->code;
        $this->material_description = $material->description;
        $this->material_unit = $material->unit;
        $this->material_unit_cost = $material->unit_cost;
        $this->material_currency = $material->currency;
        $this->material_category = $material->category;
        $this->material_reorder_level = $material->reorder_level;
        $this->material_specifications = $material->specifications;
        $this->showMaterialModal = true;
    }

    public function saveMaterial()
    {
        $this->validate([
            'material_name' => 'required|string|max:255',
            'material_code' => 'required|string|max:50|unique:materials,code,' . ($this->editingMaterial ?? 'NULL'),
            'material_unit' => 'required|string',
            'material_unit_cost' => 'required|numeric|min:0',
            'material_currency' => 'required|in:USD,NGN',
            'material_category' => 'required|string',
            'material_reorder_level' => 'required|integer|min:0',
        ]);

        if ($this->editingMaterial) {
            $material = Material::findOrFail($this->editingMaterial);
            $this->authorize('update', $material);
            $material->update([
                'name' => $this->material_name,
                'code' => $this->material_code,
                'description' => $this->material_description,
                'unit' => $this->material_unit,
                'unit_cost' => $this->material_unit_cost,
                'currency' => $this->material_currency,
                'category' => $this->material_category,
                'reorder_level' => $this->material_reorder_level,
                'specifications' => $this->material_specifications,
            ]);
        } else {
            $this->authorize('create', Material::class);
            Material::create([
                'name' => $this->material_name,
                'code' => $this->material_code,
                'description' => $this->material_description,
                'unit' => $this->material_unit,
                'unit_cost' => $this->material_unit_cost,
                'currency' => $this->material_currency,
                'category' => $this->material_category,
                'reorder_level' => $this->material_reorder_level,
                'specifications' => $this->material_specifications,
            ]);
        }

        $this->showMaterialModal = false;
        $this->resetMaterialForm();
    }

    public function openInventoryModal($materialId)
    {
        $this->authorize('create', Inventory::class);
        $this->inventory_material_id = $materialId;
        $this->showInventoryModal = true;
    }

    public function saveInventory()
    {
        $this->validate([
            'inventory_material_id' => 'required|exists:materials,id',
            'inventory_project_id' => 'required|exists:projects,id',
            'inventory_quantity' => 'required|numeric|min:0',
            'inventory_location' => 'required|string|max:255',
        ]);

        Inventory::create([
            'material_id' => $this->inventory_material_id,
            'project_id' => $this->inventory_project_id,
            'quantity' => $this->inventory_quantity,
            'allocated_quantity' => 0,
            'used_quantity' => 0,
            'location' => $this->inventory_location,
            'status' => 'available',
            'notes' => $this->inventory_notes,
        ]);

        $this->showInventoryModal = false;
        $this->resetInventoryForm();
    }

    public function deleteMaterial($materialId)
    {
        $material = Material::findOrFail($materialId);
        $this->authorize('delete', $material);
        $material->delete();
    }

    private function resetMaterialForm()
    {
        $this->editingMaterial = null;
        $this->material_name = '';
        $this->material_code = '';
        $this->material_description = '';
        $this->material_unit = 'pieces';
        $this->material_unit_cost = '';
        $this->material_currency = 'USD';
        $this->material_category = 'cement';
        $this->material_reorder_level = 100;
        $this->material_specifications = '';
    }

    private function resetInventoryForm()
    {
        $this->inventory_material_id = '';
        $this->inventory_project_id = '';
        $this->inventory_quantity = '';
        $this->inventory_location = '';
        $this->inventory_notes = '';
    }

    public function render()
    {
        $query = Material::query()->with(['inventories']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('category', 'like', "%{$this->search}%");
            });
        }

        if ($this->category_filter) {
            $query->where('category', $this->category_filter);
        }

        $materials = $query->paginate(20);
        $user = auth()->user();
        $projects = $user ? $user->getAccessibleProjects() : collect();
        $categories = Material::distinct()->pluck('category');

        return view('livewire.material.index', [
            'materials' => $materials,
            'projects' => $projects,
            'categories' => $categories,
        ]);
    }
}
