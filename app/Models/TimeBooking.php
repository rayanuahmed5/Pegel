<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $consultant_id
 * @property string $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property float $duration_hours
 * @property string $description
 * @property string|null $accounting_object
 * @property string|null $activity
 * @property string $status
 * @property string|null $rejection_reason
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read Consultant $consultant
 * @property-read User|null $approver
 */
class TimeBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'consultant_id',
        'date',
        'start_time',
        'end_time',
        'duration_hours',
        'description',
        'accounting_object',
        'activity',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_hours' => 'float',
            'date' => 'date:Y-m-d',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Consultant, $this>
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve this booking by a client project lead or manager.
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject this booking with a specific feedback reason.
     */
    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_at' => null,
            'approved_by' => $approver->id,
            'rejection_reason' => $reason,
        ]);
    }
}
