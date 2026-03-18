<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\User;

class StoreAbsenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // luego meter policies
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'absence_type_id' => ['required', 'exists:absence_types,id'],

            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after_or_equal:start_datetime'],

            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_datetime.after_or_equal' => 'La fecha/hora final debe ser mayor o igual a la inicial',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZACIÓN DE DATOS (antes de validar fuerte)
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation()
    {
        if ($this->start_datetime) {
            $this->merge([
                'start_datetime' => Carbon::parse($this->start_datetime),
            ]);
        }

        if ($this->end_datetime) {
            $this->merge([
                'end_datetime' => Carbon::parse($this->end_datetime),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES AVANZADAS
    |--------------------------------------------------------------------------
    */

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $user = User::find($this->user_id);
            $type = AbsenceType::find($this->absence_type_id);

            if (!$user || !$type) {
                return;
            }

            $start = Carbon::parse($this->start_datetime);
            $end = Carbon::parse($this->end_datetime);

            /*
            |--------------------------------------------------------------------------
            | 1. Validar rango coherente
            |--------------------------------------------------------------------------
            */

            if ($start->gt($end)) {
                $validator->errors()->add('start_datetime', 'La fecha inicial no puede ser mayor a la final');
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Validar horas vs días
            |--------------------------------------------------------------------------
            */

            $sameDay = $start->toDateString() === $end->toDateString();

            if (!$sameDay && $type->counts_as_hours) {
                $validator->errors()->add('end_datetime', 'No puedes usar horas en múltiples días');
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Validar rango de horas lógico
            |--------------------------------------------------------------------------
            */

            if ($sameDay && $type->counts_as_hours) {

                if ($start->hour === $end->hour && $start->minute === $end->minute) {
                    $validator->errors()->add('end_datetime', 'El rango de horas no puede ser igual');
                }

                if ($start->gt($end)) {
                    $validator->errors()->add('end_datetime', 'La hora final debe ser mayor');
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Validar cumpleaños
            |--------------------------------------------------------------------------
            */

            if ($type->name === 'Cumpleaños') {

                // Solo un día
                if (!$sameDay) {
                    $validator->errors()->add('start_datetime', 'El día de cumpleaños solo puede ser un día');
                }

                // Mes correcto
                if ($user->birth_date) {
                    if ($start->month !== $user->birth_date->month) {
                        $validator->errors()->add('start_datetime', 'Solo puedes tomar el día en el mes de tu cumpleaños');
                    }
                }

                // Solo una vez por año
                $exists = Absence::where('user_id', $user->id)
                    ->whereYear('start_datetime', $start->year)
                    ->where('absence_type_id', $type->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('start_datetime', 'Ya usaste el día de cumpleaños este año');
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Validar solapamientos (MUY IMPORTANTE)
            |--------------------------------------------------------------------------
            */

            $overlap = Absence::where('user_id', $user->id)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_datetime', [$start, $end])
                      ->orWhereBetween('end_datetime', [$start, $end])
                      ->orWhere(function ($q2) use ($start, $end) {
                          $q2->where('start_datetime', '<=', $start)
                             ->where('end_datetime', '>=', $end);
                      });
                })
                ->whereIn('status', ['pendiente', 'aprobado'])
                ->exists();

            if ($overlap) {
                $validator->errors()->add('start_datetime', 'Ya tienes una solicitud en ese rango de fechas');
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Validar vacaciones (saldo previo)
            |--------------------------------------------------------------------------
            */

            if ($type->deducts_vacation) {

                $days = $this->calculateDays($start, $end, $type);

                $available = $user->availableVacationDays();

                if ($days > $available) {
                    $validator->errors()->add('start_datetime', 'No tienes suficientes días disponibles');
                }
            }

        });
    }

    /*
    |--------------------------------------------------------------------------
    | CÁLCULO DE DÍAS Y HORAS (NO confiar en frontend)
    |--------------------------------------------------------------------------
    */

    private function calculateDays($start, $end, $type): float
    {
        if ($type->counts_as_hours) {

            $hours = $start->diffInMinutes($end) / 60;

            return round($hours / 8, 2); // 8h = 1 día
        }

        return $start->diffInDays($end) + 1;
    }

    /*
    |--------------------------------------------------------------------------
    | DATA FINAL LIMPIA PARA EL SERVICE
    |--------------------------------------------------------------------------
    */

    public function validatedData(): array
    {
        $data = $this->validated();

        $type = AbsenceType::find($data['absence_type_id']);

        $start = Carbon::parse($data['start_datetime']);
        $end = Carbon::parse($data['end_datetime']);

        if ($type->counts_as_hours) {
            $hours = $start->diffInMinutes($end) / 60;
            $days = $hours / 8;
        } else {
            $days = $start->diffInDays($end) + 1;
            $hours = $days * 8;
        }

        $data['total_days'] = round($days, 2);
        $data['total_hours'] = round($hours, 2);

        return $data;
    }
}