<div>
    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm font-medium text-gray-500">Today</div>
            <div class="text-2xl font-bold text-blue-600">{{ number_format($todayHours, 2) }}h</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm font-medium text-gray-500">This Week</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($weekHours, 2) }}h</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm font-medium text-gray-500">This Month</div>
            <div class="text-2xl font-bold text-purple-600">{{ number_format($monthHours, 2) }}h</div>
        </div>
    </div>

    <!-- Clock In/Out Interface -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Time Tracking</h2>
        
        @if($activeEntry)
            <!-- Active Entry Display -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-green-800 font-medium">Currently Clocked In</div>
                        <div class="text-green-600 text-sm">Since: {{ $activeEntry->clock_in->format('h:i A') }}</div>
                        <div class="text-green-600 text-sm">Duration: {{ $activeTime }}</div>
                        <div class="text-green-600 text-sm">Estimated Hours: {{ number_format($estimatedHours, 2) }}</div>
                        @if($activeEntry->break_duration > 0)
                            <div class="text-green-600 text-sm">Break: {{ $activeEntry->break_duration }} minutes</div>
                        @endif
                    </div>
                    <div class="flex space-x-2">
                        <button wire:click="openBreakModal" 
                                class="px-3 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">
                            Break
                        </button>
                        <button wire:click="clockOut" 
                                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                            Clock Out
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active Entry Form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                    <select wire:model.live="selectedProject" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Project</option>
                        @foreach($projects as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task</label>
                    <select wire:model="selectedTask" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @if(!$selectedProject) disabled @endif>
                        <option value="">Select Task</option>
                        @foreach($tasks as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <div x-data="locationTracker()" x-init="init()">
                        <!-- Location display -->
                        <div x-show="!loading && !error" class="flex items-center gap-2">
                            <flux:icon.map-pin class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-600" x-text="location || 'Getting location...'"></span>
                            <button type="button" 
                                    @click="refreshLocation()" 
                                    class="text-blue-500 hover:text-blue-700 text-sm">
                                Refresh
                            </button>
                        </div>
                        
                        <!-- Loading state -->
                        <div x-show="loading" class="flex items-center gap-2">
                            <flux:icon.arrow-path class="w-4 h-4 text-blue-500 animate-spin" />
                            <span class="text-sm text-gray-600">Getting location...</span>
                        </div>
                        
                        <!-- Error state -->
                        <div x-show="error" class="flex items-center gap-2">
                            <flux:icon.exclamation-triangle class="w-4 h-4 text-red-500" />
                            <span class="text-sm text-red-600" x-text="error"></span>
                            <button type="button" 
                                    @click="refreshLocation()" 
                                    class="text-blue-500 hover:text-blue-700 text-sm">
                                Retry
                            </button>
                        </div>
                        
                        <!-- Hidden input to store location data -->
                        <input type="hidden" 
                               wire:model="location" 
                               x-model="rawLocation">
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea wire:model="notes" 
                              rows="3"
                              placeholder="Add notes about your work..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
        @else
            <!-- Clock In Form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                    <select wire:model.live="selectedProject" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Project</option>
                        @foreach($projects as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task</label>
                    <select wire:model="selectedTask" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @if(!$selectedProject) disabled @endif>
                        <option value="">Select Task</option>
                        @foreach($tasks as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <div x-data="locationTracker()" x-init="init()">
                        <!-- Location display -->
                        <div x-show="!loading && !error" class="flex items-center gap-2">
                            <flux:icon.map-pin class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-600" x-text="location || 'Click Clock In to get location'"></span>
                            <button type="button" 
                                    @click="refreshLocation()" 
                                    class="text-blue-500 hover:text-blue-700 text-sm">
                                Refresh
                            </button>
                        </div>
                        
                        <!-- Loading state -->
                        <div x-show="loading" class="flex items-center gap-2">
                            <flux:icon.arrow-path class="w-4 h-4 text-blue-500 animate-spin" />
                            <span class="text-sm text-gray-600">Getting location...</span>
                        </div>
                        
                        <!-- Error state -->
                        <div x-show="error" class="flex items-center gap-2">
                            <flux:icon.exclamation-triangle class="w-4 h-4 text-red-500" />
                            <span class="text-sm text-red-600" x-text="error"></span>
                            <button type="button" 
                                    @click="refreshLocation()" 
                                    class="text-blue-500 hover:text-blue-700 text-sm">
                                Retry
                            </button>
                        </div>
                        
                        <!-- Hidden input to store location data -->
                        <input type="hidden" 
                               wire:model="location" 
                               x-model="rawLocation">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                    <button wire:click="clockIn" 
                            class="w-full px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition font-medium">
                        Clock In
                    </button>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea wire:model="notes" 
                              rows="3"
                              placeholder="Add notes about your work..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
        @endif
    </div>

    <!-- Recent Time Entries -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Recent Time Entries</h2>
        
        @if($recentEntries->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock In</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentEntries as $entry)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $entry->clock_in->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $entry->clock_in->format('h:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $entry->clock_out ? $entry->clock_out->format('h:i A') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($entry->total_hours, 2) }}h
                                    @if($entry->break_duration > 0)
                                        <span class="text-xs text-gray-500">({{ $entry->break_duration }}m break)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $entry->project?->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ Str::limit($entry->notes, 50) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                No time entries found. Start by clocking in!
            </div>
        @endif
    </div>

    <!-- Break Duration Modal -->
    @if($showBreakModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Update Break Duration</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Break Duration (minutes)</label>
                        <input type="number" 
                               wire:model="breakDuration" 
                               min="0" 
                               max="480"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Maximum 480 minutes (8 hours)</p>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button wire:click="$set('showBreakModal', false)" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button wire:click="updateBreakDuration" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                            Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function locationTracker() {
    return {
        loading: false,
        error: null,
        location: null,
        rawLocation: null,
        
        init() {
            // Don't get location on page load - wait for user action
        },
        
        refreshLocation() {
            this.loading = true;
            this.error = null;
            
            if (!navigator.geolocation) {
                this.error = 'Geolocation is not supported by your browser';
                this.loading = false;
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.handleSuccess(position);
                },
                (error) => {
                    this.handleError(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000 // 5 minutes cache
                }
            );
        },
        
        handleSuccess(position) {
            const { latitude, longitude } = position.coords;
            this.rawLocation = `${latitude},${longitude}`;
            this.location = `Lat: ${latitude.toFixed(6)}, Lng: ${longitude.toFixed(6)}`;
            this.loading = false;
            
            // Optional: Get address from coordinates (requires geocoding API)
            // this.reverseGeocode(latitude, longitude);
        },
        
        handleError(error) {
            this.loading = false;
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    this.error = 'Location access denied. Please enable location permissions.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    this.error = 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    this.error = 'Location request timed out.';
                    break;
                default:
                    this.error = 'An unknown error occurred.';
                    break;
            }
        },
        
        // Optional: Add reverse geocoding if you have an API
        async reverseGeocode(lat, lng) {
            try {
                // Example using OpenStreetMap Nominatim (free)
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
                );
                const data = await response.json();
                
                if (data.display_name) {
                    this.location = data.display_name;
                }
            } catch (error) {
                console.error('Geocoding failed:', error);
                // Keep coordinates as fallback
            }
        }
    };
}

// Auto-get location when clocking in
document.addEventListener('alpine:init', () => {
    document.addEventListener('click', (e) => {
        if (e.target.closest('button[wire\\:click*="clockIn"]')) {
            // Trigger location refresh before clocking in
            const tracker = Alpine.$data(e.target.closest('[x-data]'));
            if (tracker && tracker.refreshLocation) {
                tracker.refreshLocation();
            }
        }
    });
});
</script>
