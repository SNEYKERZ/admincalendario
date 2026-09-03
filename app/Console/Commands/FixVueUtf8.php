<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixVueUtf8 extends Command
{
    protected $signature = 'app:fix-vue-utf8';
    protected $description = 'Corrige caracteres UTF-8 corruptos en archivos Vue';

    public function handle()
    {
        $this->info('=== Corrigiendo UTF-8 en archivos Vue ===');

        $replacements = [
            'Ã©' => 'é',
            'Ã¡' => 'á',
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ã¼' => 'ü',
            'Ã±' => 'ñ',
            'aÃ±o' => 'año',
            'dÃ­a' => 'día',
            'AnticipaciÃ³n' => 'Anticipación',
            'MÃ¡xima' => 'Máxima',
            'invÃ¡lida' => 'inválida',
            'estÃ¡' => 'está',
        ];

        $vueFiles = File::glob(base_path('resources/js/**/*.vue'));
        $totalFixed = 0;

        foreach ($vueFiles as $file) {
            $content = File::get($file);
            $originalContent = $content;

            foreach ($replacements as $corrupt => $correct) {
                if (strpos($content, $corrupt) !== false) {
                    $content = str_replace($corrupt, $correct, $content);
                    $totalFixed++;
                    $this->line("✓ Corregido '$corrupt' → '$correct' en " . basename($file));
                }
            }

            if ($content !== $originalContent) {
                File::put($file, $content);
            }
        }

        $this->info("\n✅ Corrección completada");
        $this->info("Total de reemplazos realizados: $totalFixed");

        return self::SUCCESS;
    }
}
