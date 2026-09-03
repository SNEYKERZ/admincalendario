# Script para corregir caracteres UTF-8 corruptos en archivos Vue

$replacements = @{
    'Ã©' = 'é'
    'Ã¡' = 'á'
    'Ã­' = 'í'
    'Ã³' = 'ó'
    'Ã ' = 'Á'
    'Ã‰' = 'É'
    'Ã' = 'Í'
    'Ã"' = 'Ó'
    'Ã' = 'Ú'
    'Ã±' = 'ñ'
    'Ã' = 'Ñ'
    'Ã¼' = 'ü'
    'Ã' = 'Ü'
    'Â°' = '°'
    'Â»' = '»'
    'aÃ±o' = 'año'
    'dÃ­a' = 'día'
    'AnticipaciÃ³n' = 'Anticipación'
    'MÃ¡xima' = 'Máxima'
}

Write-Host "Corrigiendo caracteres UTF-8 corruptos en archivos Vue..."

Get-ChildItem -Path "resources/js" -Filter "*.vue" -Recurse | ForEach-Object {
    $file = $_.FullName
    $content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)
    $originalContent = $content

    foreach ($corrupt in $replacements.Keys) {
        $correct = $replacements[$corrupt]
        if ($content -match [regex]::Escape($corrupt)) {
            $content = $content -replace [regex]::Escape($corrupt), $correct
            Write-Host "  Corregido '$corrupt' → '$correct' en $($_.Name)"
        }
    }

    if ($content -ne $originalContent) {
        [System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
    }
}

Write-Host "`nListo! Caracteres UTF-8 corregidos en archivos Vue"
