<?php

namespace App\Notifications;

use App\Models\Absence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AbsenceRejected extends Notification
{
    use Queueable;

    public function __construct(
        public Absence $absence,
        public ?string $reason = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $message = (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('❌ Ausencia rechazada')
            ->greeting("Hola {$notifiable->first_name},")
            ->line('Tu solicitud de ausencia ha sido **RECHAZADA**.')
            ->line("**Tipo:** {$this->absence->type->name}")
            ->line("**Período:** {$this->absence->start_datetime->format('d/m/Y')} a {$this->absence->end_datetime->format('d/m/Y')}");

        if ($this->reason) {
            $message->line("**Motivo:** {$this->reason}");
        }

        return $message
            ->action('Ver detalles', url("/absences/{$this->absence->id}"))
            ->line('Si tienes preguntas, contáctate con tu jefe de área o el equipo de RH.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Ausencia rechazada',
            'message' => "Tu ausencia de {$this->absence->type->name} fue rechazada",
            'reason' => $this->reason,
            'absence_id' => $this->absence->id,
            'type' => 'absence_rejected',
        ];
    }
}
