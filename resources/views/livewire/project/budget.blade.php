<div>
    <flux:header class="space-y-4">
        <div class="w-full space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <flux:button variant="ghost" icon="arrow-left" href="{{ route('projects.show', $project->id) }}" wire:navigate>Back</flux:button>
                    <div>
                        <flux:heading size="lg">Budget Dashboard</flux:heading>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $project->name }}</p>
                    </div>
                </div>
                <flux:button variant="primary" wire:click="updateActualCost" icon="arrow-path">
                    Refresh Costs
                </flux:button>
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Estimated Budget</div>
                    <div class="font-semibold text-2xl">{{ $project->formatCurrency($project->estimated_budget ?? 0, 0) }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Actual Cost</div>
                    <div class="font-semibold text-2xl">{{ $project->formatCurrency($project->actual_cost ?? 0, 0) }}</div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Variance</div>
                    <div class="font-semibold text-2xl {{ $project->budget_variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $project->formatCurrency(abs($project->budget_variance), 0) }}
                        <span class="text-sm">{{ $project->budget_variance >= 0 ? 'under' : 'over' }}</span>
                    </div>
                </flux:card>
                <flux:card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Utilization</div>
                    <div class="font-semibold text-2xl">{{ number_format($project->budget_utilization, 1) }}%</div>
                </flux:card>
            </div>
        </div>

        {{-- Budget Progress Bar --}}
        <flux:card>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium">Budget Utilization</span>
                <span class="text-sm font-bold">{{ number_format($project->budget_utilization, 1) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                <div class="h-4 rounded-full transition-all {{ $project->budget_utilization > 100 ? 'bg-red-600' : ($project->budget_utilization > 80 ? 'bg-yellow-500' : 'bg-green-600') }}" 
                     style="width: {{ min($project->budget_utilization, 100) }}%">
                </div>
            </div>
            @if($project->budget_utilization > 100)
                <p class="text-sm text-red-600 dark:text-red-400 mt-2">⚠️ Budget exceeded by {{ $project->formatCurrency(abs($project->budget_variance), 0) }}</p>
            @elseif($project->budget_utilization > 80)
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2">⚠️ Approaching budget limit</p>
            @else
                <p class="text-sm text-green-600 dark:text-green-400 mt-2">✓ Within budget</p>
            @endif
        </flux:card>
    </flux:header>

    <flux:main>
        <div class="space-y-6">
            {{-- Task Cost Summary --}}
            <flux:card>
                <flux:heading size="md" class="mb-4">Task Cost Summary</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Estimated Task Costs</div>
                        <div class="text-2xl font-bold">{{ $project->formatCurrency($costs['estimated'], 0) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Actual Task Costs</div>
                        <div class="text-2xl font-bold">{{ $project->formatCurrency($costs['actual'], 0) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Task Variance</div>
                        <div class="text-2xl font-bold {{ $costs['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $project->formatCurrency(abs($costs['variance']), 0) }}
                            <span class="text-sm">{{ $costs['variance'] >= 0 ? 'saved' : 'over' }}</span>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Phase-wise Breakdown --}}
            <flux:card>
                <flux:heading size="md" class="mb-4">Phase-wise Cost Breakdown</flux:heading>
                <div class="space-y-4">
                    @forelse($phaseBreakdown as $breakdown)
                        <div class="p-4 border dark:border-gray-700 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <flux:heading size="sm">{{ $breakdown['phase']->name }}</flux:heading>
                                        <span class="text-xs text-gray-500">{{ $breakdown['phase']->tasks->count() }} tasks</span>
                                    </div>
                                </div>
                                <div class="text-sm font-medium {{ $breakdown['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $breakdown['variance'] >= 0 ? '↓' : '↑' }} 
                                    ${{ number_format(abs($breakdown['variance']), 0) }}
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-4 mb-3">
                                <div>
                                    <div class="text-xs text-gray-500">Estimated</div>
                                    <div class="font-semibold">{{ $project->formatCurrency($breakdown['estimated'], 0) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Actual</div>
                                    <div class="font-semibold">{{ $project->formatCurrency($breakdown['actual'], 0) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Variance</div>
                                    <div class="font-semibold {{ $breakdown['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $project->formatCurrency(abs($breakdown['variance']), 0) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Utilization</div>
                                    <div class="font-semibold">{{ number_format($breakdown['utilization'], 1) }}%</div>
                                </div>
                            </div>

                            {{-- Phase Progress Bar --}}
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="h-2 rounded-full transition-all {{ $breakdown['utilization'] > 100 ? 'bg-red-600' : 'bg-blue-600' }}" 
                                     style="width: {{ min($breakdown['utilization'], 100) }}%">
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-8">No phases with cost data yet</p>
                    @endforelse
                </div>
            </flux:card>

            {{-- Detailed Task List --}}
            <flux:card>
                <flux:heading size="md" class="mb-4">Task Cost Details</flux:heading>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b dark:border-gray-700">
                            <tr>
                                <th class="text-left py-3 px-4 text-sm font-semibold">Task</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold">Phase</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold">Estimated</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold">Actual</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold">Variance</th>
                                <th class="text-center py-3 px-4 text-sm font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->phases as $phase)
                                @foreach($phase->tasks as $task)
                                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="py-3 px-4">
                                            <div class="font-medium">{{ $task->name }}</div>
                                            @if($task->assignedUser)
                                                <div class="text-xs text-gray-500">{{ $task->assignedUser->name }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-sm">{{ $phase->name }}</td>
                                        <td class="py-3 px-4 text-right">{{ $project->formatCurrency($task->estimated_cost ?? 0, 0) }}</td>
                                        <td class="py-3 px-4 text-right">{{ $project->formatCurrency($task->actual_cost ?? 0, 0) }}</td>
                                        <td class="py-3 px-4 text-right {{ $task->cost_variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $project->formatCurrency(abs($task->cost_variance), 0) }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <flux:badge :color="match($task->status) {
                                                'completed' => 'green',
                                                'in_progress' => 'blue',
                                                'pending' => 'gray',
                                                default => 'gray'
                                            }" size="sm">
                                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                            </flux:badge>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 dark:border-gray-600 font-bold">
                            <tr>
                                <td colspan="2" class="py-3 px-4">Total</td>
                                <td class="py-3 px-4 text-right">{{ $project->formatCurrency($costs['estimated'], 0) }}</td>
                                <td class="py-3 px-4 text-right">{{ $project->formatCurrency($costs['actual'], 0) }}</td>
                                <td class="py-3 px-4 text-right {{ $costs['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $project->formatCurrency(abs($costs['variance']), 0) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </flux:card>
        </div>
    </flux:main>
</div>
