<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role_id', 'name', 'email', 'phone', 'status', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function worker(): HasOne
    {
        return $this->hasOne(Worker::class);
    }

    public function hasRole(string ...$slugs): bool
    {
        $slug = $this->role?->slug;

        return $slug !== null && in_array($slug, $slugs, true);
    }

    public function scopeWithRole($query, string $slug)
    {
        return $query->where('status', 'active')->whereHas('role', fn ($roles) => $roles->where('slug', $slug));
    }

    public function roleName(): string
    {
        return $this->role?->name ?? 'User';
    }

    public function reviewedReports(): HasMany
    {
        return $this->hasMany(DailyReport::class, 'reviewed_by');
    }
}
