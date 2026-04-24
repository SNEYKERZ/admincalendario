<?php

namespace App\Http\Controllers;

use App\Models\CompanySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = CompanySettings::getSettings();

        return response()->json($this->withLogoUrl($settings));
    }

    public function update(Request $request)
    {
        $settings = CompanySettings::getSettings();

        $data = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'company_logo' => 'nullable|image|max:2048',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:30',
            'company_email' => 'nullable|email',
            'company_identification' => 'nullable|string|max:50',
            'vacation_days_default' => 'sometimes|integer|min:0|max:365',
            'vacation_days_advance' => 'sometimes|integer|min:0',
            'workday_start' => 'sometimes|date_format:H:i',
            'workday_end' => 'sometimes|date_format:H:i',
            'allow_weekend_absences' => 'sometimes|boolean',
            'allow_holiday_absences' => 'sometimes|boolean',
            'require_approval_for_all' => 'sometimes|boolean',
            'notification_email_enabled' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('company_logo')) {
            if ($settings->company_logo) {
                Storage::disk('public')->delete($settings->company_logo);
            }
            $data['company_logo'] = $request->file('company_logo')->store('company', 'public');
        }

        $settings->update($data);

        return response()->json($this->withLogoUrl($settings->fresh()));
    }

    protected function withLogoUrl(CompanySettings $settings): array
    {
        $payload = $settings->toArray();
        $payload['company_logo_url'] = $settings->company_logo
            ? Storage::disk('public')->url($settings->company_logo)
            : null;

        return $payload;
    }
}
