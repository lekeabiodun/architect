<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Materials & Inventory</flux:heading>
                @can('create', App\Models\Material::class)
                    <flux:button variant="primary" wire:click="openMaterialModal" icon="plus">Add Material</flux:button>
                @endcan
            </div>
            {{-- Filters --}}
            <div class="flex gap-4">
                <flux:input wire:model.live="search" placeholder="Search materials..." icon="magnifying-glass" class="flex-1" />
                <flux:select wire:model.live="category_filter" placeholder="All Categories" class="w-48">
                    <flux:select.option value="">All Categories</flux:select.option>
                    @foreach($categories as $category)
                        <flux:select.option value="{{ $category }}">{{ ucfirst($category) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:header>

    <flux:main>
        <div class="space-y-4">
            @forelse($materials as $material)
                <flux:card>
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <flux:heading size="md">{{ $material->name }}</flux:heading>
                                <flux:badge color="gray">{{ $material->code }}</flux:badge>
                                <flux:badge :color="$material->isBelowReorderLevel() ? 'red' : 'green'">
                                    @if($material->isBelowReorderLevel())
                                        Low Stock
                                    @else
                                        In Stock
                                    @endif
                                </flux:badge>
                            </div>

                            @if($material->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $material->description }}</p>
                            @endif

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
                                <div>
                                    <div class="text-xs text-gray-500">Category</div>
                                    <div class="font-medium">{{ ucfirst($material->category) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Unit Cost</div>
                                    <div class="font-medium">{{ $material->formatCurrency($material->unit_cost, 2) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Total Quantity</div>
                                    <div class="font-medium">{{ number_format($material->total_quantity, 2) }} {{ $material->unit }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Available</div>
                                    <div class="font-medium">{{ number_format($material->available_quantity, 2) }} {{ $material->unit }}</div>
                                </div>
                            </div>

                            {{-- Inventory Locations --}}
                            @if($material->inventories->count() > 0)
                                <div class="mt-3 pt-3 border-t dark:border-gray-700">
                                    <div class="text-xs font-medium text-gray-500 mb-2">Inventory Locations:</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($material->inventories as $inventory)
                                            <div class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
                                                {{ $inventory->project->name }} - {{ $inventory->location }} 
                                                ({{ number_format($inventory->remaining_quantity, 2) }} {{ $material->unit }})
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <flux:dropdown align="end">
                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" icon-variant="mini" />
                            <flux:menu>
                                @can('update', $material)
                                    <flux:menu.item icon="pencil" wire:click="openEditMaterialModal({{ $material->id }})">Edit Material</flux:menu.item>
                                @endcan
                                @can('create', App\Models\Inventory::class)
                                    <flux:menu.item icon="cube" wire:click="openInventoryModal({{ $material->id }})">Add to Inventory</flux:menu.item>
                                @endcan
                                @can('delete', $material)
                                    <flux:menu.item icon="trash" variant="danger" wire:click="deleteMaterial({{ $material->id }})" wire:confirm="Delete this material?">Delete</flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </flux:card>
            @empty
                <flux:card class="text-center py-12">
                    <flux:icon.cube class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No materials yet</flux:heading>
                    <p class="text-gray-500 mb-4">Add materials to track inventory and costs</p>
                    @can('create', App\Models\Material::class)
                        <flux:button variant="primary" wire:click="openMaterialModal" icon="plus">Add First Material</flux:button>
                    @endcan
                </flux:card>
            @endforelse

            {{ $materials->links() }}
        </div>
    </flux:main>

    {{-- Material Modal --}}
    <flux:modal name="material-modal" wire:model.self="showMaterialModal" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingMaterial ? 'Edit' : 'Add' }} Material</flux:heading>
                <flux:text class="mt-2">{{ $editingMaterial ? 'Update material details.' : 'Add a new material to your catalog.' }}</flux:text>
            </div>

            <form wire:submit="saveMaterial" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="material_name" label="Material Name" placeholder="e.g., Portland Cement" required />
                    <flux:input wire:model="material_code" label="Code/SKU" placeholder="e.g., CEM-001" required />
                </div>

                <flux:textarea wire:model="material_description" label="Description" placeholder="Brief description" rows="2" />

                <div class="grid grid-cols-3 gap-4">
                    <flux:select wire:model="material_category" label="Category" required>
                        <flux:select.option value="cement">Cement</flux:select.option>
                        <flux:select.option value="steel">Steel</flux:select.option>
                        <flux:select.option value="lumber">Lumber</flux:select.option>
                        <flux:select.option value="electrical">Electrical</flux:select.option>
                        <flux:select.option value="plumbing">Plumbing</flux:select.option>
                        <flux:select.option value="roofing">Roofing</flux:select.option>
                        <flux:select.option value="finishing">Finishing</flux:select.option>
                        <flux:select.option value="hardware">Hardware</flux:select.option>
                        <flux:select.option value="safety">Safety</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="material_unit" label="Unit" required>
                        <flux:select.option value="pieces">Pieces</flux:select.option>
                        <flux:select.option value="kg">Kilograms</flux:select.option>
                        <flux:select.option value="m3">Cubic Meters</flux:select.option>
                        <flux:select.option value="m2">Square Meters</flux:select.option>
                        <flux:select.option value="m">Meters</flux:select.option>
                        <flux:select.option value="liters">Liters</flux:select.option>
                        <flux:select.option value="bags">Bags</flux:select.option>
                        <flux:select.option value="sheets">Sheets</flux:select.option>
                        <flux:select.option value="rolls">Rolls</flux:select.option>
                    </flux:select>

                    <flux:input wire:model="material_unit_cost" type="number" step="0.01" label="Unit Cost" required />
                </div>

                <flux:select wire:model="material_currency" label="Currency" required>
                    <flux:select.option value="USD">USD ($)</flux:select.option>
                    <flux:select.option value="NGN">NGN (₦)</flux:select.option>
                </flux:select>

                <flux:input wire:model="material_reorder_level" type="number" label="Reorder Level" required />

                <flux:textarea wire:model="material_specifications" label="Specifications" placeholder="Technical specifications" rows="2" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ $editingMaterial ? 'Update' : 'Add' }} Material</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Add to Inventory Modal --}}
    <flux:modal name="inventory-modal" wire:model.self="showInventoryModal" class="md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add to Inventory</flux:heading>
                <flux:text class="mt-2">Add material to a project's inventory</flux:text>
            </div>

            <form wire:submit="saveInventory" class="space-y-6">
                <flux:select wire:model="inventory_project_id" label="Project" required>
                    <flux:select.option value="">Select Project</flux:select.option>
                    @foreach($projects as $project)
                        <flux:select.option value="{{ $project->id }}">{{ $project->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="inventory_quantity" type="number" step="0.01" label="Quantity" required />

                <flux:input wire:model="inventory_location" label="Storage Location" placeholder="e.g., Site Storage Area A" required />

                <flux:textarea wire:model="inventory_notes" label="Notes" placeholder="Additional notes" rows="2" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Add to Inventory</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
