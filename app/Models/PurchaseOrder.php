<?php

namespace App\Models;

use App\Domain\Exceptions\BudgetExceededException;
use App\Domain\Exceptions\ConsultantNotAssignedException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $order_number
 * @property string $title
 * @property string|null $description
 * @property int $supplier_id
 * @property int $cost_centre_id
 * @property int|null $project_lead_id
 * @property float $ordered_budget_pd
 * @property float $hourly_rate_eur
 * @property string $start_date
 * @property string $end_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Supplier $supplier
 * @property-read CostCentre $costCentre
 * @property-read User|null $projectLead
 * @property-read Collection<int, Consultant> $consultants
 * @property-read Collection<int, TimeBooking> $timeBookings
 */
class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'title',
        'description',
        'supplier_id',
        'cost_centre_id',
        'project_lead_id',
        'ordered_budget_pd',
        'hourly_rate_eur',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordered_budget_pd' => 'float',
            'hourly_rate_eur' => 'float',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<CostCentre, $this>
     */
    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function projectLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_lead_id');
    }

    /**
     * @return BelongsToMany<Consultant, $this>
     */
    public function consultants(): BelongsToMany
    {
        return $this->belongsToMany(Consultant::class, 'purchase_order_consultant')
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
     * Total booked hours excluding rejected entries.
     */
    public function usedHours(): float
    {
        return (float) $this->timeBookings()
            ->where('status', '!=', 'rejected')
            ->sum('duration_hours');
    }

    /**
     * Total booked person-days (1 PD = 8 hours).
     */
    public function usedPD(): float
    {
        return round($this->usedHours() / 8.0, 2);
    }

    /**
     * Remaining free budget in hours.
     */
    public function remainingBudgetHours(): float
    {
        $orderedHours = $this->ordered_budget_pd * 8.0;

        return max(0.0, round($orderedHours - $this->usedHours(), 2));
    }

    /**
     * Remaining free budget in Person-Days (PD).
     */
    public function remainingBudgetPD(): float
    {
        return round($this->remainingBudgetHours() / 8.0, 2);
    }

    /**
     * Percentage of budget used.
     */
    public function budgetUsagePercent(): float
    {
        if ($this->ordered_budget_pd <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->usedPD() / $this->ordered_budget_pd) * 100, 1));
    }

    /**
     * Check if a consultant is assigned to this order.
     */
    public function isConsultantAssigned(Consultant $consultant): bool
    {
        return $this->consultants()->where('consultants.id', $consultant->id)->exists();
    }

    /**
     * Core domain rule: book hours on this order.
     * Enforces that the requested hours do not exceed remaining free budget.
     *
     * @throws BudgetExceededException
     * @throws ConsultantNotAssignedException
     */
    public function book(
        Consultant $consultant,
        float $hours,
        string $date,
        string $description,
        ?string $accountingObject = null,
        ?string $activity = null,
        ?string $startTime = null,
        ?string $endTime = null,
        string $status = 'pending'
    ): TimeBooking {
        if (! $this->isConsultantAssigned($consultant)) {
            throw new ConsultantNotAssignedException(
                "Consultant {$consultant->name} is not assigned to Purchase Order {$this->order_number}."
            );
        }

        $freeHours = $this->remainingBudgetHours();

        if ($hours > $freeHours) {
            $freePD = $this->remainingBudgetPD();
            throw new BudgetExceededException(
                "Booking rejected: only {$freePD} PD ({$freeHours} h) left on order {$this->order_number}."
            );
        }

        return $this->timeBookings()->create([
            'consultant_id' => $consultant->id,
            'date' => $date,
            'duration_hours' => $hours,
            'description' => $description,
            'accounting_object' => $accountingObject,
            'activity' => $activity,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
        ]);
    }
}
