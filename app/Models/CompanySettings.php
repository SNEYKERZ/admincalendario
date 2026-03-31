<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySettings extends Model
{
    protected $fillable = [
        'company_name',
        'company_logo',
        'company_address',
        'company_phone',
        'company_email',
        'company_identification',
        'vacation_days_default',
        'vacation_days_advance',
        'workday_start',
        'workday_end',
        'allow_weekend_absences',
        'allow_holiday_absences',
        'require_approval_for_all',
        'notification_email_enabled',
    ];

    protected $casts = [
        'vacation_days_default' => 'integer',
        'vacation_days_advance' => 'integer',
        'allow_weekend_absences' => 'boolean',
        'allow_holiday_absences' => 'boolean',
        'require_approval_for_all' => 'boolean',
        'notification_email_enabled' => 'boolean',
    ];

    public static function getSettings(): self
    {
        return static::first() ?? static::create([
            'company_name' => config('app.name'),
            'vacation_days_default' => 15,
            'vacation_days_advance' => 30,
            'workday_start' => '08:00',
            'workday_end' => '17:00',
            'allow_weekend_absences' => false,
            'allow_holiday_absences' => false,
            'require_approval_for_all' => true,
            'notification_email_enabled' => true,
        ]);
    }
}
