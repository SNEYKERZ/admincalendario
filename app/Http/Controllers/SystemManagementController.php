<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionSettings;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Response;

class SystemManagementController extends Controller
{
    /**
     * Muestra la página de gestión del sistema (Inertia).
     */
    public function index(): Response
    {
        return inertia('SystemManagement');
    }

    /**
     * Retorna los datos JSON para la página de gestión del sistema.
     */
    public function getData(): \Illuminate\Http\JsonResponse
    {
        $settings = SubscriptionSettings::getSettings();
        $plans = SubscriptionPlan::orderBy('display_order')->get();

        // Get all admins with their subscription status (excluding superadmin)
        $admins = User::regularAdmins()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->with(['subscription' => function ($query) {
                $query->where('is_active', true)
                    ->where('expires_at', '>', now());
            }])
            ->get()
            ->map(function ($admin) {
                $subscription = $admin->subscription->first();
                $admin->has_active_subscription = $subscription !== null;
                $admin->subscription_days_remaining = $subscription?->daysRemaining() ?? 0;
                $admin->subscription_expires_at = $subscription?->expires_at?->format('Y-m-d');

                return $admin;
            });

        // Get announcements needed (admins whose subscription expires in 5 days)
        $announcementAdmins = User::regularAdmins()
            ->whereHas('subscription', function ($query) {
                $query->where('is_active', true)
                    ->where('expires_at', '>', now())
                    ->whereRaw('DATEDIFF(expires_at, NOW()) <= ?', [5]);
            })
            ->with('subscription.plan')
            ->get()
            ->map(function ($admin) {
                $subscription = $admin->subscription->first();

                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'plan_name' => $subscription?->plan?->name,
                    'expires_at' => $subscription?->expires_at?->format('Y-m-d'),
                    'days_remaining' => $subscription?->daysRemaining(),
                ];
            });

        return response()->json([
            'settings' => [
                'conversion_rate' => $settings->conversion_rate,
                'cop_reference_amount' => $settings->cop_reference_amount,
                'usd_reference_amount' => $settings->usd_reference_amount,
                'show_ads_days_before' => $settings->show_ads_days_before,
                'enable_payments' => $settings->enable_payments,
                'payment_gateway' => $settings->payment_gateway,
            ],
            'plans' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'duration_days' => $plan->duration_days,
                    'price_cop' => $plan->price_cop,
                    'price_usd' => $plan->price_usd,
                    'discount_percentage' => $plan->discount_percentage,
                    'original_price_cop' => $plan->original_price_cop,
                    'original_price_usd' => $plan->original_price_usd,
                    'is_active' => $plan->is_active,
                    'display_order' => $plan->display_order,
                    'description' => $plan->description,
                ];
            }),
            'admins' => $admins,
            'announcement_admins' => $announcementAdmins,
        ]);
    }

    /**
     * Actualiza la configuración de suscripciones.
     */
    public function updateSettings(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'cop_reference_amount' => 'required|numeric|min:0',
            'usd_reference_amount' => 'required|numeric|min:0',
            'show_ads_days_before' => 'required|integer|min:1|max:30',
            'enable_payments' => 'boolean',
            'payment_gateway' => 'nullable|string',
        ]);

        $settings = SubscriptionSettings::getSettings();

        $settings->update([
            'cop_reference_amount' => $validated['cop_reference_amount'],
            'usd_reference_amount' => $validated['usd_reference_amount'],
            'show_ads_days_before' => $validated['show_ads_days_before'],
            'enable_payments' => $validated['enable_payments'] ?? false,
            'payment_gateway' => $validated['payment_gateway'],
        ]);

        // Recalculate conversion rate
        $settings->recalculateConversionRate();

        return response()->json(['success' => true, 'message' => 'Configuración actualizada']);
    }

    /**
     * Crea o actualiza un plan de suscripción.
     */
    public function storePlan(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_days' => 'required|integer|min:1',
            'price_cop' => 'required|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'original_price_cop' => 'nullable|numeric|min:0',
            'original_price_usd' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
            'description' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\Log::info('Creating plan with data:', $validated);

        $settings = SubscriptionSettings::getSettings();

        // Calculate USD price if not provided
        $priceUsd = $validated['price_usd'] ?? $settings->calculateUsdFromCop($validated['price_cop']);

        $plan = SubscriptionPlan::create([
            'name' => $validated['name'],
            'duration_days' => $validated['duration_days'],
            'price_cop' => $validated['price_cop'],
            'price_usd' => $priceUsd,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'original_price_cop' => $validated['original_price_cop'] ?? null,
            'original_price_usd' => $validated['original_price_usd'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_order' => $validated['display_order'] ?? 0,
            'description' => $validated['description'] ?? null,
        ]);

        \Illuminate\Support\Facades\Log::info('Plan created:', $plan->toArray());

        return response()->json(['success' => true, 'plan' => $plan]);
    }

    /**
     * Actualiza un plan de suscripción.
     */
    public function updatePlan(Request $request, SubscriptionPlan $plan): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_days' => 'required|integer|min:1',
            'price_cop' => 'required|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'original_price_cop' => 'nullable|numeric|min:0',
            'original_price_usd' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
            'description' => 'nullable|string',
        ]);

        $settings = SubscriptionSettings::getSettings();

        // Calculate USD price if not provided
        $priceUsd = $validated['price_usd'] ?? $settings->calculateUsdFromCop($validated['price_cop']);

        $plan->update([
            'name' => $validated['name'],
            'duration_days' => $validated['duration_days'],
            'price_cop' => $validated['price_cop'],
            'price_usd' => $priceUsd,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'original_price_cop' => $validated['original_price_cop'] ?? null,
            'original_price_usd' => $validated['original_price_usd'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_order' => $validated['display_order'] ?? 0,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json(['success' => true, 'plan' => $plan]);
    }

    /**
     * Elimina un plan de suscripción.
     */
    public function destroyPlan(SubscriptionPlan $plan): \Illuminate\Http\JsonResponse
    {
        // Check if any active subscriptions use this plan
        $activeSubscriptions = $plan->subscriptions()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->count();

        if ($activeSubscriptions > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el plan porque tiene suscripciones activas',
            ], 422);
        }

        $plan->delete();

        return response()->json(['success' => true, 'message' => 'Plan eliminado']);
    }

    /**
     * Activa manualmente una suscripción para un admin.
     */
    public function activateSubscription(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Check if user already has an active subscription
        $existingSubscription = $user->subscription()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingSubscription) {
            // Deactivate existing subscription
            $existingSubscription->update(['is_active' => false]);
        }

        // Create new subscription
        $startsAt = now();
        $expiresAt = $startsAt->copy()->addDays($plan->duration_days);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'payment_method' => 'manual',
            'payment_status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Suscripción activada exitosamente',
            'subscription' => [
                'id' => $subscription->id,
                'starts_at' => $subscription->starts_at->format('Y-m-d'),
                'expires_at' => $subscription->expires_at->format('Y-m-d'),
                'plan_name' => $plan->name,
            ],
        ]);
    }

    /**
     * Desactiva la suscripción de un admin.
     */
    public function deactivateSubscription(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $subscription = $user->subscription()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene una suscripción activa',
            ], 422);
        }

        $subscription->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Suscripción desactivada']);
    }

    /**
     * Recalcula los precios en USD basados en la conversión.
     */
    public function recalculatePrices(): \Illuminate\Http\JsonResponse
    {
        $settings = SubscriptionSettings::getSettings();

        $plans = SubscriptionPlan::all();

        foreach ($plans as $plan) {
            $newUsdPrice = $settings->calculateUsdFromCop($plan->price_cop);
            $plan->update(['price_usd' => $newUsdPrice]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Precios recalculados correctamente',
            'updated_count' => $plans->count(),
        ]);
    }

    /**
     * Obtiene todos los anuncios.
     */
    public function getAnnouncements(): \Illuminate\Http\JsonResponse
    {
        $announcements = Announcement::orderByDesc('created_at')->get();

        return response()->json([
            'announcements' => $announcements->map(function ($a) {
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'message' => $a->message,
                    'type' => $a->type,
                    'days_before' => $a->days_before,
                    'is_active' => $a->is_active,
                    'created_at' => $a->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Crea un nuevo anuncio.
     */
    public function storeAnnouncement(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:expiring,expired,general',
            'days_before' => 'nullable|integer|min:1|max:365',
            'is_active' => 'boolean',
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'],
            'days_before' => $validated['days_before'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'message' => $announcement->message,
                'type' => $announcement->type,
                'days_before' => $announcement->days_before,
                'is_active' => $announcement->is_active,
            ],
        ]);
    }

    /**
     * Actualiza un anuncio.
     */
    public function updateAnnouncement(Request $request, Announcement $announcement): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:expiring,expired,general',
            'days_before' => 'nullable|integer|min:1|max:365',
            'is_active' => 'boolean',
        ]);

        $announcement->update($validated);

        return response()->json([
            'success' => true,
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'message' => $announcement->message,
                'type' => $announcement->type,
                'days_before' => $announcement->days_before,
                'is_active' => $announcement->is_active,
            ],
        ]);
    }

    /**
     * Elimina un anuncio.
     */
    public function destroyAnnouncement(Announcement $announcement): \Illuminate\Http\JsonResponse
    {
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Anuncio eliminado',
        ]);
    }
}
