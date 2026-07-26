<?php

namespace App\Models;

use App\Enums\UserStatus;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
