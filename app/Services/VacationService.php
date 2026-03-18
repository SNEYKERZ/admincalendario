<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class VacationService
{
    public function deductDays(User $user, float $daysToDiscount): void
    {
        DB::transaction(function () use ($user, $daysToDiscount) {

            $vacationYears = $user->vacationYears()
                ->where('expires_at', '>=', now())
                ->orderBy('year')
                ->get();

            foreach ($vacationYears as $year) {

                $available = $year->allocated_days - $year->used_days;

                if ($available <= 0) continue;

                if ($available >= $daysToDiscount) {
                    $year->used_days += $daysToDiscount;
                    $year->save();
                    return;
                }

                $year->used_days += $available;
                $year->save();
                $daysToDiscount -= $available;
            }

            if ($daysToDiscount > 0) {
                throw new Exception("No hay saldo suficiente");
            }
        });
    }
}