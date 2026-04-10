<?php

namespace App\Console\Commands;

use App\Models\HrDocument;
use App\Models\User;
use App\Notifications\DocumentExpirationAlert;
use Illuminate\Console\Command;

class SendDocumentExpirationAlerts extends Command
{
    protected $signature = 'documents:send-expiration-alerts {--days=30 : Días antes del vencimiento para alertar}';

    protected $description = 'Envía alertas automáticas para documentos próximos a vencer o vencidos';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $today = now()->startOfDay();
        $limit = now()->addDays($days)->endOfDay();

        $documents = HrDocument::with(['user', 'uploader'])
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $limit->toDateString())
            ->get();

        $admins = User::admins()->get();
        $sent = 0;

        foreach ($documents as $document) {
            $expiresAt = $document->expires_at?->startOfDay();
            if (! $expiresAt) {
                continue;
            }

            $daysRemaining = $today->diffInDays($expiresAt, false);
            $alertType = $daysRemaining < 0 ? 'expired' : 'expiring_soon';

            if ($document->last_alert_type === $alertType) {
                continue;
            }

            foreach ($admins as $admin) {
                $admin->notify(new DocumentExpirationAlert($document, $alertType, max($daysRemaining, 0)));
                $sent++;
            }

            if ($document->user) {
                $document->user->notify(new DocumentExpirationAlert($document, $alertType, max($daysRemaining, 0)));
                $sent++;
            }

            $document->update([
                'last_alert_sent_at' => now(),
                'last_alert_type' => $alertType,
            ]);
        }

        $this->info("Alertas enviadas: {$sent}");

        return self::SUCCESS;
    }
}
