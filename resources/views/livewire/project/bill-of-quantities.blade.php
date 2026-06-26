<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <flux:button variant="ghost" icon="arrow-left" href="{{ route('projects.show', $project->id) }}">Back</flux:button>
                    <flux:heading size="lg">Bill of Quantities - {{ $project->name }}</flux:heading>
                </div>
                @if($canCreate)
                <flux:button variant="primary" wire:click="openModal" icon="plus">Add Item</flux:button>
                @endif
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Items</div>
                    <div class="font-semibold">{{ $billOfQuantities->count() }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Amount</div>
                    <div class="font-semibold">{{ $project->formatCurrency($billOfQuantities->sum('total_amount'), 2) }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Categories</div>
                    <div class="font-semibold">{{ $categories->count() }}</div>
                </flux:card>
            </div>
        </div>
    </flux:header>

    <flux:main>
        <div class="space-y-6">
            @if($billOfQuantities->count() > 0)
                <flux:card>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Order</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Item Code</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Description</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Unit</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Requestable</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Consumed</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Remaining</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Unit Rate</th>
                                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Total</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Category</th>
                                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($billOfQuantities as $item)
                                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="py-3 px-4 text-sm">{{ $item->order }}</td>
                                        <td class="py-3 px-4 text-sm font-mono">{{ $item->item_code ?? '-' }}</td>
                                        <td class="py-3 px-4 text-sm font-medium">{{ $item->description }}</td>
                                        <td class="py-3 px-4 text-sm">{{ $item->unit }}</td>
                                        <td class="py-3 px-4 text-sm text-right">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="py-3 px-4 text-sm text-right">{{ number_format($item->requestable_quantity, 2) }}</td>
                                        <td class="py-3 px-4 text-sm text-right">
                                            <span class="{{ $item->consumed_quantity > 0 ? 'text-orange-600 font-medium' : '' }}">
                                                {{ number_format($item->consumed_quantity, 2) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-right">
                                            <span class="{{ $item->remaining_quantity > 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                                {{ number_format($item->remaining_quantity, 2) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-right">{{ $project->formatCurrency($item->unit_rate, 2) }}</td>
                                        <td class="py-3 px-4 text-sm font-semibold text-right">{{ $project->formatCurrency($item->total_amount, 2) }}</td>
                                        <td class="py-3 px-4 text-sm">
                                            @if($item->category)
                                                <flux:badge variant="outline">{{ $item->category }}</flux:badge>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center justify-center gap-1">
                                                @can('update', $item)
                                                    <flux:button size="sm" variant="ghost" icon="arrow-up" wire:click="moveUp({{ $item->id }})" title="Move Up"></flux:button>
                                                    <flux:button size="sm" variant="ghost" icon="arrow-down" wire:click="moveDown({{ $item->id }})" title="Move Down"></flux:button>
                                                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEditModal({{ $item->id }})" title="Edit"></flux:button>
                                                @endcan
                                                @can('delete', $item)
                                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="Delete this item?" title="Delete"></flux:button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-200 dark:border-gray-700">
                                    <td colspan="9" class="py-3 px-4 text-sm font-bold text-right">Total:</td>
                                    <td class="py-3 px-4 text-sm font-bold text-right">{{ $project->formatCurrency($billOfQuantities->sum('total_amount'), 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </flux:card>
            @else
                <flux:card class="text-center py-12">
                    <flux:icon.wrench class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No Bill of Quantities items yet</flux:heading>
                    <p class="text-gray-500 mb-4">Add items to create your bill of quantities</p>
                    @if($canCreate)
                        <flux:button variant="primary" wire:click="openModal" icon="plus">Add First Item</flux:button>
                    @endif
                </flux:card>
            @endif
        </div>
    </flux:main>

    {{-- Add/Edit Modal --}}
    <flux:modal name="boq-modal" wire:model.self="showModal" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingItem ? 'Edit' : 'Add' }} Bill of Quantities Item</flux:heading>
                <flux:text class="mt-2">{{ $editingItem ? 'Update item details.' : 'Add a new item to the bill of quantities.' }}</flux:text>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="item_code" label="Item Code" placeholder="e.g., CON-001" />

                <flux:input wire:model="description" label="Description" placeholder="e.g., Ready Mix Concrete" required />
                
                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="unit" label="Unit" placeholder="e.g., m³, kg, pcs" required />
                    <flux:input wire:model="quantity" type="number" step="0.01" label="Quantity" required />
                    <flux:input wire:model.live="requestable_quantity" type="number" step="0.01" label="Requestable Quantity" required />
                </div>

                <flux:input wire:model="unit_rate" type="number" step="0.01" label="Unit Rate" required />

                <flux:input wire:model="category" label="Category" placeholder="e.g., Concrete, Steel, Labor" />
                
                <flux:textarea wire:model="notes" label="Notes" placeholder="Additional notes or specifications" rows="3" />

                @if($quantity && $unit_rate)
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Calculated Total:</span>
                            <span class="text-lg font-bold">{{ $project->formatCurrency($quantity * $unit_rate, 2) }}</span>
                        </div>
                    </div>
                @endif

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ $editingItem ? 'Update' : 'Add' }} Item</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
