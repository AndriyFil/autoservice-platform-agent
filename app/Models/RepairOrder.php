<?php

namespace App\Models;

use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use Database\Factories\RepairOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairOrder extends Model
{
    /** @use HasFactory<RepairOrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'customer_id',
        'vehicle_id',
        'booking_request_id',
        'status',
        'requires_estimate_approval',
        'notes',
        'created_by_user_id',
        'problem_description',
        'opened_at',
        'closed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RepairOrderStatus::class,
            'requires_estimate_approval' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workshop, $this> */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<BookingRequest, $this> */
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<RepairOrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(RepairOrderLine::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<Estimate, $this> */
    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class)->orderByDesc('version');
    }

    public function subtotalCents(): int
    {
        return $this->lines->sum(fn (RepairOrderLine $line): int => $line->subtotalCents());
    }

    public function taxCents(): int
    {
        return $this->lines->sum(fn (RepairOrderLine $line): int => $line->taxCents());
    }

    public function totalCents(): int
    {
        return $this->lines->sum(fn (RepairOrderLine $line): int => $line->totalCents());
    }
}
