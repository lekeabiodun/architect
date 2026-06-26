<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    @can('viewDashboard', App\Models\User::class)
                        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    @endcan
                    
                    @can('viewProjects', App\Models\User::class)
                        <flux:navlist.item icon="folder-git-2" :href="route('projects.index')" :current="request()->routeIs('projects.*')" wire:navigate>
                            {{ __('Projects') }}
                        </flux:navlist.item>
                    @endcan
                    
                    @can('viewMyTasks', App\Models\User::class)
                        <flux:navlist.item icon="clipboard-document-list" :href="route('tasks.my-tasks')" :current="request()->routeIs('tasks.my-tasks')" wire:navigate>
                            {{ __('My Tasks') }}
                        </flux:navlist.item>
                    @endcan

                    {{-- @can('viewMaterials', App\Models\User::class)
                        <flux:navlist.item icon="cube" :href="route('materials.index')" :current="request()->routeIs('materials.*')" wire:navigate>
                            {{ __('Materials') }}
                        </flux:navlist.item>
                    @endcan --}}

                    @can('viewMaterialRequests', App\Models\User::class)
                        <flux:navlist.item icon="inbox-stack" :href="route('material-requests.index')" :current="request()->routeIs('material-requests.*')" wire:navigate>
                            {{ __('Material Requests') }}
                        </flux:navlist.item>
                    @endcan

                     @can('inspectTasks', App\Models\User::class)
                        <flux:navlist.item icon="check-badge" :href="route('inspector.dashboard')" :current="request()->routeIs('inspector.*')" wire:navigate>
                            {{ __('Inspections') }}
                        </flux:navlist.item>
                    @endcan

                    {{-- Time Tracking --}}
                    @canany(['createTimeEntries', 'manageTimeTracking', 'createLeaveRequests', 'approveLeave'], App\Models\User::class)
                        <flux:navlist.group :heading="__('Time Tracking')" class="grid">
                            @can('createTimeEntries', App\Models\User::class)
                                <flux:navlist.item icon="clock" :href="route('time-tracking.index')" :current="request()->routeIs('time-tracking.index')" wire:navigate>
                                    {{ __('Clock In/Out') }}
                                </flux:navlist.item>
                            @endcan

                            @can('manageTimeTracking', App\Models\User::class)
                                <flux:navlist.item icon="chart-bar" :href="route('time-tracking.timesheet')" :current="request()->routeIs('time-tracking.timesheet')" wire:navigate>
                                    {{ __('Timesheet Admin') }}
                                </flux:navlist.item>
                            @endcan

                            @can('createLeaveRequests', App\Models\User::class)
                                <flux:navlist.item icon="calendar-days" :href="route('time-tracking.leave')" :current="request()->routeIs('time-tracking.leave')" wire:navigate>
                                    {{ __('Leave Requests') }}
                                </flux:navlist.item>
                            @endcan

                            @can('approveLeave', App\Models\User::class)
                                <flux:navlist.item icon="check-circle" :href="route('time-tracking.leave-approval')" :current="request()->routeIs('time-tracking.leave-approval')" wire:navigate>
                                    {{ __('Leave Approval') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    
                </flux:navlist.group>

                {{-- Admin Settings (Super Admin only) --}}
                @can('viewAdminSettings', App\Models\User::class)
                    <flux:navlist.group :heading="__('Administration')" class="grid">
                        @can('viewTeamMembers', App\Models\User::class)
                            <flux:navlist.item icon="user-group" :href="route('team.index')" :current="request()->routeIs('team.*')" wire:navigate>
                                {{ __('Team Members') }}
                            </flux:navlist.item>
                        @endcan
                        <flux:navlist.item icon="shield-check" :href="route('settings.roles-permissions')" :current="request()->routeIs('settings.roles-permissions')" wire:navigate>
                            {{ __('Roles & Permissions') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="user-circle" :href="route('settings.user-roles')" :current="request()->routeIs('settings.user-roles')" wire:navigate>
                            {{ __('User Role Management') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                @endcan
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                {{-- <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item> --}}
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <flux:toast position="top end" class="pt-20" />

        @fluxScripts
    </body>
</html>
