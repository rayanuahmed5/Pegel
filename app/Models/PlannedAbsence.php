<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $consultant_id
 * @property string $type
 * @property string $start_date
 * @property string $end_date
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Consultant $consultant
 */
class PlannedAbsence extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'type',
        'start_date',
        'end_date',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Consultant, $this>
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function isVacation(): bool
    {
        return $this->type === 'vacation';
    }

    public function isShutdown(): bool
    {
        return $this->type === 'shutdown';
    }

    public function isHoliday(): bool
    {
        return $this->type === 'public_holiday';
    }
}
