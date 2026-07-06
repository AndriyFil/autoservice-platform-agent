<?php

namespace App\Models;

use App\Enums\BookingRequestStatus;
use App\Support\Phone;
use Database\Factories\BookingRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingRequest extends Model
{
    /** @use HasFactory<BookingRequestFactory> */
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
        'created_by_user_id',
        'customer_name',
        'customer_phone',
        'customer_phone_normalized',
        'problem_description',
        'original_message',
        'preferred_date',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (BookingRequest $bookingRequest): void {
            if (! $bookingRequest->isDirty('customer_phone') && filled($bookingRequest->customer_phone_normalized)) {
                return;
            }

            $bookingRequest->customer_phone_normalized = (new Phone((string) $bookingRequest->customer_phone))
                ->normalize();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'status' => BookingRequestStatus::class,
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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasOne<RepairOrder, $this> */
    public function repairOrder(): HasOne
    {
        return $this->hasOne(RepairOrder::class);
    }
}
