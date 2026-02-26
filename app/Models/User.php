<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

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

    protected $appends = [
        'primary_role',
        'is_admin',
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
    // Helpers
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $roleSlug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $role) => $role->slug === $roleSlug && $role->is_active);
        }

        return $this->roles()->where('slug', $roleSlug)->where('is_active', true)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->filter(fn (Role $role) => $role->is_active)
                ->flatMap(fn (Role $role) => $role->permissions)
                ->contains(fn (Permission $permission) => $permission->slug === $permissionSlug);
        }

        return in_array($permissionSlug, $this->effectivePermissionSlugs(), true);
    }

    /**
     * @return array<int,string>
     */
    public function effectivePermissionSlugs(): array
    {
        return Cache::remember($this->permissionCacheKey(), now()->addMinutes(10), function () {
            return $this->roles()
                ->where('roles.is_active', true)
                ->join('permission_role', 'roles.id', '=', 'permission_role.role_id')
                ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
                ->distinct()
                ->pluck('permissions.slug')
                ->values()
                ->all();
        });
    }

    public function forgetPermissionCache(): void
    {
        Cache::forget($this->permissionCacheKey());
    }

    public static function forgetPermissionCacheForRole(int $roleId): void
    {
        $userIds = DB::table('role_user')
            ->where('role_id', $roleId)
            ->pluck('user_id');

        static::forgetPermissionCacheForUsers($userIds->all());
    }

    public static function forgetPermissionCacheForPermission(int $permissionId): void
    {
        $userIds = DB::table('permission_role')
            ->join('roles', 'roles.id', '=', 'permission_role.role_id')
            ->join('role_user', 'role_user.role_id', '=', 'roles.id')
            ->where('permission_role.permission_id', $permissionId)
            ->where('roles.is_active', true)
            ->pluck('role_user.user_id');

        static::forgetPermissionCacheForUsers($userIds->all());
    }

    /**
     * @param iterable<int,int|string> $userIds
     */
    public static function forgetPermissionCacheForUsers(iterable $userIds): void
    {
        collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->each(fn (int $id) => Cache::forget(static::permissionCacheKeyForId($id)));
    }

    public static function forgetPermissionCacheForAllUsers(): void
    {
        static::query()->select('id')->chunkById(500, function ($users): void {
            static::forgetPermissionCacheForUsers($users->pluck('id')->all());
        });
    }

    private function permissionCacheKey(): string
    {
        return static::permissionCacheKeyForId((int) $this->id);
    }

    private static function permissionCacheKeyForId(int $userId): string
    {
        return "user_permissions:{$userId}";
    }

    public function getPrimaryRoleAttribute(): ?string
    {
        $firstRole = $this->roles()->where('is_active', true)->orderBy('roles.id')->first();
        if ($firstRole) {
            return $firstRole->slug;
        }

        return $this->role;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function syncLegacyRoleFromRoles(): void
    {
        $primaryRole = $this->roles()->where('is_active', true)->orderBy('roles.id')->first();
        $legacyRole = ($primaryRole && in_array($primaryRole->slug, ['admin', 'staff'], true))
            ? $primaryRole->slug
            : 'staff';

        if ($this->role !== $legacyRole) {
            $this->forceFill(['role' => $legacyRole])->save();
        }
    }

    public function assignRole(Role $role): void
    {
        $this->syncRoles([$role]);
    }

    /**
     * @param iterable<int,Role|int|string> $roles
     */
    public function syncRoles(iterable $roles): void
    {
        $roleIds = collect($roles)
            ->map(fn ($role) => $role instanceof Role ? $role->id : (int) $role)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->roles()->sync($roleIds);
        $this->syncLegacyRoleFromRoles();
        $this->forgetPermissionCache();
    }
}
