<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get projects where user is the manager
     */
    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    /**
     * Get all projects user is assigned to
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Get tasks assigned to this user
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Get comments by this user
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Check if user is a project manager
     */
    public function isProjectManager(): bool
    {
        return $this->hasRole('project_manager');
    }

    /**
     * Check if user is a contractor
     */
    public function isContractor(): bool
    {
        return $this->hasRole('contractor');
    }

    /**
     * Check if user is a client
     */
    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    /**
     * Check if user is an inspector
     */
    public function isInspector(): bool
    {
        return $this->hasRole('inspector');
    }

    /**
     * Check if user is an engineer
     */
    public function isEngineer(): bool
    {
        return $this->hasRole('engineer');
    }

    /**
     * Check if user is a developer
     */
    public function isDeveloper(): bool
    {
        return $this->hasRole('developer');
    }

    /**
     * Check if user is a director
     */
    public function isDirector(): bool
    {
        return $this->hasRole('director');
    }

    /**
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user is a worker
     */
    public function isWorker(): bool
    {
        return $this->hasRole('worker');
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Determine the landing route name for this user after login.
     *
     * Roles that cannot view the main dashboard are sent to the first
     * section they can actually access, so they never hit a 403 on '/'.
     */
    public function homeRoute(): string
    {
        if ($this->isClient()) {
            return 'projects.index';
        }

        if ($this->isInspector()) {
            return 'inspector.dashboard';
        }

        if ($this->isWorker()) {
            return 'tasks.my-tasks';
        }

        return 'dashboard';
    }

    /**
     * Check if user can manage materials/inventory
     */
    public function canManageMaterials(): bool
    {
        return $this->hasAnyRole(['super_admin', 'project_manager', 'director', 'manager']) ||
            $this->hasPermissionTo('manage materials');
    }

    /**
     * Check if user can view materials/inventory
     */
    public function canViewMaterials(): bool
    {
        // Clients cannot view materials
        if ($this->hasRole('client')) {
            return false;
        }

        // Workers can only view materials for their tasks
        if ($this->hasRole('worker')) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can approve material requests
     */
    public function canApproveMaterialRequests(): bool
    {
        return $this->hasAnyRole(['super_admin', 'director', 'manager', 'project_manager']) ||
            $this->hasPermissionTo('approve material requests');
    }

    /**
     * Check if user can inspect tasks
     */
    public function canInspectTasks(): bool
    {
        return $this->hasAnyRole(['inspector', 'super_admin']) ||
            $this->hasPermissionTo('inspect tasks');
    }

    /**
     * Get projects accessible by this user based on role
     */
    public function getAccessibleProjects()
    {
        if ($this->isSuperAdmin()) {
            return Project::all();
        }

        if ($this->isClient()) {
            // Clients can only view their own projects
            return $this->projects;
        }

        // All other roles can view projects assigned to them
        return $this->projects()->with(['phases', 'users'])->get();
    }

    /**
     * Get material requests for this user
     */
    public function materialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'requested_by');
    }

    /**
     * Get material requests approved by this user
     */
    public function approvedMaterialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'approved_by');
    }

    /**
     * Get material requests disbursed by this user
     */
    public function disbursedMaterialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'disbursed_by');
    }

    /**
     * Get material requests confirmed by this user (inspector)
     */
    public function confirmedMaterialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'confirmed_by');
    }

    /**
     * Get tasks inspected by this user
     */
    public function inspectedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'inspected_by');
    }

    /**
     * Get time entries for this user
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get active time entry for this user
     */
    public function activeTimeEntry(): ?TimeEntry
    {
        return $this->timeEntries()->active()->first();
    }

    /**
     * Get leave requests for this user
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get leave balances for this user
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Get leave balance for specific type and year
     */
    public function getLeaveBalance(string $leaveType, int $year): ?LeaveBalance
    {
        return $this->leaveBalances()
            ->where('leave_type', $leaveType)
            ->where('year', $year)
            ->first();
    }

    /**
     * Check if user can manage time tracking (admin functionality).
     *
     * Permission-driven: roles that should manage timesheets are granted
     * 'manage time entries' in the seeder (super_admin holds all permissions).
     */
    public function canManageTimeTracking(): bool
    {
        return $this->hasPermissionTo('manage time entries');
    }

    /**
     * Check if user can approve leave requests.
     *
     * Permission-driven: roles that should approve leave are granted
     * 'approve leave requests' in the seeder (super_admin holds all permissions).
     */
    public function canApproveLeave(): bool
    {
        return $this->hasPermissionTo('approve leave requests');
    }

    /**
     * Get total hours worked in a date range
     */
    public function getTotalHoursWorked($startDate, $endDate): float
    {
        return $this->timeEntries()
            ->whereBetween('clock_in', [$startDate, $endDate])
            ->get()
            ->sum('total_hours');
    }

    /**
     * Get current work hours today
     */
    public function getTodayHoursAttribute(): float
    {
        return $this->getTotalHoursWorked(now()->startOfDay(), now()->endOfDay());
    }

    /**
     * Get current week hours
     */
    public function getWeekHoursAttribute(): float
    {
        return $this->getTotalHoursWorked(now()->startOfWeek(), now()->endOfWeek());
    }

    /**
     * Get current month hours
     */
    public function getMonthHoursAttribute(): float
    {
        return $this->getTotalHoursWorked(now()->startOfMonth(), now()->endOfMonth());
    }
}
