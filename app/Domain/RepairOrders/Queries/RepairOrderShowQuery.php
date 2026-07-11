<?php

namespace App\Domain\RepairOrders\Queries;

use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\RepairOrders\Enums\RepairOrderLineType;
use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\WorkshopUser;

class RepairOrderShowQuery
{
    /**
     * @return array{
     *     id: int,
     *     status: array{value: string, label: string},
     *     problemDescription: string|null,
     *     notes: string|null,
     *     requiresEstimateApproval: bool,
     *     canUpdateEstimateApprovalRequirement: bool,
     *     openedAt: string,
     *     closedAt: string|null,
     *     lines: array<int, array{id: int, type: array{value: string, label: string}, description: string, quantity: string, unitPriceCents: int, taxRate: string, sortOrder: int, subtotalCents: int, taxCents: int, totalCents: int}>,
     *     workingTotals: array{subtotalCents: int, taxCents: int, totalCents: int},
     *     estimateTotals: array{subtotalCents: int, taxCents: int, totalCents: int},
     *     estimates: array<int, array{id: int, version: int, status: array{value: string, label: string}, subtotalCents: int, taxCents: int, totalCents: int, currency: string, generatedAt: string|null, document: array{id: int, filename: string, downloadUrl: string}|null}>,
     *     documents: array<int, array{id: int, filename: string, type: array{value: string, label: string}, status: array{value: string, label: string}, generatedAt: string|null, downloadUrl: string|null}>,
     *     availableLineTypes: array<int, array{value: string, label: string}>,
     *     statusActions: array{canGenerateEstimate: bool, hasEstimate: bool},
     *     availableStatusTransitions: array<int, array{value: string, label: string}>,
     *     customer: array{id: int, name: string, phone: string}|null,
     *     vehicle: array{id: int, brand: string|null, model: string|null, licensePlate: string|null}|null,
     *     bookingRequest: array{id: int, status: array{value: string, label: string}, problemDescription: string, originalMessage: string|null, preferredDate: string|null, createdAt: string}|null
     * }
     */
    public function handle(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder): array
    {
        $repairOrder = RepairOrder::query()
            ->with([
                'customer',
                'vehicle',
                'bookingRequest',
                'lines',
                'estimates.documents',
                'estimates.generatedEstimatePdfDocuments',
            ])
            ->whereKey($repairOrder->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        return [
            'id' => $repairOrder->id,
            'status' => [
                'value' => $repairOrder->status->value,
                'label' => $repairOrder->status->label(),
            ],
            'problemDescription' => $repairOrder->problem_description,
            'notes' => $repairOrder->notes,
            'requiresEstimateApproval' => $repairOrder->requires_estimate_approval,
            'canUpdateEstimateApprovalRequirement' => ! $repairOrder->status->isFinal(),
            'openedAt' => $repairOrder->opened_at->toISOString(),
            'closedAt' => $repairOrder->closed_at?->toISOString(),
            'lines' => $repairOrder->lines
                ->map(fn (RepairOrderLine $line): array => [
                    'id' => $line->id,
                    'type' => [
                        'value' => $line->type->value,
                        'label' => $line->type->label(),
                    ],
                    'description' => $line->description,
                    'quantity' => (string) $line->quantity,
                    'unitPriceCents' => $line->unit_price_cents,
                    'taxRate' => (string) $line->tax_rate,
                    'sortOrder' => $line->sort_order,
                    'subtotalCents' => $line->subtotalCents(),
                    'taxCents' => $line->taxCents(),
                    'totalCents' => $line->totalCents(),
                ])
                ->all(),
            'workingTotals' => [
                'subtotalCents' => $repairOrder->subtotalCents(),
                'taxCents' => $repairOrder->taxCents(),
                'totalCents' => $repairOrder->totalCents(),
            ],
            'estimateTotals' => [
                'subtotalCents' => $repairOrder->subtotalCents(),
                'taxCents' => $repairOrder->taxCents(),
                'totalCents' => $repairOrder->totalCents(),
            ],
            'estimates' => $repairOrder->estimates
                ->map(function (Estimate $estimate): array {
                    $document = $estimate->generatedEstimatePdfDocuments
                        ->sortByDesc('id')
                        ->first();

                    return [
                        'id' => $estimate->id,
                        'version' => $estimate->version,
                        'status' => [
                            'value' => $estimate->status->value,
                            'label' => $estimate->status->label(),
                        ],
                        'subtotalCents' => $estimate->subtotal_cents,
                        'taxCents' => $estimate->tax_cents,
                        'totalCents' => $estimate->total_cents,
                        'currency' => $estimate->currency,
                        'generatedAt' => $estimate->generated_at?->toISOString(),
                        'document' => $document
                            ? [
                                'id' => $document->id,
                                'filename' => $document->filename,
                                'downloadUrl' => route('dashboard.documents.download', $document),
                            ]
                            : null,
                    ];
                })
                ->values()
                ->all(),
            'documents' => $repairOrder->estimates
                ->flatMap(fn (Estimate $estimate) => $estimate->documents)
                ->sortByDesc(fn (Document $document): int => $document->id)
                ->map(fn (Document $document): array => [
                    'id' => $document->id,
                    'filename' => $document->filename,
                    'type' => [
                        'value' => $document->type->value,
                        'label' => $document->type->label(),
                    ],
                    'status' => [
                        'value' => $document->status->value,
                        'label' => $document->status->label(),
                    ],
                    'generatedAt' => $document->generated_at?->toISOString(),
                    'downloadUrl' => $document->status === DocumentStatus::Generated
                        ? route('dashboard.documents.download', $document)
                        : null,
                ])
                ->values()
                ->all(),
            'availableLineTypes' => array_map(
                fn (RepairOrderLineType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ],
                RepairOrderLineType::cases(),
            ),
            'statusActions' => $this->statusActions($repairOrder),
            'availableStatusTransitions' => $this->availableStatusTransitions($repairOrder->status),
            'customer' => $repairOrder->customer
                ? [
                    'id' => $repairOrder->customer->id,
                    'name' => $repairOrder->customer->name,
                    'phone' => $repairOrder->customer->phone,
                ]
                : null,
            'vehicle' => $repairOrder->vehicle
                ? [
                    'id' => $repairOrder->vehicle->id,
                    'brand' => $repairOrder->vehicle->brand,
                    'model' => $repairOrder->vehicle->model,
                    'licensePlate' => $repairOrder->vehicle->license_plate,
                ]
                : null,
            'bookingRequest' => $repairOrder->bookingRequest
                ? [
                    'id' => $repairOrder->bookingRequest->id,
                    'status' => [
                        'value' => $repairOrder->bookingRequest->status->value,
                        'label' => $repairOrder->bookingRequest->status->label(),
                    ],
                    'problemDescription' => $repairOrder->bookingRequest->problem_description,
                    'originalMessage' => $repairOrder->bookingRequest->original_message,
                    'preferredDate' => $repairOrder->bookingRequest->preferred_date?->toDateString(),
                    'createdAt' => $repairOrder->bookingRequest->created_at->toISOString(),
                ]
                : null,
        ];
    }

    /**
     * @return array{canGenerateEstimate: bool, hasEstimate: bool}
     */
    private function statusActions(RepairOrder $repairOrder): array
    {
        $hasEstimate = $repairOrder->estimates->isNotEmpty();

        return [
            'canGenerateEstimate' => in_array($repairOrder->status, [RepairOrderStatus::Draft, RepairOrderStatus::InProgress], true)
                && $repairOrder->lines->isNotEmpty(),
            'hasEstimate' => $hasEstimate,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableStatusTransitions(RepairOrderStatus $status): array
    {
        return collect($status->manualTransitions())
            ->map(fn (RepairOrderStatus $targetStatus): array => [
                'value' => $targetStatus->value,
                'label' => __("repair_orders.status_actions.{$targetStatus->value}"),
            ])
            ->values()
            ->all();
    }
}
