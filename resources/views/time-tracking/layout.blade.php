<x-layouts.app>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <flux:toast position="top end" class="pt-20" />
        <flux:container>
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="md:flex md:items-center md:justify-between">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                                {{ $title ?? 'Time Tracking' }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $subtitle ?? 'Manage your time entries and leave requests' }}
                            </p>
                        </div>
                        @if(isset($actions))
                            <div class="mt-4 flex md:mt-0 md:ml-4">
                                {{ $actions }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </flux:container>
    </div>
</x-layouts.app>
