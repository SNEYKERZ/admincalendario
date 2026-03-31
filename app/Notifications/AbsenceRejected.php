<?php

namespace App\Notifications;

use App\Models\Absence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AbsenceRejected extends Notification
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
            ->subject('Ausencia rechazada')
            ->line('Tu solicitud de ausencia ha sido RECHAZADA.')
            ->line("Tipo: {$this->absence->type->name}")
            ->line("Desde: {$this->absence->start_datetime->format('d/m/Y')}")
            ->line("Hasta: {$this->absence->end_datetime->format('d/m/Y')}")
            ->action('Ver detalles', url('/dashboard'))
            ->line('Por favor, contacta a tu administrador para más información.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Ausencia rechazada',
            'message' => "Tu ausencia de {$this->absence->type->name} fue rechazada",
            'absence_id' => $this->absence->id,
            'type' => 'absence_rejected',
        ];
    }
}
