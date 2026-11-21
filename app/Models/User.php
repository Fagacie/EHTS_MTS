<?php

namespace App\Models;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'photo_storage_disk',
        'photo_storage_key',
        'status',
        'status_reason',
        'status_changed_at',
        'status_changed_by',
        'last_login_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'status_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['assigned_by', 'assigned_at']);
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'status_changed_by');
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function instructorProfile(): HasOne
    {
        return $this->hasOne(InstructorProfile::class);
    }

    public function photoUrl(): ?string
    {
        if (blank($this->photo_storage_key)) {
            return null;
        }

        return Storage::disk($this->photo_storage_disk ?: 'public')->url($this->photo_storage_key);
    }

    public function assignedSessions(): BelongsToMany
    {
        return $this->belongsToMany(TrainingSession::class, 'session_instructors', 'instructor_id', 'training_session_id')
            ->withPivot(['id', 'role_label', 'assigned_by', 'assigned_at'])
            ->orderBy('starts_at');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function moduleEntitlements(): HasMany
    {
        return $this->hasMany(ModuleEntitlement::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class)->orderByDesc('issued_at');
    }

    public function hasActiveModuleEntitlement(int $moduleId): bool
    {
        try {
            return DB::table('module_entitlements')
                ->where('user_id', $this->id)
                ->where('module_id', $moduleId)
                ->where('status', 'active')
                ->exists();
        } catch (QueryException) {
            return false;
        }
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasRole(RoleSlug|string $role): bool
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;

        return $this->roles->contains(fn (Role $r) => $r->slug === $slug);
    }

    /**
     * @param  iterable<RoleSlug|string>  $roles
     */
    public function hasAnyRole(iterable $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permissionSlug))
            ->exists();
    }

    public function assignRole(RoleSlug|string $role, ?int $assignedBy = null): void
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;
        $roleModel = Role::query()->where('slug', $slug)->firstOrFail();

        $this->roles()->syncWithoutDetaching([
            $roleModel->id => [
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ],
        ]);

        $this->unsetRelation('roles');
    }

    /**
     * @param  list<RoleSlug|string>  $slugs
     */
    public function syncRolesBySlugs(array $slugs, ?int $assignedBy = null): void
    {
        $normalized = array_map(
            fn (RoleSlug|string $slug) => $slug instanceof RoleSlug ? $slug->value : $slug,
            $slugs,
        );

        $roleIds = Role::query()->whereIn('slug', $normalized)->pluck('id')->all();

        $payload = [];
        foreach ($roleIds as $roleId) {
            $payload[$roleId] = [
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ];
        }

        $this->roles()->sync($payload);
        $this->unsetRelation('roles');
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole(RoleSlug::Administrator);
    }

    public function isInstructor(): bool
    {
        return $this->hasRole(RoleSlug::Instructor);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(RoleSlug::Customer);
    }

    public function primaryRoleSlug(): ?string
    {
        foreach ([RoleSlug::Administrator, RoleSlug::Instructor, RoleSlug::Customer] as $priority) {
            if ($this->hasRole($priority)) {
                return $priority->value;
            }
        }

        return null;
    }

    public function defaultDashboardUrl(): string
    {
        if ($this->isAdministrator()) {
            return route('admin-vue.dashboard');
        }

        if ($this->isInstructor()) {
            return route('instructor.dashboard');
        }

        if ($this->isCustomer()) {
            return route('portal.dashboard');
        }

        return route('home');
    }

    /**
     * @return list<array{slug: string, label: string, description: string, url: string, route_pattern: string}>
     */
    public function availablePortals(): array
    {
        $portals = [];

        if ($this->isCustomer()) {
            $portals[] = [
                'slug' => RoleSlug::Customer->value,
                'label' => 'Trainee Portal',
                'description' => 'Bookings, modules & certificates',
                'url' => route('portal.dashboard'),
                'route_pattern' => 'portal.*',
            ];
        }

        if ($this->isInstructor()) {
            $portals[] = [
                'slug' => RoleSlug::Instructor->value,
                'label' => 'Instructor Portal',
                'description' => 'Sessions, attendance & recordings',
                'url' => route('instructor.dashboard'),
                'route_pattern' => 'instructor.*',
            ];
        }

        if ($this->isAdministrator()) {
            $portals[] = [
                'slug' => RoleSlug::Administrator->value,
                'label' => 'Administrator Panel',
                'description' => 'Vue administrator workspace',
                'url' => route('admin-vue.dashboard'),
                'route_pattern' => 'admin-vue.*',
            ];
        }

        return $portals;
    }

}
