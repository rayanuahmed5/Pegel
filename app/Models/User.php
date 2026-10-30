<?php

namespace App\Models;

use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string $email
 * @property string $role
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 * @property-read Consultant|null $consultant
 * @property-read Collection<int, PurchaseOrder> $managedOrders
 */
#[Fillable(['name', 'username', 'email', 'password', 'current_team_id', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable;

    public function isConsultant(): bool
    {
        return $this->role === 'consultant';
    }

    public function isProjectLead(): bool
    {
        return $this->role === 'project_lead';
    }

    public function isPurchasing(): bool
    {
        return $this->role === 'purchasing';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * @return HasOne<Consultant, $this>
     */
    public function consultant(): HasOne
    {
        return $this->hasOne(Consultant::class);
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function managedOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'project_lead_id');
    }

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
}
