<?php

namespace App\Http\Controllers;

use App\Models\LicenseToken;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    /**
     * Obtiene los planes de suscripción disponibles para el landing.
     * Endpoint: GET /api/public/plans
     */
    public function getPlans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'duration_days' => $plan->duration_days,
                    'price_cop' => (float) $plan->price_cop,
                    'price_usd' => (float) $plan->price_usd,
                    'discount_percentage' => (float) ($plan->discount_percentage ?? 0),
                    'original_price_cop' => (float) ($plan->original_price_cop ?? $plan->price_cop),
                    'original_price_usd' => (float) ($plan->original_price_usd ?? $plan->price_usd),
                    'is_active' => $plan->is_active,
                    'display_order' => $plan->display_order,
                    'description' => $plan->description,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $plans,
            'meta' => [
                'currency' => [
                    'default' => 'cop',
                    'supported' => ['cop', 'usd'],
                ],
                'last_updated' => now()->toDateString(),
            ],
        ]);
    }

    /**
     * Verifica si una licencia es válida (para que Ausentra la use).
     * Endpoint: GET /api/public/verify-license/{token}
     */
    public function verifyLicense(string $token): JsonResponse
    {
        $license = LicenseToken::findByToken($token);

        if (! $license) {
            return response()->json([
                'valid' => false,
                'status' => 'not_found',
                'message' => 'Licencia no encontrada',
            ], 404);
        }

        return response()->json($license->getVerificationResponse());
    }

    /**
     * Obtiene el estado detallado de una licencia.
     * Endpoint: GET /api/public/license/{token}
     */
    public function getLicenseStatus(string $token): JsonResponse
    {
        $license = LicenseToken::findByToken($token);

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'Licencia no encontrada',
            ], 404);
        }

        $license->recordConsultation();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $license->id,
                'token' => $license->token,
                'company_name' => $license->company_name,
                'company_email' => $license->company_email,
                'company_nit' => $license->company_nit,
                'plan' => [
                    'id' => $license->plan?->id,
                    'name' => $license->plan?->name,
                    'duration_days' => $license->plan?->duration_days,
                ],
                'starts_at' => $license->starts_at?->toIso8601String(),
                'expires_at' => $license->expires_at?->toIso8601String(),
                'is_active' => $license->is_active,
                'status' => $license->status,
                'days_remaining' => $license->daysRemaining(),
                'is_valid' => $license->isValid(),
                'is_expiring_soon' => $license->isExpiringSoon(),
                'payment' => [
                    'method' => $license->payment_method,
                    'amount' => (float) $license->amount_paid,
                    'currency' => $license->currency,
                    'transaction_id' => $license->transaction_id,
                    'formatted_amount' => $license->getFormattedAmount(),
                ],
                'statistics' => [
                    'consultation_count' => $license->consultation_count,
                    'last_consulted_at' => $license->last_consulted_at?->toIso8601String(),
                ],
                'created_at' => $license->created_at?->toIso8601String(),
                'checked_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Obtiene estadísticas generales de licencias (para superadmin).
     * Endpoint: GET /api/public/admin/licenses/stats
     */
    public function getLicenseStats(): JsonResponse
    {
        $total = LicenseToken::count();
        $active = LicenseToken::active()->count();
        $expired = LicenseToken::expired()->count();
        $expiringSoon = LicenseToken::expiringSoon(7)->count();

        $revenueCOP = LicenseToken::where('currency', 'COP')->sum('amount_paid');
        $revenueUSD = LicenseToken::where('currency', 'USD')->sum('amount_paid');

        return response()->json([
            'success' => true,
            'data' => [
                'total_licenses' => $total,
                'active_licenses' => $active,
                'expired_licenses' => $expired,
                'expiring_soon_licenses' => $expiringSoon,
                'revenue' => [
                    'cop' => (float) $revenueCOP,
                    'usd' => (float) $revenueUSD,
                    'formatted_cop' => '$'.number_format($revenueCOP, 0, ',', '.'),
                    'formatted_usd' => '$'.number_format($revenueUSD, 2),
                ],
                'by_plan' => LicenseToken::with('plan')
                    ->get()
                    ->groupBy('plan_id')
                    ->map(function ($licenses, $planId) {
                        $plan = $licenses->first()->plan;

                        return [
                            'plan_id' => $planId,
                            'plan_name' => $plan?->name,
                            'count' => $licenses->count(),
                            'active' => $licenses->where('status', 'active')->count(),
                        ];
                    })->values(),
            ],
        ]);
    }

    /**
     * Lista todas las licencias (para superadmin).
     * Endpoint: GET /api/public/admin/licenses
     */
    public function listLicenses(Request $request): JsonResponse
    {
        $query = LicenseToken::with('plan');

        // Filtros
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', '%'.$request->search.'%')
                    ->orWhere('company_email', 'like', '%'.$request->search.'%')
                    ->orWhere('company_nit', 'like', '%'.$request->search.'%')
                    ->orWhere('token', 'like', '%'.$request->search.'%');
            });
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $licenses = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $licenses->map(function ($license) {
                return [
                    'id' => $license->id,
                    'token' => $license->token,
                    'company_name' => $license->company_name,
                    'company_email' => $license->company_email,
                    'company_nit' => $license->company_nit,
                    'plan' => [
                        'id' => $license->plan?->id,
                        'name' => $license->plan?->name,
                    ],
                    'starts_at' => $license->starts_at?->toIso8601String(),
                    'expires_at' => $license->expires_at?->toIso8601String(),
                    'is_active' => $license->is_active,
                    'status' => $license->status,
                    'days_remaining' => $license->daysRemaining(),
                    'is_valid' => $license->isValid(),
                    'is_expiring_soon' => $license->isExpiringSoon(),
                    'payment' => [
                        'method' => $license->payment_method,
                        'amount' => (float) $license->amount_paid,
                        'currency' => $license->currency,
                        'transaction_id' => $license->transaction_id,
                        'formatted_amount' => $license->getFormattedAmount(),
                    ],
                    'created_at' => $license->created_at?->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $licenses->currentPage(),
                'last_page' => $licenses->lastPage(),
                'per_page' => $licenses->perPage(),
                'total' => $licenses->total(),
            ],
        ]);
    }

    /**
     * Crea una nueva licencia (para superadmin).
     * Endpoint: POST /api/public/admin/licenses
     */
    public function createLicense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_nit' => 'nullable|string|max:50',
            'plan_id' => 'required|exists:subscription_plans,id',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
            'payment_method' => 'nullable|string|max:50',
            'amount_paid' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['token'] = LicenseToken::generateToken();
        $validated['is_active'] = true;
        $validated['status'] = 'active';

        $license = LicenseToken::create($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $license->id,
                'token' => $license->token,
                'company_name' => $license->company_name,
                'expires_at' => $license->expires_at?->toIso8601String(),
            ],
            'message' => 'Licencia creada exitosamente',
        ], 201);
    }

    /**
     * Actualiza una licencia existente (para superadmin).
     * Endpoint: PUT /api/public/admin/licenses/{id}
     */
    public function updateLicense(Request $request, int $id): JsonResponse
    {
        $license = LicenseToken::find($id);

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'Licencia no encontrada',
            ], 404);
        }

        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_nit' => 'nullable|string|max:50',
            'plan_id' => 'sometimes|exists:subscription_plans,id',
            'starts_at' => 'sometimes|date',
            'expires_at' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,expired,suspended,cancelled',
            'payment_method' => 'nullable|string|max:50',
            'amount_paid' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $license->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $license->id,
                'token' => $license->token,
                'company_name' => $license->company_name,
                'status' => $license->status,
            ],
            'message' => 'Licencia actualizada exitosamente',
        ]);
    }

    /**
     * Elimina una licencia (para superadmin).
     * Endpoint: DELETE /api/public/admin/licenses/{id}
     */
    public function deleteLicense(int $id): JsonResponse
    {
        $license = LicenseToken::find($id);

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'Licencia no encontrada',
            ], 404);
        }

        $license->delete();

        return response()->json([
            'success' => true,
            'message' => 'Licencia eliminada exitosamente',
        ]);
    }

    /**
     * Renueva una licencia (para superadmin).
     * Endpoint: POST /api/public/admin/licenses/{id}/renew
     */
    public function renewLicense(Request $request, int $id): JsonResponse
    {
        $license = LicenseToken::find($id);

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'Licencia no encontrada',
            ], 404);
        }

        $validated = $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $license->renew($validated['days']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $license->id,
                'expires_at' => $license->expires_at?->toIso8601String(),
                'days_remaining' => $license->daysRemaining(),
            ],
            'message' => 'Licencia renovada exitosamente',
        ]);
    }

    /**
     * Activa/desactiva una licencia (para superadmin).
     * Endpoint: POST /api/public/admin/licenses/{id}/toggle
     */
    public function toggleLicense(int $id): JsonResponse
    {
        $license = LicenseToken::find($id);

        if (! $license) {
            return response()->json([
                'success' => false,
                'message' => 'Licencia no encontrada',
            ], 404);
        }

        if ($license->is_active) {
            $license->deactivate();
        } else {
            $license->activate();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $license->id,
                'is_active' => $license->is_active,
                'status' => $license->status,
            ],
            'message' => $license->is_active ? 'Licencia activada' : 'Licencia suspendida',
        ]);
    }

    /**
     * Crea una sesión de checkout para pagos.
     * Endpoint: POST /api/public/create-checkout-session
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'required|in:stripe,mercadopago',
            'currency' => 'nullable|in:cop,usd',
            'company_data' => 'nullable|array',
            'company_data.company_name' => 'required_with:company_data|string|max:255',
            'company_data.company_email' => 'required_with:company_data|email|max:255',
            'company_data.company_nit' => 'required_with:company_data|string|max:50',
            'company_data.contact_name' => 'required_with:company_data|string|max:255',
            'company_data.contact_phone' => 'nullable|string|max:20',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $currency = $validated['currency'] ?? 'cop';

        // Guardar company_data en sesión para usarlo después del pago
        $companyData = $validated['company_data'] ?? [];

        // Por ahora generamos un mock de URL de pago
        // En producción, esto integraría con Stripe/MercadoPago
        $amount = $currency === 'cop' ? $plan->price_cop : $plan->price_usd;

        // Simular checkout - en producción esto llamarías a Stripe/MercadoPago
        $checkoutData = [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $validated['payment_method'],
            'company_data' => $companyData,
            'created_at' => now()->toIso8601String(),
        ];

        // Guardar en sesión para recuperar después del pago
        session(['pending_checkout' => $checkoutData]);

        // Generar URL de retorno (en producción sería la URL de Stripe/MP)
        $returnUrl = url('/api/public/payment-callback?success=true&session_id=mock_'.uniqid());

        return response()->json([
            'url' => $returnUrl,
            'checkout_id' => 'mock_'.uniqid(),
            'plan_name' => $plan->name,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    /**
     * Callback después del pago (webhook o redirect).
     * Endpoint: GET /api/public/payment-callback
     */
    public function paymentCallback(Request $request): JsonResponse
    {
        $checkoutData = session('pending_checkout');

        if (! $checkoutData) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró información de pago',
            ], 400);
        }

        $companyData = $checkoutData['company_data'] ?? [];

        // Buscar o crear la empresa (esto depende de tu lógica de negocio)
        // Por ahora creamos la licencia

        $plan = SubscriptionPlan::findOrFail($checkoutData['plan_id']);

        // Calcular fechas
        $startsAt = now();
        $expiresAt = $startsAt->addDays($plan->duration_days);

        // Crear la licencia
        $license = LicenseToken::create([
            'token' => LicenseToken::generateToken(),
            'company_name' => $companyData['company_name'] ?? 'Empresa sin nombre',
            'company_email' => $companyData['company_email'] ?? null,
            'company_nit' => $companyData['company_nit'] ?? null,
            'plan_id' => $plan->id,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'status' => 'active',
            'payment_method' => $checkoutData['payment_method'],
            'amount_paid' => $checkoutData['amount'],
            'currency' => $checkoutData['currency'],
            'transaction_id' => $request->get('session_id', 'mock_transaction'),
            'notes' => 'Licencia creada automáticamente después del pago',
        ]);

        // Limpiar sesión
        session()->forget('pending_checkout');

        return response()->json([
            'success' => true,
            'message' => 'Pago completado exitosamente',
            'data' => [
                'license_id' => $license->id,
                'token' => $license->token,
                'company_name' => $license->company_name,
                'expires_at' => $license->expires_at->toIso8601String(),
            ],
        ]);
    }
}
