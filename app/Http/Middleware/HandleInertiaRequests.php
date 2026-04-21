<?php

namespace App\Http\Middleware;

use App\Managers\TenantManager;
use App\Models\CompanySettings;
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
        $company = [
            'name' => config('app.name'),
            'logo' => null,
            'logo_url' => asset('logo.png'),
        ];

        if ($request->user()) {
            $tenantId = app(TenantManager::class)->getTenantId();

            if ($tenantId) {
                $settings = CompanySettings::query()
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($settings) {
                    $company = [
                        'name' => $settings->company_name ?: config('app.name'),
                        'logo' => $settings->company_logo,
                        'logo_url' => $settings->company_logo
                            ? asset('storage/'.$settings->company_logo)
                            : asset('logo.png'),
                    ];
                }
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'company' => $company,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
