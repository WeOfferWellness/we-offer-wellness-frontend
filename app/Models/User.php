<?php

namespace App\Models;

use App\Services\OfferingService;
use App\Services\MailService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
        'profile_picture',
        'phone',
        'location',
        'location_name',
        'location_lat',
        'location_lon',
        'language',
        'is_active',
        'is_vendor',
        'two_factor_enabled',
        'google_calendar_token',
        'google_refresh_token',
        'google_token_expires_at',
        'verification_skipped',
        'email_verification_code',
        'email_verification_code_sent_at',
        'account_type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_vendor' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'google_token_expires_at' => 'datetime',
        'verification_skipped' => 'boolean',
        'email_verification_code_sent_at' => 'datetime',
        'account_type' => 'string',
    ];

    /**
     * Role and permission relationships
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permission');
    }

    /**
     * Vendor relationships
     */
    public function vendorDetail()
    {
        return $this->hasOne(VendorDetail::class);
    }

    public function tier()
    {
        return $this->hasOneThrough(
            VendorTier::class, // Final model
            VendorDetail::class, // Intermediate model
            'user_id', // Foreign key on VendorDetail table
            'vendor_id', // Foreign key on VendorTier table
            'id', // Local key on User table
            'id'  // Local key on VendorDetail table
        );
    }

    public function availability()
    {
        return $this->hasMany(VendorAvailability::class);
    }

    public function defaultAvailability()
    {
        return $this->hasMany(VendorAvailabilityDefault::class);
    }

    /**
     * Social and content relationships
     */
    public function posts()
    {
        return $this->hasMany(UserPost::class);
    }

    public function comments()
    {
        return $this->hasMany(UserComment::class);
    }

    public function likes()
    {
        return $this->hasMany(UserLike::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function media()
    {
        return $this->hasMany(UserMedia::class);
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * User Profile relationships
     */
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function bio()
    {
        return $this->hasOne(UserBio::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function waitingList()
    {
        // Always use the latest waiting list status for this user
        return $this->hasOne(UserWaitingList::class)->latestOfMany();
    }

    /**
     * Booking and reservation relationships
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Session and connection relationships
     */
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function connectedAccounts()
    {
        return $this->hasMany(UserConnectedAccount::class);
    }

    /**
     * Social connections: followers, friends
     */
    public function followers()
    {
        return $this->hasMany(UserFollower::class, 'followed_id', 'id');
    }

    public function following()
    {
        return $this->hasMany(UserFollower::class, 'follower_id', 'id');
    }

    public function friendRequestsSent()
    {
        return $this->hasMany(UserFriendRequest::class, 'sender_id', 'id');
    }

    public function friendRequestsReceived()
    {
        return $this->hasMany(UserFriendRequest::class, 'receiver_id', 'id');
    }

    public function friends()
    {
        return $this->hasMany(UserFriend::class, 'user_id', 'id');
    }

    /**
     * Accessors & Helpers
     */

    /**
     * Check if the user is a vendor.
     */
    public function isVendor(): bool
    {
        return (bool) $this->is_vendor;
    }

    /**
     * Check if the user is a journalist.
     */
    public function isJournalist(): bool
    {
        return $this->hasRole('Journalist');
    }

    /**
     * Check if user is an Admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    /**
     * Check if user is a Provider.
     */
    public function isProvider(): bool
    {
        return $this->hasRole('Provider');
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Resolve the active plan key for the user.
     */
    public function resolvePlanKey(): string
    {
        $tierValue = '';

        try {
            $tierValue = (string) (
                $this->tier?->tier
                ?? $this->account_type
                ?? ($this->vendorDetail?->tiers()->orderByDesc('plan_started_at')->orderByDesc('id')->value('tier'))
                ?? ''
            );
        } catch (\Throwable $e) {
            $tierValue = (string) ($this->account_type ?? '');
        }

        return $this->normalizePlanKey($tierValue);
    }

    /**
     * Determine whether the user is on a starter plan.
     */
    public function isStarterPlan(): bool
    {
        return $this->resolvePlanKey() === 'starter';
    }

    /**
     * Determine whether the user is on a business accelerator plan.
     */
    public function isBusinessAcceleratorPlan(): bool
    {
        return $this->resolvePlanKey() === 'business-accelerator';
    }

    /**
     * Normalize a plan value into a stable plan key.
     */
    protected function normalizePlanKey(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = str_replace(['_', ' '], '-', $normalized);

        return match ($normalized) {
            'community', 'starter', 'standard', 'free-starter', 'starter-package' => 'starter',
            'core', 'business-accelerator', 'business-accelerator-package', 'businessaccelerator' => 'business-accelerator',
            'premium', 'premium-accelerator', 'premiumaccelerator' => 'premium-accelerator',
            'become-partner', 'partner' => 'become-partner',
            default => $normalized,
        };
    }

    /**
     * Public display name for profile and card surfaces.
     */
    public function getPublicDisplayNameAttribute(): string
    {
        $personalName = trim((string) ($this->full_name ?: trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''))));
        if ($personalName !== '') {
            return $personalName;
        }

        if ($this->isAdmin()) {
            return 'Team member';
        }

        if (! $this->isStarterPlan()) {
            $accountName = trim((string) ($this->name ?? ''));
            if ($accountName !== '') {
                return $accountName;
            }

            $businessName = trim((string) ($this->vendorDetail?->vendor_name ?? ''));
            if ($businessName !== '') {
                return $businessName;
            }
        }

        return $this->isAdmin() ? 'Team member' : 'Practitioner';
    }

    /**
     * Public role label for profile and card surfaces.
     */
    public function getPublicRoleLabelAttribute(): string
    {
        if ($this->isAdmin()) {
            return 'We Offer Wellness team';
        }

        if ($this->isStarterPlan()) {
            return 'Wellness practitioner';
        }

        $practiceLabel = trim((string) data_get($this->settings?->settings_data, 'practice_label', ''));
        if ($practiceLabel !== '') {
            return $practiceLabel;
        }

        $businessName = trim((string) ($this->vendorDetail?->vendor_name ?? ''));
        if ($businessName !== '') {
            return $businessName;
        }

        return 'Wellness practitioner';
    }

    /**
     * Resolve a stored media path to a public URL.
     */
    protected function resolveMediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $assetHost = rtrim((string) (config('app.asset_url') ?: config('services.asset_host') ?: 'https://atease.weofferwellness.co.uk'), '/');

        return $assetHost.'/storage/'.ltrim($path, '/');
    }

    /**
     * Public URL for the profile picture.
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        return $this->resolveMediaUrl($this->profile_picture);
    }

    /**
     * Public URL for the cover image.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        $coverImage = $this->resolveMediaUrl($this->cover_image ?? null);

        return $coverImage ?: 'https://studio.weofferwellness.co.uk/storage/uploads/images/93131c74-12f2-4eaa-a2cb-747f6cc70fc4.jpg';
    }

    /**
     * Get the public practitioner profile URL.
     */
    public function getPractitionerProfileUrlAttribute(): string
    {
        $fullName = trim((string) ($this->public_display_name ?: ''));
        if ($fullName === '') {
            $fullName = $this->isAdmin() ? 'Team member' : 'Practitioner';
        }

        $slug = Str::slug($fullName) ?: ($this->isAdmin() ? 'team-member' : 'practitioner');

        if ($this->isAdmin()) {
            return url('/about/team/' . $slug);
        }

        $userKey = $this->getKey();
        if (! $userKey) {
            return url('/practioner/' . $slug);
        }

        $shortHash = substr(hash('sha256', 'user:' . $userKey), 0, 6);

        return url('/practioner/' . $slug . '-' . $shortHash);
    }

    /**
     * Get the vendor's name.
     */
    public function getVendorNameAttribute(): ?string
    {
        return $this->vendorDetail->vendor_name ?? null;
    }

    /**
     * Get user status.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'Deactivated';
        }

        $waitingListStatus = $this->waitingList->status ?? null;

        return match ($waitingListStatus) {
            'pending' => 'Pending Waiting List',
            'rejected' => 'Rejected Waiting List',
            default => 'Active',
        };
    }

    /**
     * Check the user's role.
     */
    public function getRoleAttribute(): string
    {
        // Determine a primary role label based on assigned roles with a fixed priority
        $names = $this->roles()->pluck('name')->map(fn ($n) => strtolower($n));
        if ($names->contains('admin')) return 'Admin';
        if ($names->contains('provider')) return 'Provider';
        if ($names->contains('journalist')) return 'Journalist';
        if ($names->contains('client') || $names->contains('user')) return 'Client';
        return 'Unknown';
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        $normalized = strtolower($roleName);

        if (in_array($normalized, ['user', 'client'], true)) {
            return $this->roles()
                ->whereIn(\DB::raw('LOWER(name)'), ['user', 'client'])
                ->exists();
        }

        return $this->roles()->whereRaw('LOWER(name) = ?', [$normalized])->exists();
    }

    public function sentMessages()
    {
        return $this->hasMany(UserMessage::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(UserMessage::class, 'receiver_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function countUserContent()
    {
        if ($this->hasRole('Provider')) {
            return Product::where('vendor_id', $this->vendorDetail->id)->count();
        } elseif ($this->hasRole('Journalist')) {
            return Article::where('user_id', $this->id)->count();
        }

        return 0; // Default if the user has neither role
    }

    public function getProductList()
    {
        $vendor = $this->vendorDetail;
        $user = auth()->user();

        if (!$vendor) {
            return redirect()->route('provider.dashboard')->withErrors('You are not associated with any vendor.');
        }

        return Product::where('vendor_id', $vendor->id)
            ->with(['category', 'variants', 'media', 'options.values', 'painPoints']);
    }

    /**
     * Get all user-related content (Products or Articles)
     */
    public function getUserContent()
    {
        if ($this->hasRole('Provider') || $this->is_vendor) {
            return $this->products;
        } elseif ($this->hasRole('Journalist')) {
            return $this->articles;
        }
        return collect();
    }

    /**
     * Send the email verification notification using the Brevo API.
     */
    public function sendEmailVerificationNotification(): void
    {
        $expires = (int) Config::get('auth.verification.expire', 60);
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes($expires),
            ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]
        );

        // Generate a 6-digit verification code, store hashed with timestamp
        $code = (string) random_int(100000, 999999);
        $this->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_code_sent_at' => Carbon::now(),
        ])->save();

        // Email content tries to avoid spam triggers: concise, code-first, minimal links
        $subject = 'Your We Offer Wellness verification code: ' . $code;
        MailService::send(
            $this->email,
            $subject,
            'emails.verify-email',
            [
                'user' => $this,
                'url' => $url,
                'code' => $code,
                'expires' => $expires,
            ]
        );
    }

    /**
     * Send the password reset notification using the custom Brevo template.
     */
    public function sendPasswordResetNotification($token)
    {
        $url = url(route('password.reset', ['token' => $token, 'email' => $this->email], false));
        MailService::send(
            $this->email,
            'Reset your We Offer Wellness password',
            'emails.reset-password',
            [
                'user' => $this,
                'url' => $url,
            ]
        );
    }

    public function userBio()
    {
        return $this->hasOne(UserBio::class, 'user_id');
    }

}
