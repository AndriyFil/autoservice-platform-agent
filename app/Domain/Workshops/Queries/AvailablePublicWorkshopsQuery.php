<?php

namespace App\Domain\Workshops\Queries;

use App\Models\Workshop;
use Illuminate\Database\Eloquent\Builder;

class AvailablePublicWorkshopsQuery
{
    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function options(): array
    {
        return $this->query()
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Workshop $workshop): array => [
                'id' => $workshop->id,
                'name' => $workshop->name,
            ])
            ->all();
    }

    public function resolve(int $workshopId): Workshop
    {
        return $this->query()->findOrFail($workshopId);
    }

    /** @return Builder<Workshop> */
    private function query(): Builder
    {
        return Workshop::query();
    }
}
