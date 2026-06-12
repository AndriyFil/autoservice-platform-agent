<?php

namespace App\Http\Middleware;

use App\Support\ActiveWorkshopMembershipResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveWorkshop
{
    public function __construct(
        private readonly ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $activeWorkshopUser = $this->activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

        $request->attributes->set('activeWorkshopUser', $activeWorkshopUser);

        return $next($request);
    }
}
