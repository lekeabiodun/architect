<div>
    <flux:header>
        <flux:heading size="lg">Dashboard</flux:heading>
    </flux:header>

    <flux:main>
        <div class="space-y-6">
            {{-- Stats Overview --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <flux:card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Active Projects</div>
                            <div class="text-2xl font-bold">{{ \App\Models\Project::where('status', 'active')->count() }}</div>
                        </div>
                        <flux:icon.folder-open class="w-8 h-8 text-blue-500" />
                    </div>
                </flux:card>

                <flux:card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total Tasks</div>
                            <div class="text-2xl font-bold">{{ \App\Models\Task::count() }}</div>
                        </div>
                        <flux:icon.list-bullet class="w-8 h-8 text-green-500" />
                    </div>
                </flux:card>

                <flux:card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">My Tasks</div>
                            <div class="text-2xl font-bold">{{ auth()->user()->tasks()->where('status', '!=', 'completed')->count() }}</div>
                        </div>
                        <flux:icon.user class="w-8 h-8 text-purple-500" />
                    </div>
                </flux:card>

                <flux:card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                            <div class="text-2xl font-bold">{{ \App\Models\Task::where('status', 'completed')->count() }}</div>
                        </div>
                        <flux:icon.check-circle class="w-8 h-8 text-orange-500" />
                    </div>
                </flux:card>
            </div>

            {{-- Recent Projects --}}
            <flux:card>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">Recent Projects</flux:heading>
                        <flux:button variant="primary" href="{{ route('projects.index') }}">View All Projects</flux:button>
                    </div>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Project Name</flux:table.column>
                            <flux:table.column>Client</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Progress</flux:table.column>
                            <flux:table.column>Manager</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse(\App\Models\Project::with('manager')->latest()->take(5)->get() as $project)
                                <flux:table.row :key="$project->id">
                                    <flux:table.cell>
                                        <a href="{{ route('projects.show', $project->id) }}" class="font-medium text-blue-600 hover:underline">
                                            {{ $project->name }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $project->client_name }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge :color="match($project->status) {
                                            'active' => 'green',
                                            'on_hold' => 'yellow',
                                            'completed' => 'blue',
                                            'cancelled' => 'red',
                                            default => 'gray'
                                        }">
                                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $project->overall_progress }}%"></div>
                                            </div>
                                            <span class="text-sm">{{ number_format($project->overall_progress, 0) }}%</span>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $project->manager?->name ?? 'Unassigned' }}</flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="5" class="text-center text-gray-500">No projects yet</flux:table.cell>
                                </flux:row>
                            @endforelse
                        </flux:rows>
                    </flux:table>
                </div>
            </flux:card>
        </div>
    </flux:main>
</div>
