<?php

namespace App\Models;

use App\Domain\Shared\ValueObjects\Phone;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'name',
        'phone',
        'phone_normalized',
        'normalized_phone',
    ];

    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            if (! $customer->isDirty('phone') && filled($customer->phone_normalized)) {
                return;
            }

            $phone = (string) $customer->phone;
            $phoneValue = new Phone($phone);

            $customer->phone_normalized = $phoneValue->normalize();

            if (! filled($customer->normalized_phone)) {
                $customer->normalized_phone = $phoneValue->normalizeLegacyDigits();
            }
        });
    }

    /** @return BelongsTo<Workshop, $this> */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /** @return HasMany<Vehicle, $this> */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /** @return HasMany<BookingRequest, $this> */
    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    /** @return HasMany<RepairOrder, $this> */
    public function repairOrders(): HasMany
    {
        return $this->hasMany(RepairOrder::class);
    }
}
