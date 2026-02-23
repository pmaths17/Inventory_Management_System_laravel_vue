<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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

        return $this->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permissionSlug))
            ->exists();
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
        return $this->hasRole('admin') || $this->role === 'admin';
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
        $this->roles()->sync([$role->id]);
        $this->syncLegacyRoleFromRoles();
    }
}
