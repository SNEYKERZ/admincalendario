<?php

/*
|--------------------------------------------------------------------------
| Datos legales del responsable del sitio
|--------------------------------------------------------------------------
|
| Estos valores se muestran en las páginas legales públicas (/legal/*).
| Deben completarse en el .env con los datos reales de la empresa antes de
| salir a producción. Mientras falten, las páginas muestran un marcador
| visible "[PENDIENTE: ...]" para que no pase desapercibido.
|
| Cambie LEGAL_VERSION cada vez que modifique el texto de las políticas:
| se guarda junto con la aceptación de cada usuario como prueba.
|
*/

return [
    'version' => env('LEGAL_VERSION', '2026-09-30'),
    'updated_at' => env('LEGAL_UPDATED_AT', '2026-09-30'),

    'product_name' => env('LEGAL_PRODUCT_NAME', 'Ausentra'),
    'company_name' => env('LEGAL_COMPANY_NAME'),
    'company_nit' => env('LEGAL_COMPANY_NIT'),
    'address' => env('LEGAL_ADDRESS'),
    'city' => env('LEGAL_CITY'),
    'country' => env('LEGAL_COUNTRY', 'Colombia'),
    'phone' => env('LEGAL_PHONE'),
    'privacy_email' => env('LEGAL_PRIVACY_EMAIL'),
    'support_email' => env('LEGAL_SUPPORT_EMAIL'),
    'website' => env('LEGAL_WEBSITE', env('APP_URL')),
];
