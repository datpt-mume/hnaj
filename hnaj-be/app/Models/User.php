<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\UserStatus;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'google_id',
        'avatar_url',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
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
            'status' => UserStatus::class,
        ];
    }

    /**
     * Kiểm tra role trên relation đã load để tránh truy vấn lặp trong middleware.
     */
    public function hasRole(RoleName $role): bool
    {
        return $this->roles
            ->contains(fn (Role $assigned): bool => $assigned->name === $role->value);
    }

    public function hasAnyRole(RoleName ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function roleNames(): array
    {
        return $this->roles->pluck('name')->all();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasVerifiedEmailAddress(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function accountSetupTokens(): HasMany
    {
        return $this->hasMany(AccountSetupToken::class);
    }

    public function emailVerificationTokens(): HasMany
    {
        return $this->hasMany(EmailVerificationToken::class);
    }

    public function createdPlaces(): HasMany
    {
        return $this->hasMany(Place::class, 'created_by');
    }

    public function uploadedImages(): HasMany
    {
        return $this->hasMany(PlaceImage::class, 'uploaded_by');
    }

    public function managedPlaces(): HasMany
    {
        return $this->hasMany(PlaceManager::class);
    }

    public function assignedManagerRoles(): HasMany
    {
        return $this->hasMany(PlaceManager::class, 'assigned_by');
    }

    public function assignedRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'assigned_by');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function visitEvents(): HasMany
    {
        return $this->hasMany(VisitEvent::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function submittedPlaceRequests(): HasMany
    {
        return $this->hasMany(PlaceRequest::class, 'submitted_by');
    }

    public function reviewedPlaceRequests(): HasMany
    {
        return $this->hasMany(PlaceRequest::class, 'reviewed_by');
    }

    public function reviewedManagerApplications(): HasMany
    {
        return $this->hasMany(ManagerApplication::class, 'reviewed_by');
    }

    public function approvedManagerApplications(): HasMany
    {
        return $this->hasMany(ManagerApplication::class, 'approved_user_id');
    }

    public function submittedPromotionRequests(): HasMany
    {
        return $this->hasMany(PromotionRequest::class, 'submitted_by');
    }

    public function reviewedPromotionRequests(): HasMany
    {
        return $this->hasMany(PromotionRequest::class, 'reviewed_by');
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(ModerationAction::class, 'performed_by');
    }

    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }
}
