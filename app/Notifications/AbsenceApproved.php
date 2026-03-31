<?php

namespace App\Notifications;

use App\Models\Absence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AbsenceApproved extends Notification
{
    use Queueable;

    public function __construct(
        public Absence $absence
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Ausencia aprobada')
            ->line('Tu solicitud de ausencia ha sido APROBADA.')
            ->line("Tipo: {$this->absence->type->name}")
            ->line("Desde: {$this->absence->start_datetime->format('d/m/Y')}")
            ->line("Hasta: {$this->absence->end_datetime->format('d/m/Y')}")
            ->line("Días: {$this->absence->total_days}")
            ->action('Ver detalles', url('/dashboard'))
            ->line('¡Disfruta tu ausencia!');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Ausencia aprobada',
            'message' => "Tu ausencia de {$this->absence->type->name} fue aprobada",
            'absence_id' => $this->absence->id,
            'type' => 'absence_approved',
        ];
    }
}
