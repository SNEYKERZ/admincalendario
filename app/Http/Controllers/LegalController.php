<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    private const PAGES = [
        'terms' => 'LegalTerms',
        'privacy' => 'LegalPrivacy',
        'cookies' => 'LegalCookies',
        'refunds' => 'LegalRefunds',
    ];

    public function show(string $page): Response
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        return Inertia::render(self::PAGES[$page], [
            'legal' => config('legal'),
            'cookies' => [
                'session' => config('session.cookie'),
                'session_minutes' => (int) config('session.lifetime'),
            ],
        ]);
    }
}
