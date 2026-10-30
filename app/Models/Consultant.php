<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $supplier_id
 * @property string $name
 * @property string $email
 * @property float $daily_rate_eur
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Supplier $supplier
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read Collection<int, TimeBooking> $timeBookings
 * @property-read Collection<int, PlannedAbsence> $plannedAbsences
 */
class Consultant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'supplier_id',
        'name',
        'email',
        'daily_rate_eur',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_rate_eur' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsToMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseOrder::class, 'purchase_order_consultant')
            ->withTimestamps();
    }

    /**
     * @return HasMany<TimeBooking, $this>
     */
    public function timeBookings(): HasMany
    {
        return $this->hasMany(TimeBooking::class);
    }

    /**
     * @return HasMany<PlannedAbsence, $this>
     */
    public function plannedAbsences(): HasMany
    {
        return $this->hasMany(PlannedAbsence::class);
    }
}
