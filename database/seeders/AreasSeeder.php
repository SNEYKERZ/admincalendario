<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            [
                'name' => 'Recursos Humanos',
                'description' => 'Gestión de talento humano, nóminas y bienestar laboral',
                'color' => '#8B5CF6',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Tecnología',
                'description' => 'Desarrollo de software, infraestructura y soporte técnico',
                'color' => '#3B82F6',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Ventas',
                'description' => 'Comercialización de productos y servicios',
                'color' => '#10B981',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Comunicación, publicidad y branding',
                'color' => '#F59E0B',
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Finanzas',
                'description' => 'Contabilidad, tesorería y planificación financiera',
                'color' => '#6366F1',
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Operaciones',
                'description' => 'Logística, producción y operaciones',
                'color' => '#EC4899',
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Servicio al Cliente',
                'description' => 'Atención al cliente y soporte post-venta',
                'color' => '#14B8A6',
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Administración',
                'description' => 'Gestión administrativa y servicios generales',
                'color' => '#78716C',
                'display_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($areas as $area) {
            Area::updateOrCreate(
                ['name' => $area['name']],
                $area
            );
        }

        $this->command->info('✅ Áreas sembradas: '.count($areas));
    }
}
