<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\OvertimeHoursService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OvertimeHoursSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Sembrando horas extra...');

        $service = app(OvertimeHoursService::class);

        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->command->warn('⚠️ No hay admin para registrar/aprobar horas extra. Se omite.');

            return;
        }

        // Simula que quien registra los lotes es el admin (igual que en la app real,
        // el admin puede crear horas extra para cualquier colaborador de su tenant).
        Auth::login($admin);

        $lastMonth = now()->subMonthNoOverflow();
        $thisMonth = now();
        $nextMonth = now()->addMonthNoOverflow();

        $date = fn (CarbonInterface $month, int $day) => Carbon::createFromDate($month->year, $month->month, $day)->toDateString();

        // 5 colaboradores de 5 áreas distintas, para poder cruzar reportes por área.
        $records = [
            [
                'email' => 'ana.lopez@ausentra.com', // Recursos Humanos
                'entries' => [
                    ['date' => $date($lastMonth, 5), 'start' => '18:00', 'end' => '19:30', 'project' => 'Cierre de nómina', 'client' => null, 'decision' => 'approved'],
                    ['date' => $date($lastMonth, 19), 'start' => '18:00', 'end' => '20:00', 'project' => 'Cierre de nómina', 'client' => null, 'decision' => 'approved'],
                    ['date' => $date($thisMonth, 3), 'start' => '18:00', 'end' => '19:00', 'project' => 'Inducción nuevos empleados', 'client' => null, 'decision' => 'pending'],
                    ['date' => $date($thisMonth, 16), 'start' => '18:30', 'end' => '20:00', 'project' => 'Auditoría de contratos', 'client' => null, 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 6), 'start' => '18:00', 'end' => '19:30', 'project' => 'Cierre de nómina', 'client' => null, 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 20), 'start' => '18:00', 'end' => '19:00', 'project' => 'Bienestar laboral', 'client' => null, 'decision' => 'rejected'],
                ],
            ],
            [
                'email' => 'luis.ramirez@ausentra.com', // Tecnología
                'entries' => [
                    ['date' => $date($lastMonth, 6), 'start' => '19:00', 'end' => '21:00', 'project' => 'Migración de servidores', 'client' => 'Interno', 'decision' => 'approved'],
                    ['date' => $date($lastMonth, 20), 'start' => '18:00', 'end' => '19:30', 'project' => 'Soporte incidente crítico', 'client' => 'Interno', 'decision' => 'approved'],
                    ['date' => $date($thisMonth, 4), 'start' => '18:00', 'end' => '20:00', 'project' => 'Despliegue módulo horas extra', 'client' => 'Interno', 'decision' => 'approved'],
                    ['date' => $date($thisMonth, 17), 'start' => '18:00', 'end' => '19:00', 'project' => 'Soporte incidente crítico', 'client' => 'Interno', 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 7), 'start' => '18:30', 'end' => '20:00', 'project' => 'Migración de servidores', 'client' => 'Interno', 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 21), 'start' => '18:00', 'end' => '19:30', 'project' => 'Mantenimiento base de datos', 'client' => 'Interno', 'decision' => 'pending'],
                ],
            ],
            [
                'email' => 'camila.torres@ausentra.com', // Ventas
                'entries' => [
                    ['date' => $date($lastMonth, 7), 'start' => '18:00', 'end' => '19:00', 'project' => 'Cierre comercial de mes', 'client' => 'Grupo Nortex', 'decision' => 'approved'],
                    ['date' => $date($lastMonth, 21), 'start' => '18:00', 'end' => '20:00', 'project' => 'Feria comercial', 'client' => 'Distribuidora Andina', 'decision' => 'pending'],
                    ['date' => $date($thisMonth, 8), 'start' => '18:00', 'end' => '19:30', 'project' => 'Cierre comercial de mes', 'client' => 'Grupo Nortex', 'decision' => 'approved'],
                    ['date' => $date($thisMonth, 19), 'start' => '18:00', 'end' => '19:00', 'project' => 'Visita a cliente', 'client' => 'Comercial Rivas', 'decision' => 'rejected'],
                    ['date' => $date($nextMonth, 9), 'start' => '18:30', 'end' => '20:00', 'project' => 'Cierre comercial de mes', 'client' => 'Grupo Nortex', 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 23), 'start' => '18:00', 'end' => '19:00', 'project' => 'Feria comercial', 'client' => 'Distribuidora Andina', 'decision' => 'pending'],
                ],
            ],
            [
                'email' => 'valentina.diaz@ausentra.com', // Marketing
                'entries' => [
                    ['date' => $date($lastMonth, 10), 'start' => '18:00', 'end' => '19:30', 'project' => 'Lanzamiento de campaña', 'client' => null, 'decision' => 'approved'],
                    ['date' => $date($lastMonth, 24), 'start' => '18:00', 'end' => '19:00', 'project' => 'Producción de contenido', 'client' => null, 'decision' => 'pending'],
                    ['date' => $date($thisMonth, 5), 'start' => '18:00', 'end' => '20:00', 'project' => 'Lanzamiento de campaña', 'client' => null, 'decision' => 'approved'],
                    ['date' => $date($thisMonth, 20), 'start' => '18:00', 'end' => '19:00', 'project' => 'Evento de marca', 'client' => null, 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 11), 'start' => '18:30', 'end' => '19:30', 'project' => 'Producción de contenido', 'client' => null, 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 25), 'start' => '18:00', 'end' => '19:30', 'project' => 'Evento de marca', 'client' => null, 'decision' => 'rejected'],
                ],
            ],
            [
                'email' => 'sebastian.vargas@ausentra.com', // Finanzas
                'entries' => [
                    ['date' => $date($lastMonth, 12), 'start' => '18:00', 'end' => '19:30', 'project' => 'Cierre contable mensual', 'client' => null, 'decision' => 'approved'],
                    ['date' => $date($lastMonth, 26), 'start' => '18:00', 'end' => '20:00', 'project' => 'Auditoría externa', 'client' => 'Firma Auditora ABC', 'decision' => 'approved'],
                    ['date' => $date($thisMonth, 6), 'start' => '18:00', 'end' => '19:00', 'project' => 'Cierre contable mensual', 'client' => null, 'decision' => 'approved'],
                    ['date' => $date($thisMonth, 21), 'start' => '18:00', 'end' => '19:30', 'project' => 'Auditoría externa', 'client' => 'Firma Auditora ABC', 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 13), 'start' => '18:30', 'end' => '20:00', 'project' => 'Presupuesto próximo año', 'client' => null, 'decision' => 'pending'],
                    ['date' => $date($nextMonth, 27), 'start' => '18:00', 'end' => '19:00', 'project' => 'Cierre contable mensual', 'client' => null, 'decision' => 'pending'],
                ],
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($records as $userRecord) {
            $user = User::where('email', $userRecord['email'])->first();

            if (! $user) {
                $this->command->warn("⚠️ Usuario {$userRecord['email']} no existe. Se omite.");

                continue;
            }

            foreach ($userRecord['entries'] as $entry) {
                try {
                    $overtime = $service->create([
                        'user_id' => $user->id,
                        'date' => $entry['date'],
                        'start_time' => $entry['start'],
                        'end_time' => $entry['end'],
                        'project' => $entry['project'],
                        'client' => $entry['client'],
                        'reason' => 'Horas extra registradas por demanda operativa del área.',
                    ]);

                    match ($entry['decision']) {
                        'approved' => $service->approve($overtime, $admin),
                        'rejected' => $service->reject($overtime, $admin, 'No se justifica la hora extra para esta fecha.'),
                        default => null, // se queda 'pending'
                    };

                    $created++;
                } catch (ValidationException $e) {
                    // Ya existe un registro para ese usuario+fecha (re-ejecución del seeder) u otra
                    // regla de negocio no se cumplió: lo saltamos sin romper el resto del seeding.
                    $skipped++;
                }
            }
        }

        Auth::logout();

        $this->command->info("✅ Horas extra sembradas: {$created} creadas, {$skipped} omitidas (ya existían).");
    }
}
