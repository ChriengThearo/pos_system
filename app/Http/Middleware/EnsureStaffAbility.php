<?php

namespace App\Http\Middleware;

use App\Support\StaffAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $requestedAbilities = $this->parseAbilities($ability);

        if (! StaffAuth::check()) {
            return redirect()
                ->route('staff.login')
                ->with('error', 'Please log in first.');
        }

        if (! $this->hasAnyAbility($requestedAbilities)) {
            if ($request->expectsJson()) {
                abort(403, 'Your role cannot access this resource.');
            }

            $readableAbility = $this->readableAbilities($requestedAbilities);
            $fallbackRoute = $this->fallbackRouteName();

            if ($fallbackRoute === null) {
                abort(403, 'Your role has no accessible modules.');
            }

            return redirect()
                ->route($fallbackRoute)
                ->with('error', 'You have no permission to '.trim($readableAbility).'.');
        }

        return $next($request);
    }

    /**
     * @return array<int, string>
     */
    private function parseAbilities(string $ability): array
    {
        $parts = preg_split('/[|,]/', $ability) ?: [];
        $abilities = [];

        foreach ($parts as $part) {
            $candidate = trim((string) $part);
            if ($candidate !== '') {
                $abilities[] = $candidate;
            }
        }

        if ($abilities === []) {
            $candidate = trim($ability);

            return $candidate !== '' ? [$candidate] : [];
        }

        return array_values(array_unique($abilities));
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function hasAnyAbility(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if (StaffAuth::can($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function readableAbilities(array $abilities): string
    {
        $readable = array_values(array_filter(array_map(
            static fn (string $item): string => trim(str_replace(['.', '_'], ' ', $item)),
            $abilities
        )));

        if ($readable === []) {
            return 'this resource';
        }

        if (count($readable) === 1) {
            return $readable[0];
        }

        $last = array_pop($readable);

        return implode(', ', $readable).' or '.$last;
    }

    private function fallbackRouteName(): ?string
    {
        $routes = [
            'dashboard.manage' => 'admin.dashboard',
            'dashboard.read' => 'store.home',
            'orders.read' => 'store.orders',
            'shop.read' => 'store.catalog',
            'checkout.process' => 'store.cart',
            'invoices.read' => 'invoices.index',
            'purchases.read' => 'purchases.index',
            'client-depts.read' => 'client-depts.index',
            'clients.read' => 'clients.index',
            'currencies.read' => 'currencies.index',
            'products.read' => 'products.index',
            'stock-status.read' => 'products.status',
            'future-stock.read' => 'products.status.future',
            'employees.read' => 'employees.index',
            'jobs.read' => 'jobs.index',
            'users.read' => 'admin.rbac.users.index',
            'roles.read' => 'admin.rbac.roles.index',
            'permissions.read' => 'admin.rbac.permissions.index',
            'system.audit' => 'store.deep-check',
        ];

        foreach ($routes as $ability => $routeName) {
            if (StaffAuth::can($ability)) {
                return $routeName;
            }
        }

        return null;
    }
}
