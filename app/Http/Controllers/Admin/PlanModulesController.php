<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Module;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanModulesController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::with('modules')->get();
        $allModules = Module::orderBy('display_order')->get();

        $plansWithModules = $plans->map(function ($plan) use ($allModules) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price_cop' => $plan->price_cop,
                'price_usd' => $plan->price_usd,
                'discount_percentage' => $plan->discount_percentage,
                'duration_days' => $plan->duration_days,
                'description' => $plan->description,
                'module_ids' => $plan->modules->pluck('id')->toArray(),
                'modules' => $plan->modules,
            ];
        });

        return Inertia::render('Admin/PlanModules/Index', [
            'plans' => $plansWithModules,
            'allModules' => $allModules,
        ]);
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'price_cop' => 'nullable|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'duration_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'module_ids' => 'required|array',
            'module_ids.*' => 'exists:modules,id',
        ]);

        $plan->update([
            'name' => $data['name'],
            'price_cop' => $data['price_cop'] ?? $plan->price_cop,
            'price_usd' => $data['price_usd'] ?? $plan->price_usd,
            'discount_percentage' => $data['discount_percentage'] ?? $plan->discount_percentage,
            'duration_days' => $data['duration_days'],
            'description' => $data['description'],
        ]);

        $plan->modules()->sync($data['module_ids']);

        return redirect()->route('admin.plan-modules.index')->with('success', 'Plan actualizado correctamente');
    }
}
