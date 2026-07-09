<?php

namespace App\Http\Middleware;

use App\Models\WorkshopUser;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => fn (): array => [
                'user' => $request->user(),
                'activeWorkshopUser' => $this->activeWorkshopUser($request),
            ],
            'flash' => [
                'status' => $request->session()->get('status'),
            ],
            'translations' => [
                'repair_orders' => __('repair_orders'),
                'estimates' => __('estimates'),
            ],
        ]);
    }

    /**
     * @return array{id: int, role: string, workshopId: int}|null
     */
    private function activeWorkshopUser(Request $request): ?array
    {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');

        if (! $activeWorkshopUser instanceof WorkshopUser) {
            return null;
        }

        return [
            'id' => $activeWorkshopUser->id,
            'role' => $activeWorkshopUser->role->value,
            'workshopId' => $activeWorkshopUser->workshop_id,
        ];
    }
}
