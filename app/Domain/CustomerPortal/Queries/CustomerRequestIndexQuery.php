<?php

namespace App\Domain\CustomerPortal\Queries;

use App\Models\BookingRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRequestIndexQuery
{
    /** @return array{recent: array<int, array<string, mixed>>, hasMore: bool, requests: LengthAwarePaginator} */
    public function handle(string $verifiedPhone): array
    {
        $base = BookingRequest::query()
            ->where('customer_phone_normalized', $verifiedPhone)
            ->with('workshop:id,name')
            ->latest();

        $recentRows = (clone $base)->limit(11)->get();
        $requests = (clone $base)->paginate(20)
            ->through(fn (BookingRequest $request): array => $this->map($request));

        return [
            'recent' => $recentRows->take(10)
                ->map(fn (BookingRequest $request): array => $this->map($request))
                ->values()->all(),
            'hasMore' => $recentRows->count() > 10,
            'requests' => $requests,
        ];
    }

    /** @return array<string, mixed> */
    private function map(BookingRequest $request): array
    {
        $title = trim((string) ($request->problem_description ?: $request->original_message));

        return [
            'id' => $request->id,
            'title' => $title !== '' ? $title : 'Service request',
            'status' => ['value' => $request->status->value, 'label' => $request->status->label()],
            'workshopName' => $request->workshop->name,
            'submittedAt' => $request->created_at->toIso8601String(),
            'updatedAt' => $request->updated_at->toIso8601String(),
        ];
    }
}
