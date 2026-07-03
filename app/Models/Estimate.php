<?php

namespace App\Models;

use App\Enums\EstimateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Estimate extends Model
{
    /** @use HasFactory<\Database\Factories\EstimateFactory> */
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'version',
        'status',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'currency',
        'created_by_user_id',
        'generated_at',
        'approved_at',
        'rejected_at',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EstimateStatus::class,
            'version' => 'integer',
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EstimateLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
