<?php

namespace App\Models;

use App\Enums\RepairOrderLineType;
use Database\Factories\RepairOrderLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairOrderLine extends Model
{
    /** @use HasFactory<RepairOrderLineFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'repair_order_id',
        'type',
        'description',
        'quantity',
        'unit_price_cents',
        'tax_rate',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RepairOrderLineType::class,
            'quantity' => 'decimal:2',
            'unit_price_cents' => 'integer',
            'tax_rate' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<RepairOrder, $this> */
    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class);
    }

    /**
     * Attributes for an EstimateLine built from this repair order line.
     *
     * @return array<string, mixed>
     */
    public function toEstimateLineAttributes(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'tax_rate' => $this->tax_rate,
            'subtotal_cents' => $this->subtotalCents(),
            'tax_cents' => $this->taxCents(),
            'total_cents' => $this->totalCents(),
            'sort_order' => $this->sort_order,
        ];
    }

    public function subtotalCents(): int
    {
        $rawCents = $this->unit_price_cents * $this->decimalToMinorUnits((string) $this->quantity, 2);
        $subtotal = $this->divideAndRoundHalfUp($rawCents, 100);

        return $this->type === RepairOrderLineType::Discount ? -$subtotal : $subtotal;
    }

    public function taxCents(): int
    {
        $rawTaxCents = $this->subtotalCents() * $this->decimalToMinorUnits((string) $this->tax_rate, 2);

        return $this->divideAndRoundHalfUp($rawTaxCents, 10000);
    }

    public function totalCents(): int
    {
        return $this->subtotalCents() + $this->taxCents();
    }

    private function decimalToMinorUnits(string $value, int $scale): int
    {
        $normalized = trim($value);
        $isNegative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '+-');

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $whole = preg_replace('/\D+/', '', $whole) ?: '0';
        $fraction = substr(str_pad(preg_replace('/\D+/', '', $fraction) ?: '', $scale, '0'), 0, $scale);
        $minorUnits = ((int) $whole * (10 ** $scale)) + (int) $fraction;

        return $isNegative ? -$minorUnits : $minorUnits;
    }

    private function divideAndRoundHalfUp(int $value, int $divisor): int
    {
        $sign = $value < 0 ? -1 : 1;
        $absoluteValue = abs($value);

        return $sign * intdiv($absoluteValue + intdiv($divisor, 2), $divisor);
    }
}
