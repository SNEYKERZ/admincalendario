# Script para renombrar "Ausencia" a "Ausencia/Novedad"
$searchReplace = @(
    @{ search = 'Nueva Ausencia'; replace = 'Nueva Ausencia/Novedad' },
    @{ search = 'Ausencia actual'; replace = 'Ausencia/Novedad actual' },
    @{ search = 'Mis ausencias recientes'; replace = 'Mis ausencias/novedades recientes' },
    @{ search = 'Ausencias recientes del equipo'; replace = 'Ausencias/Novedades recientes del equipo' },
    @{ search = '"Ausencia eliminada"'; replace = '"Ausencia/Novedad eliminada"' },
    @{ search = '"Ausencia creada"'; replace = '"Ausencia/Novedad creada"' },
    @{ search = 'Tipo de Ausencia'; replace = 'Tipo de Ausencia/Novedad' },
    @{ search = 'Resumen de Ausencias'; replace = 'Resumen de Ausencias/Novedades' },
    @{ search = 'Total de Ausencias'; replace = 'Total de Ausencias/Novedades' },
    @{ search = 'Ausencias Pendientes'; replace = 'Ausencias/Novedades Pendientes' },
    @{ search = 'Reglas de Ausencias'; replace = 'Reglas de Ausencias/Novedades' },
    @{ search = 'Gestión de Ausencias'; replace = 'Gestión de Ausencias/Novedades' },
    @{ search = 'Calendario de Ausencias'; replace = 'Calendario de Ausencias/Novedades' },
    @{ search = 'Aprobar Ausencia'; replace = 'Aprobar Ausencia/Novedad' },
    @{ search = 'Rechazar Ausencia'; replace = 'Rechazar Ausencia/Novedad' }
)

Get-ChildItem -Path "C:\laragon\www\admincalendar\resources\js" -Filter "*.vue" -Recurse | ForEach-Object {
    $file = $_.FullName
    $content = Get-Content $file -Raw

    foreach ($pair in $searchReplace) {
        if ($content -match [regex]::Escape($pair.search)) {
            $content = $content -replace [regex]::Escape($pair.search), $pair.replace
            Write-Host "[DONE] Reemplazado en: $($_.Name)"
        }
    }

    Set-Content $file $content -Encoding UTF8
}

Write-Host "`n[OK] Renombrado completado"
