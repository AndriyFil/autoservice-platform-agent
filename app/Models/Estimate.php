<?php

namespace App\Models;

use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Estimates\Enums\EstimateStatus;
use Database\Factories\EstimateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Estimate extends Model
{
    /** @use HasFactory<EstimateFactory> */
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'version',
        'status',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'currency',
        'requires_customer_approval',
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
            'requires_customer_approval' => 'boolean',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<RepairOrder, $this> */
    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<EstimateLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(EstimateLine::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return MorphMany<Document, $this> */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /** @return MorphMany<Document, $this> */
    public function generatedEstimatePdfDocuments(): MorphMany
    {
        return $this->documents()
            ->where('type', DocumentType::EstimatePdf)
            ->where('status', DocumentStatus::Generated);
    }
}
