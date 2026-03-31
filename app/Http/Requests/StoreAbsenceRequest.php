<?php

namespace App\Http\Requests;

use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\User;
use App\Services\AbsenceCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
{
    /**
     * Almacena el cálculo para no duplicar lógica
     */
    protected ?array $cachedCalculation = null;

    public function authorize(): bool
    {
        // La autorización se maneja via AbsencePolicy en el controlador
        // Este método retorna true porque Laravel primero verifica policies
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        $userRules = $this->user()?->isAdmin()
            ? ['required', 'exists:users,id']
            : ['nullable'];

        return [
            'user_id' => $userRules,
            'absence_type_id' => ['required', 'exists:absence_types,id'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'include_saturday' => ['nullable', 'boolean'],
            'include_sunday' => ['nullable', 'boolean'],
            'include_holidays' => ['nullable', 'boolean'],
            'holiday_country' => ['nullable', 'string', 'size:2'],
            'reason' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_datetime.after' => 'La fecha/hora final debe ser mayor a la inicial',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()?->isAdmin() && $this->user()) {
            $this->merge([
                'user_id' => $this->user()->id,
            ]);
        }

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

        if ($this->holiday_country) {
            $this->merge([
                'holiday_country' => strtoupper((string) $this->holiday_country),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->user()?->isAdmin()
                ? $this->user_id
                : $this->user()?->id;

            $user = User::find($userId);
            $type = AbsenceType::find($this->absence_type_id);

            if (! $user || ! $type) {
                return;
            }

            $start = Carbon::parse($this->start_datetime);
            $end = Carbon::parse($this->end_datetime);

            if ($start->gt($end)) {
                $validator->errors()->add('start_datetime', 'La fecha inicial no puede ser mayor a la final');

                return;
            }

            $sameDay = $start->toDateString() === $end->toDateString();

            if (! $sameDay && $type->counts_as_hours) {
                $validator->errors()->add('end_datetime', 'No puedes usar horas en multiples dias');
            }

            if ($sameDay && $type->counts_as_hours && $start->equalTo($end)) {
                $validator->errors()->add('end_datetime', 'El rango de horas no puede ser igual');
            }

            if ($type->name === 'Cumpleaños') {
                if (! $sameDay) {
                    $validator->errors()->add('start_datetime', 'El dia de cumpleaños solo puede ser un dia');
                }

                if ($user->birth_date && $start->month !== $user->birth_date->month) {
                    $validator->errors()->add('start_datetime', 'Solo puedes tomar el dia en el mes de tu cumpleaños');
                }

                $exists = Absence::where('user_id', $user->id)
                    ->whereYear('start_datetime', $start->year)
                    ->where('absence_type_id', $type->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('start_datetime', 'Ya usaste el dia de cumpleaños este año');
                }
            }

            $overlap = Absence::where('user_id', $user->id)
                ->whereIn('status', ['pendiente', 'aprobado'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_datetime', [$start, $end])
                        ->orWhereBetween('end_datetime', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_datetime', '<=', $start)
                                ->where('end_datetime', '>=', $end);
                        });
                })
                ->exists();

            if ($overlap) {
                $validator->errors()->add('start_datetime', 'Ya tienes una solicitud en ese rango de fechas');
            }

            $calculationService = app(AbsenceCalculationService::class);
            $calculation = $calculationService->calculate(
                $type,
                $start,
                $end,
                $calculationService->resolveOptions($type, $this->all())
            );

            // Guardar cálculo para reutilizarlo en validatedData()
            $this->cachedCalculation = $calculation;

            if ($calculation['total_days'] <= 0) {
                $validator->errors()->add('start_datetime', 'El rango no contiene dias contabilizables con las reglas seleccionadas');
            }

            if ($type->deducts_vacation && $calculation['total_days'] > $user->availableVacationDays()) {
                $validator->errors()->add('start_datetime', 'No tienes suficientes dias disponibles');
            }
        });
    }

    public function validatedData(): array
    {
        $data = $this->validated();

        if (! $this->user()?->isAdmin() && $this->user()) {
            $data['user_id'] = $this->user()->id;
        }

        // Usar cálculo cacheado desde withValidator para evitar duplicación
        $calculation = $this->cachedCalculation;

        if (! $calculation) {
            // Fallback: calcular si no se hizo en el validator (edge case)
            $type = AbsenceType::findOrFail($data['absence_type_id']);
            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);
            $calculationService = app(AbsenceCalculationService::class);
            $calculation = $calculationService->calculate(
                $type,
                $start,
                $end,
                $calculationService->resolveOptions($type, $data)
            );
        }

        return [
            ...$data,
            'include_saturday' => $calculation['include_saturday'],
            'include_sunday' => $calculation['include_sunday'],
            'include_holidays' => $calculation['include_holidays'],
            'holiday_country' => $calculation['holiday_country'],
            'total_days' => $calculation['total_days'],
            'total_hours' => $calculation['total_hours'],
        ];
    }
}
