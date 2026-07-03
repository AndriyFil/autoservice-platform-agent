<?php

namespace App\Models;

use App\Enums\RepairOrderLineType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateLine extends Model
{
    /** @use HasFactory<\Database\Factories\EstimateLineFactory> */
    use HasFactory;

    protected $fillable = [
        'estimate_id',
        'type',
        'description',
        'quantity',
        'unit_price_cents',
        'tax_rate',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => RepairOrderLineType::class,
            'quantity' => 'decimal:2',
            'unit_price_cents' => 'integer',
            'tax_rate' => 'decimal:2',
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }
}
